<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock_quantity, p.points_reward
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Calculate totals
$subtotal = 0;
$total_points = 0;
foreach ($cart_items as $item) {
    $price = $item['discount_price'] ?: $item['price'];
    $subtotal += $price * $item['quantity'];
    $total_points += $item['points_reward'] * $item['quantity'];
}

$shipping = $subtotal > 500 ? 0 : 50;
$total = $subtotal + $shipping;

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $points_to_use = (int)($_POST['points_to_use'] ?? 0);
    
    // Validation
    if (empty($full_name) || empty($mobile) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidMobile($mobile)) {
        $error = 'Please enter a valid mobile number';
    } elseif ($points_to_use > $user['points']) {
        $error = 'Insufficient points';
    } elseif ($points_to_use > 0 && $points_to_use < 100) {
        $error = 'Minimum 100 points required for redemption';
    } else {
        // Calculate final amount after points redemption
        $points_value = $points_to_use / 2; // 2 points = 1 rupee
        $final_amount = max(0, $total - $points_value);
        
        // Create order
        $order_number = generateOrderNumber();
        $shipping_address = "$full_name\n$mobile\n$address\n$city, $state - $pincode";
        
        try {
            $pdo->beginTransaction();
            
            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, points_used, final_amount, points_earned, shipping_address, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'UPI')
            ");
            $stmt->execute([$user_id, $order_number, $total, $points_to_use, $final_amount, $total_points, $shipping_address]);
            $order_id = $pdo->lastInsertId();
            
            // Insert order items
            foreach ($cart_items as $item) {
                $price = $item['discount_price'] ?: $item['price'];
                $item_total = $price * $item['quantity'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price, total) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $price, $item_total]);
                
                // Update stock
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Update user points and orders
            $new_points = $user['points'] - $points_to_use + $total_points;
            $new_total_orders = $user['total_orders'] + 1;
            $new_badge = calculateBadge($new_total_orders);
            
            $stmt = $pdo->prepare("UPDATE users SET points = ?, total_orders = ?, badge = ? WHERE id = ?");
            $stmt->execute([$new_points, $new_total_orders, $new_badge, $user_id]);
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            // Redirect to payment
            $_SESSION['order_id'] = $order_id;
            $_SESSION['final_amount'] = $final_amount;
            header('Location: payment.php');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create order. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="checkout-page">
            <h1><i class="fas fa-credit-card"></i> Checkout</h1>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="checkout-form">
                <div class="checkout-content">
                    <!-- Shipping Address -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-map-marker-alt"></i> Shipping Address</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" required 
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : htmlspecialchars($user['username']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="mobile">Mobile Number *</label>
                                <input type="tel" id="mobile" name="mobile" required 
                                       value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : htmlspecialchars($user['mobile']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address *</label>
                            <textarea id="address" name="address" rows="3" required placeholder="House No, Street, Area"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" required 
                                       value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="state">State *</label>
                                <input type="text" id="state" name="state" required 
                                       value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="pincode">Pincode *</label>
                                <input type="text" id="pincode" name="pincode" required pattern="[0-9]{6}" 
                                       value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Points Redemption -->
                    <?php if ($user['points'] >= 100): ?>
                    <div class="checkout-section">
                        <h2><i class="fas fa-coins"></i> Redeem Points</h2>
                        
                        <div class="points-info">
                            <p>Available Points: <strong><?php echo $user['points']; ?></strong></p>
                            <p>Points Value: <strong><?php echo formatCurrency($user['points'] / 2); ?></strong> (2 points = ₹1)</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="points_to_use">Points to Use (Min: 100)</label>
                            <input type="number" id="points_to_use" name="points_to_use" 
                                   min="0" max="<?php echo $user['points']; ?>" step="10" 
                                   value="<?php echo isset($_POST['points_to_use']) ? $_POST['points_to_use'] : '0'; ?>"
                                   onchange="calculateTotal()">
                            <small>Enter points in multiples of 10</small>
                        </div>
                        
                        <div class="quick-points">
                            <button type="button" onclick="setPoints(100)">Use 100</button>
                            <button type="button" onclick="setPoints(<?php echo min(500, $user['points']); ?>)">Use 500</button>
                            <button type="button" onclick="setPoints(<?php echo min(1000, $user['points']); ?>)">Use 1000</button>
                            <button type="button" onclick="setPoints(<?php echo $user['points']; ?>)">Use All</button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Order Summary -->
                    <div class="checkout-section">
                        <h2><i class="fas fa-receipt"></i> Order Summary</h2>
                        
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <img src="assets/images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                                    <p>Price: <?php echo formatCurrency($item['discount_price'] ?: $item['price']); ?></p>
                                </div>
                                <div class="item-total">
                                    <?php echo formatCurrency(($item['discount_price'] ?: $item['price']) * $item['quantity']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span id="subtotal"><?php echo formatCurrency($subtotal); ?></span>
                            </div>
                            
                            <div class="total-row">
                                <span>Shipping:</span>
                                <span><?php echo $shipping > 0 ? formatCurrency($shipping) : 'FREE'; ?></span>
                            </div>
                            
                            <div class="total-row" id="points-discount-row" style="display: none;">
                                <span>Points Discount:</span>
                                <span id="points-discount">-₹0.00</span>
                            </div>
                            
                            <div class="total-row final-total">
                                <span><strong>Final Total:</strong></span>
                                <span><strong id="final-total"><?php echo formatCurrency($total); ?></strong></span>
                            </div>
                            
                            <div class="points-earn">
                                <i class="fas fa-coins"></i>
                                You'll earn <strong><?php echo $total_points; ?> points</strong> on this order
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-actions">
                    <a href="cart.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-credit-card"></i> Place Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        const subtotal = <?php echo $subtotal; ?>;
        const shipping = <?php echo $shipping; ?>;
        const originalTotal = <?php echo $total; ?>;
        
        function calculateTotal() {
            const pointsToUse = parseInt(document.getElementById('points_to_use').value) || 0;
            const pointsValue = pointsToUse / 2;
            const finalTotal = Math.max(0, originalTotal - pointsValue);
            
            document.getElementById('final-total').textContent = '₹' + finalTotal.toFixed(2);
            
            const discountRow = document.getElementById('points-discount-row');
            const discountAmount = document.getElementById('points-discount');
            
            if (pointsToUse > 0) {
                discountRow.style.display = 'flex';
                discountAmount.textContent = '-₹' + pointsValue.toFixed(2);
            } else {
                discountRow.style.display = 'none';
            }
        }
        
        function setPoints(points) {
            const maxPoints = <?php echo $user['points']; ?>;
            const actualPoints = Math.min(points, maxPoints);
            document.getElementById('points_to_use').value = actualPoints;
            calculateTotal();
        }
        
        // Validate pincode
        document.getElementById('pincode').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
        });
        
        // Validate mobile
        document.getElementById('mobile').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
        });
    </script>
</body>
</html>