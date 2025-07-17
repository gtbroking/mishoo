<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $cart_id => $quantity) {
            $quantity = max(1, (int)$quantity);
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
        }
        header('Location: cart.php');
        exit;
    }
    
    if (isset($_POST['remove_item'])) {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        header('Location: cart.php');
        exit;
    }
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock_quantity, p.points_reward
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
$total_points = 0;
foreach ($cart_items as $item) {
    $price = $item['discount_price'] ?: $item['price'];
    $subtotal += $price * $item['quantity'];
    $total_points += $item['points_reward'] * $item['quantity'];
}

$shipping = $subtotal > 500 ? 0 : 50; // Free shipping above ₹500
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="cart-page">
            <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
            
            <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Add some products to your cart to see them here</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
            <?php else: ?>
            
            <form method="POST" class="cart-form">
                <div class="cart-content">
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="assets/images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="item-price">
                                    <?php if ($item['discount_price']): ?>
                                    <span class="current-price"><?php echo formatCurrency($item['discount_price']); ?></span>
                                    <span class="original-price"><?php echo formatCurrency($item['price']); ?></span>
                                    <?php else: ?>
                                    <span class="current-price"><?php echo formatCurrency($item['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-points">
                                    <i class="fas fa-coins"></i> <?php echo $item['points_reward']; ?> points per item
                                </div>
                                <div class="stock-status">
                                    <?php if ($item['stock_quantity'] > 0): ?>
                                    <span class="in-stock">In Stock</span>
                                    <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="item-quantity">
                                <label>Quantity:</label>
                                <div class="quantity-control">
                                    <button type="button" onclick="changeQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" name="quantities[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock_quantity']; ?>"
                                           id="qty_<?php echo $item['id']; ?>">
                                    <button type="button" onclick="changeQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                </div>
                            </div>
                            
                            <div class="item-total">
                                <strong>
                                    <?php 
                                    $price = $item['discount_price'] ?: $item['price'];
                                    echo formatCurrency($price * $item['quantity']); 
                                    ?>
                                </strong>
                            </div>
                            
                            <div class="item-actions">
                                <button type="submit" name="remove_item" value="1" class="btn-remove" 
                                        onclick="return confirm('Remove this item from cart?')">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="summary-card">
                            <h3>Order Summary</h3>
                            
                            <div class="summary-row">
                                <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                                <span><?php echo formatCurrency($subtotal); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?php echo $shipping > 0 ? formatCurrency($shipping) : 'FREE'; ?></span>
                            </div>
                            
                            <?php if ($shipping > 0): ?>
                            <div class="shipping-note">
                                <i class="fas fa-info-circle"></i>
                                Add <?php echo formatCurrency(500 - $subtotal); ?> more for free shipping
                            </div>
                            <?php endif; ?>
                            
                            <div class="summary-row total-row">
                                <span><strong>Total</strong></span>
                                <span><strong><?php echo formatCurrency($total); ?></strong></span>
                            </div>
                            
                            <div class="points-earn">
                                <i class="fas fa-coins"></i>
                                You'll earn <strong><?php echo $total_points; ?> points</strong> on this order
                            </div>
                            
                            <div class="cart-actions">
                                <button type="submit" name="update_cart" class="btn btn-outline">
                                    <i class="fas fa-sync"></i> Update Cart
                                </button>
                                
                                <a href="checkout.php" class="btn btn-primary btn-large">
                                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                                </a>
                            </div>
                            
                            <div class="continue-shopping">
                                <a href="products.php">
                                    <i class="fas fa-arrow-left"></i> Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function changeQuantity(cartId, change) {
            const input = document.getElementById('qty_' + cartId);
            const currentValue = parseInt(input.value);
            const newValue = currentValue + change;
            const max = parseInt(input.max);
            
            if (newValue >= 1 && newValue <= max) {
                input.value = newValue;
            }
        }
        
        // Auto-update cart when quantity changes
        document.querySelectorAll('input[name^="quantities"]').forEach(input => {
            input.addEventListener('change', function() {
                // Optional: Auto-submit form on quantity change
                // this.form.submit();
            });
        });
    </script>
</body>
</html>