<?php
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['order_status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $order_id])) {
        $success = 'Order status updated successfully';
    } else {
        $error = 'Failed to update order status';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "o.order_status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get orders
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.mobile 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(final_amount) as total_revenue
    FROM orders
");
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 1rem; }
        .admin-content { flex: 1; padding: 2rem; background: #f8f9fa; }
        .orders-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { margin: 0 0 0.5rem 0; font-size: 2rem; color: #3498db; }
        .filters { display: flex; gap: 1rem; margin-bottom: 2rem; align-items: center; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .status-pending { color: #ffc107; }
        .status-confirmed { color: #17a2b8; }
        .status-shipped { color: #6f42c1; }
        .status-delivered { color: #28a745; }
        .status-cancelled { color: #dc3545; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.9rem; }
        .order-details { background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 0.5rem 0; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="admin-content">
                <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>
                
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
                
                <!-- Order Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['pending_orders']; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['confirmed_orders']; ?></h3>
                        <p>Confirmed Orders</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['shipped_orders']; ?></h3>
                        <p>Shipped Orders</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['delivered_orders']; ?></h3>
                        <p>Delivered Orders</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                        <div>
                            <label for="status">Filter by Status:</label>
                            <select name="status" id="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date">Filter by Date:</label>
                            <input type="date" name="date" id="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                        </div>
                        
                        <a href="orders.php" class="btn btn-outline">Clear Filters</a>
                    </form>
                </div>
                
                <!-- Orders Table -->
                <div class="orders-section">
                    <h2>Orders (<?php echo count($orders); ?>)</h2>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $order['order_number']; ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($order['email']); ?></small><br>
                                    <small><?php echo htmlspecialchars($order['mobile']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo formatCurrency($order['final_amount']); ?></strong><br>
                                    <?php if ($order['points_used'] > 0): ?>
                                    <small>Points Used: <?php echo $order['points_used']; ?></small><br>
                                    <?php endif; ?>
                                    <small>Points Earned: <?php echo $order['points_earned']; ?></small>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="order_status" onchange="this.form.submit()" class="status-<?php echo $order['order_status']; ?>">
                                            <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $order['order_status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <span class="status-<?php echo $order['payment_status']; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="toggleOrderDetails(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                            <tr id="details-<?php echo $order['id']; ?>" style="display: none;">
                                <td colspan="7">
                                    <div class="order-details">
                                        <h4>Shipping Address:</h4>
                                        <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                        
                                        <h4>Order Items:</h4>
                                        <?php
                                        $stmt = $pdo->prepare("
                                            SELECT oi.*, p.name, p.image 
                                            FROM order_items oi 
                                            JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?
                                        ");
                                        $stmt->execute([$order['id']]);
                                        $order_items = $stmt->fetchAll();
                                        ?>
                                        
                                        <table style="width: 100%; margin-top: 1rem;">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <img src="../assets/images/<?php echo $item['image']; ?>" 
                                                             style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px; vertical-align: middle;">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><?php echo formatCurrency($item['price']); ?></td>
                                                    <td><?php echo formatCurrency($item['total']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleOrderDetails(orderId) {
            const detailsRow = document.getElementById('details-' + orderId);
            if (detailsRow.style.display === 'none') {
                detailsRow.style.display = 'table-row';
            } else {
                detailsRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>