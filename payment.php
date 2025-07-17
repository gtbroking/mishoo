<?php
require_once 'config/functions.php';

if (!isLoggedIn() || !isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['order_id'];
$final_amount = $_SESSION['final_amount'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get UPI ID from settings
$upi_id = getSetting('upi_id');
$whatsapp_number = getSetting('whatsapp_number');

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $transaction_id = trim($_POST['transaction_id']);
    
    if (empty($transaction_id)) {
        $error = 'Please enter transaction ID';
    } else {
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', order_status = 'confirmed' WHERE id = ?");
        if ($stmt->execute([$order_id])) {
            // Clear session
            unset($_SESSION['order_id']);
            unset($_SESSION['final_amount']);
            
            // Redirect to success page
            header('Location: order_success.php?order=' . $order['order_number']);
            exit;
        } else {
            $error = 'Failed to confirm payment. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="payment-page">
            <h1><i class="fas fa-credit-card"></i> Payment</h1>
            
            <div class="payment-container">
                <!-- Order Summary -->
                <div class="payment-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-details">
                        <div class="summary-row">
                            <span>Order Number:</span>
                            <span><strong><?php echo $order['order_number']; ?></strong></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Amount:</span>
                            <span><?php echo formatCurrency($order['total_amount']); ?></span>
                        </div>
                        <?php if ($order['points_used'] > 0): ?>
                        <div class="summary-row">
                            <span>Points Used:</span>
                            <span><?php echo $order['points_used']; ?> points (-<?php echo formatCurrency($order['points_used'] / 2); ?>)</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-row final-amount">
                            <span><strong>Amount to Pay:</strong></span>
                            <span><strong><?php echo formatCurrency($final_amount); ?></strong></span>
                        </div>
                        <div class="summary-row">
                            <span>Points to Earn:</span>
                            <span><strong><?php echo $order['points_earned']; ?> points</strong></span>
                        </div>
                    </div>
                </div>

                <?php if ($final_amount > 0): ?>
                <!-- UPI Payment -->
                <div class="payment-method">
                    <h2><i class="fas fa-mobile-alt"></i> UPI Payment</h2>
                    
                    <div class="upi-details">
                        <div class="upi-info">
                            <h3>Pay using UPI</h3>
                            <p><strong>UPI ID:</strong> <?php echo $upi_id; ?></p>
                            <p><strong>Amount:</strong> ₹<?php echo number_format($final_amount, 2); ?></p>
                        </div>
                        
                        <div class="upi-actions">
                            <a href="upi://pay?pa=<?php echo $upi_id; ?>&am=<?php echo $final_amount; ?>&cu=INR&tn=Order%20<?php echo $order['order_number']; ?>" 
                               class="btn btn-primary btn-large upi-btn">
                                <i class="fas fa-mobile-alt"></i> Pay with UPI App
                            </a>
                            
                            <div class="upi-alternatives">
                                <p>Or scan QR code / Copy UPI ID to pay manually</p>
                                <div class="upi-copy">
                                    <input type="text" value="<?php echo $upi_id; ?>" readonly id="upiId">
                                    <button onclick="copyUpiId()" class="btn btn-outline">Copy UPI ID</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Confirmation -->
                <div class="payment-confirmation">
                    <h2><i class="fas fa-check-circle"></i> Confirm Payment</h2>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="confirmation-form">
                        <div class="form-group">
                            <label for="transaction_id">Transaction ID / Reference Number *</label>
                            <input type="text" id="transaction_id" name="transaction_id" required 
                                   placeholder="Enter UPI transaction ID">
                            <small>Enter the transaction ID you received after payment</small>
                        </div>
                        
                        <button type="submit" name="confirm_payment" class="btn btn-success btn-large">
                            <i class="fas fa-check"></i> Confirm Payment
                        </button>
                    </form>
                </div>
                
                <?php else: ?>
                <!-- Free Order -->
                <div class="free-order">
                    <div class="free-order-info">
                        <i class="fas fa-gift"></i>
                        <h2>Congratulations!</h2>
                        <p>Your order is completely covered by points redemption.</p>
                        <p>No payment required!</p>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="transaction_id" value="POINTS_REDEEMED">
                        <button type="submit" name="confirm_payment" class="btn btn-success btn-large">
                            <i class="fas fa-check"></i> Confirm Order
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Help Section -->
                <div class="payment-help">
                    <h3><i class="fas fa-question-circle"></i> Need Help?</h3>
                    <p>Having trouble with payment? Contact us for assistance.</p>
                    
                    <a href="https://wa.me/<?php echo $whatsapp_number; ?>?text=Hi, I need help with payment for order <?php echo $order['order_number']; ?>" 
                       target="_blank" class="btn btn-outline whatsapp-help-btn">
                        <i class="fab fa-whatsapp"></i> WhatsApp Support
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function copyUpiId() {
            const upiInput = document.getElementById('upiId');
            upiInput.select();
            document.execCommand('copy');
            showNotification('UPI ID copied to clipboard!', 'success');
        }
        
        // Auto-focus on transaction ID after UPI payment attempt
        document.querySelector('.upi-btn').addEventListener('click', function() {
            setTimeout(() => {
                document.getElementById('transaction_id').focus();
            }, 2000);
        });
        
        // Validate transaction ID
        document.getElementById('transaction_id').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    </script>
</body>
</html>