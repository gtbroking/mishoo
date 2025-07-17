<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's orders
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="orders-page">
            <h1><i class="fas fa-box"></i> My Orders</h1>
            
            <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-box-open"></i>
                <h2>No orders yet</h2>
                <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Start Shopping
                </a>
            </div>
            <?php else: ?>
            
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-number">
                            <h3>Order #<?php echo $order['order_number']; ?></h3>
                            <p>Placed on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="order-status">
                            <span class="status-badge <?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                            <span class="payment-status <?php echo $order['payment_status']; ?>">
                                Payment: <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-summary">
                            <div class="summary-item">
                                <span>Total Amount:</span>
                                <span><?php echo formatCurrency($order['total_amount']); ?></span>
                            </div>
                            <?php if ($order['points_used'] > 0): ?>
                            <div class="summary-item">
                                <span>Points Used:</span>
                                <span><?php echo $order['points_used']; ?> points</span>
                            </div>
                            <?php endif; ?>
                            <div class="summary-item">
                                <span>Final Amount:</span>
                                <span><strong><?php echo formatCurrency($order['final_amount']); ?></strong></span>
                            </div>
                            <div class="summary-item">
                                <span>Points Earned:</span>
                                <span class="points-earned"><?php echo $order['points_earned']; ?> points</span>
                            </div>
                        </div>
                        
                        <div class="shipping-info">
                            <h4><i class="fas fa-map-marker-alt"></i> Shipping Address</h4>
                            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        
                        <?php if ($order['order_status'] === 'delivered'): ?>
                        <a href="order_details.php?id=<?php echo $order['id']; ?>#reviews" class="btn btn-secondary">
                            <i class="fas fa-star"></i> Rate Products
                        </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['order_status'], ['pending', 'confirmed'])): ?>
                        <button class="btn btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                        <?php endif; ?>
                        
                        <a href="https://wa.me/<?php echo getSetting('whatsapp_number'); ?>?text=Hi, I have a question about my order <?php echo $order['order_number']; ?>" 
                           target="_blank" class="btn btn-success">
                            <i class="fab fa-whatsapp"></i> Support
                        </a>
                    </div>
                    
                    <!-- Order Progress -->
                    <div class="order-progress">
                        <div class="progress-steps">
                            <div class="step <?php echo in_array($order['order_status'], ['pending', 'confirmed', 'shipped', 'delivered']) ? 'completed' : ''; ?>">
                                <i class="fas fa-check-circle"></i>
                                <span>Confirmed</span>
                            </div>
                            <div class="step <?php echo in_array($order['order_status'], ['shipped', 'delivered']) ? 'completed' : ''; ?>">
                                <i class="fas fa-box"></i>
                                <span>Shipped</span>
                            </div>
                            <div class="step <?php echo $order['order_status'] === 'delivered' ? 'completed' : ''; ?>">
                                <i class="fas fa-home"></i>
                                <span>Delivered</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('api/order_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'cancel',
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Order cancelled successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message || 'Failed to cancel order', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            }
        }
    </script>
</body>
</html>