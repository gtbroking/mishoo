<?php
require_once 'config/functions.php';

if (!isLoggedIn() || !isset($_GET['order'])) {
    header('Location: index.php');
    exit;
}

$order_number = $_GET['order'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$order_items = $stmt->fetchAll();

$whatsapp_number = getSetting('whatsapp_number');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="order-success-page">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your purchase. Your order has been successfully placed.</p>
            </div>

            <div class="order-details-card">
                <h2>Order Details</h2>
                
                <div class="order-info">
                    <div class="info-row">
                        <span>Order Number:</span>
                        <span><strong><?php echo $order['order_number']; ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span>Order Date:</span>
                        <span><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Payment Status:</span>
                        <span class="status <?php echo $order['payment_status']; ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span>Order Status:</span>
                        <span class="status <?php echo $order['order_status']; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                </div>

                <div class="order-items">
                    <h3>Items Ordered</h3>
                    <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <img src="assets/images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                            <p>Price: <?php echo formatCurrency($item['price']); ?></p>
                        </div>
                        <div class="item-total">
                            <?php echo formatCurrency($item['total']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span><?php echo formatCurrency($order['total_amount'] - ($order['total_amount'] > 500 ? 0 : 50)); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?php echo $order['total_amount'] > 500 ? 'FREE' : formatCurrency(50); ?></span>
                    </div>
                    <?php if ($order['points_used'] > 0): ?>
                    <div class="summary-row">
                        <span>Points Used:</span>
                        <span>-<?php echo formatCurrency($order['points_used'] / 2); ?> (<?php echo $order['points_used']; ?> points)</span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total-row">
                        <span><strong>Total Paid:</strong></span>
                        <span><strong><?php echo formatCurrency($order['final_amount']); ?></strong></span>
                    </div>
                </div>

                <div class="points-earned">
                    <i class="fas fa-coins"></i>
                    <span>You earned <strong><?php echo $order['points_earned']; ?> points</strong> from this order!</span>
                </div>
            </div>

            <div class="shipping-address">
                <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                <div class="address-details">
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </div>
            </div>

            <div class="next-steps">
                <h3>What's Next?</h3>
                <div class="steps-list">
                    <div class="step">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h4>Order Confirmed</h4>
                            <p>Your order has been received and is being processed</p>
                        </div>
                    </div>
                    <div class="step">
                        <i class="fas fa-box"></i>
                        <div>
                            <h4>Preparing for Shipment</h4>
                            <p>We'll pack your items and prepare them for shipping</p>
                        </div>
                    </div>
                    <div class="step">
                        <i class="fas fa-truck"></i>
                        <div>
                            <h4>Shipped</h4>
                            <p>Your order will be shipped and you'll receive tracking details</p>
                        </div>
                    </div>
                    <div class="step">
                        <i class="fas fa-home"></i>
                        <div>
                            <h4>Delivered</h4>
                            <p>Your order will be delivered to your address</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="orders.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> View All Orders
                </a>
                
                <a href="products.php" class="btn btn-outline">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                
                <a href="https://wa.me/<?php echo $whatsapp_number; ?>?text=Hi, I have a question about my order <?php echo $order['order_number']; ?>" 
                   target="_blank" class="btn btn-success">
                    <i class="fab fa-whatsapp"></i> WhatsApp Support
                </a>
            </div>

            <div class="order-tracking-info">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <h4>Order Tracking</h4>
                        <p>You can track your order status anytime by visiting your <a href="orders.php">Orders page</a> or contacting our support team.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        // Confetti animation for success
        function createConfetti() {
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    top: -10px;
                    left: ${Math.random() * 100}vw;
                    z-index: 10000;
                    pointer-events: none;
                    border-radius: 50%;
                `;
                
                document.body.appendChild(confetti);
                
                confetti.animate([
                    { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(100vh) rotate(720deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 2000 + 1000,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                }).onfinish = () => confetti.remove();
            }
        }
        
        // Trigger confetti on page load
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>