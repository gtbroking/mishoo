<?php
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stats['total_users'] = $stmt->fetch()['count'];

// Total products
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$stats['total_products'] = $stmt->fetch()['count'];

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $stmt->fetch()['count'];

// Total revenue
$stmt = $pdo->query("SELECT SUM(final_amount) as total FROM orders WHERE payment_status = 'completed'");
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Badge distribution
$stmt = $pdo->query("SELECT badge, COUNT(*) as count FROM users GROUP BY badge");
$badge_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Recent orders
$stmt = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
$recent_orders = $stmt->fetchAll();

// Pending redemptions
$stmt = $pdo->query("SELECT pr.*, u.username FROM points_redemption pr JOIN users u ON pr.user_id = u.id WHERE pr.status = 'pending' ORDER BY pr.created_at DESC");
$pending_redemptions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Shopping Website</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 1rem;
        }
        .admin-content {
            flex: 1;
            padding: 2rem;
            background: #f8f9fa;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            color: #3498db;
        }
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
        }
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        .sidebar-nav a {
            color: white;
            text-decoration: none;
            padding: 0.75rem;
            display: block;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .sidebar-nav a:hover {
            background: #34495e;
        }
        .sidebar-nav a.active {
            background: #3498db;
        }
        .admin-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }
        .badge-card {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            color: white;
        }
        .badge-card.silver { background: #95a5a6; }
        .badge-card.gold { background: #f39c12; }
        .badge-card.platinum { background: #9b59b6; }
        .badge-card.elite { background: #e74c3c; }
        .recent-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="admin-content">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p><i class="fas fa-users"></i> Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['total_products']; ?></h3>
                        <p><i class="fas fa-box"></i> Total Products</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p><i class="fas fa-shopping-cart"></i> Total Orders</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                        <p><i class="fas fa-rupee-sign"></i> Total Revenue</p>
                    </div>
                </div>
                
                <!-- Badge Statistics -->
                <div class="recent-section">
                    <h3>User Badge Distribution</h3>
                    <div class="badge-stats">
                        <div class="badge-card silver">
                            <h3><?php echo $badge_stats['SILVER'] ?? 0; ?></h3>
                            <p>Silver Users</p>
                        </div>
                        <div class="badge-card gold">
                            <h3><?php echo $badge_stats['GOLD'] ?? 0; ?></h3>
                            <p>Gold Users</p>
                        </div>
                        <div class="badge-card platinum">
                            <h3><?php echo $badge_stats['PLATINUM'] ?? 0; ?></h3>
                            <p>Platinum Users</p>
                        </div>
                        <div class="badge-card elite">
                            <h3><?php echo $badge_stats['ELITE'] ?? 0; ?></h3>
                            <p>Elite Users</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="recent-section">
                    <h3>Recent Orders</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo formatCurrency($order['final_amount']); ?></td>
                                <td><span class="status <?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pending Redemptions -->
                <?php if (!empty($pending_redemptions)): ?>
                <div class="recent-section">
                    <h3>Pending Point Redemptions</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Points</th>
                                <th>Cash Value</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_redemptions as $redemption): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($redemption['username']); ?></td>
                                <td><?php echo $redemption['points_redeemed']; ?></td>
                                <td><?php echo formatCurrency($redemption['cash_value']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($redemption['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?action=approve_redemption&id=<?php echo $redemption['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="users.php?action=reject_redemption&id=<?php echo $redemption['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>