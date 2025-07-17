<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getUserData($_SESSION['user_id']);
$error = '';
$success = '';

// Handle points redemption request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_points'])) {
    $points_to_redeem = (int)$_POST['points_to_redeem'];
    $min_redeem = (int)getSetting('min_redeem_points');
    
    if ($points_to_redeem < $min_redeem) {
        $error = "Minimum redemption is $min_redeem points";
    } elseif ($points_to_redeem > $user['points']) {
        $error = "Insufficient points";
    } else {
        $cash_value = $points_to_redeem / 2; // 2 points = 1 rupee
        
        $stmt = $pdo->prepare("INSERT INTO points_redemption (user_id, points_redeemed, cash_value) VALUES (?, ?, ?)");
        if ($stmt->execute([$user['id'], $points_to_redeem, $cash_value])) {
            $success = "Redemption request submitted successfully! Admin will review it.";
        } else {
            $error = "Failed to submit redemption request";
        }
    }
}

// Get user's orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

// Get redemption history
$stmt = $pdo->prepare("SELECT * FROM points_redemption WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$redemptions = $stmt->fetchAll();

// Generate invite link for eligible users
$invite_link = '';
if (in_array($user['badge'], ['GOLD', 'PLATINUM', 'ELITE'])) {
    $invite_link = 'http://' . $_SERVER['HTTP_HOST'] . '/register.php?ref=' . $user['referral_code'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-info">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <p><?php echo htmlspecialchars($user['mobile']); ?></p>
                    </div>
                </div>
                <div class="user-stats">
                    <div class="stat-card">
                        <div class="badge-display <?php echo strtolower($user['badge']); ?>">
                            <?php echo $user['badge']; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $user['points']; ?></h3>
                        <p>Points</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $user['total_orders']; ?></h3>
                        <p>Orders</p>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <div class="profile-content">
                <!-- Points & Redemption -->
                <div class="profile-section">
                    <h3><i class="fas fa-coins"></i> Points & Rewards</h3>
                    <div class="points-info">
                        <p>Current Points: <strong><?php echo $user['points']; ?></strong></p>
                        <p>Points Value: <strong><?php echo formatCurrency($user['points'] / 2); ?></strong> (2 points = ₹1)</p>
                        <p>Badge Benefits: <strong><?php echo getPointsPerProduct($user['badge']); ?> points per product</strong></p>
                    </div>
                    
                    <?php if ($user['points'] >= getSetting('min_redeem_points')): ?>
                    <form method="POST" class="redeem-form">
                        <div class="form-group">
                            <label for="points_to_redeem">Redeem Points (Min: <?php echo getSetting('min_redeem_points'); ?>)</label>
                            <input type="number" id="points_to_redeem" name="points_to_redeem" 
                                   min="<?php echo getSetting('min_redeem_points'); ?>" 
                                   max="<?php echo $user['points']; ?>" required>
                        </div>
                        <button type="submit" name="redeem_points" class="btn btn-primary">
                            <i class="fas fa-money-bill"></i> Request Redemption
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Referral System -->
                <?php if ($invite_link): ?>
                <div class="profile-section">
                    <h3><i class="fas fa-share-alt"></i> Invite Friends</h3>
                    <p>Share your referral code and earn 10 points for each successful signup!</p>
                    <div class="referral-info">
                        <p>Your Referral Code: <strong><?php echo $user['referral_code']; ?></strong></p>
                        <div class="invite-link">
                            <input type="text" value="<?php echo $invite_link; ?>" readonly id="inviteLink">
                            <button onclick="copyInviteLink()" class="btn btn-secondary">Copy Link</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Orders -->
                <div class="profile-section">
                    <h3><i class="fas fa-box"></i> Recent Orders</h3>
                    <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <h4>Order #<?php echo $order['order_number']; ?></h4>
                                <span class="order-status <?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="order-details">
                                <p>Amount: <?php echo formatCurrency($order['final_amount']); ?></p>
                                <p>Points Earned: <?php echo $order['points_earned']; ?></p>
                                <p>Date: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="orders.php" class="btn btn-outline">View All Orders</a>
                    <?php else: ?>
                    <p>No orders yet. <a href="products.php">Start shopping!</a></p>
                    <?php endif; ?>
                </div>

                <!-- Redemption History -->
                <?php if (!empty($redemptions)): ?>
                <div class="profile-section">
                    <h3><i class="fas fa-history"></i> Redemption History</h3>
                    <div class="redemptions-list">
                        <?php foreach ($redemptions as $redemption): ?>
                        <div class="redemption-card">
                            <div class="redemption-info">
                                <p>Points: <?php echo $redemption['points_redeemed']; ?></p>
                                <p>Value: <?php echo formatCurrency($redemption['cash_value']); ?></p>
                                <p>Date: <?php echo date('M d, Y', strtotime($redemption['created_at'])); ?></p>
                            </div>
                            <span class="redemption-status <?php echo $redemption['status']; ?>">
                                <?php echo ucfirst($redemption['status']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function copyInviteLink() {
            const inviteLink = document.getElementById('inviteLink');
            inviteLink.select();
            document.execCommand('copy');
            alert('Invite link copied to clipboard!');
        }
    </script>
</body>
</html>