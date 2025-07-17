<?php
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $points = (int)$_POST['points'];
        $badge = $_POST['badge'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE users SET points = ?, badge = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$points, $badge, $status, $user_id])) {
            $success = 'User updated successfully';
        } else {
            $error = 'Failed to update user';
        }
    }
    
    if (isset($_POST['approve_redemption'])) {
        $redemption_id = (int)$_POST['redemption_id'];
        $stmt = $pdo->prepare("UPDATE points_redemption SET status = 'approved' WHERE id = ?");
        if ($stmt->execute([$redemption_id])) {
            $success = 'Redemption approved successfully';
        } else {
            $error = 'Failed to approve redemption';
        }
    }
    
    if (isset($_POST['reject_redemption'])) {
        $redemption_id = (int)$_POST['redemption_id'];
        $stmt = $pdo->prepare("UPDATE points_redemption SET status = 'rejected' WHERE id = ?");
        if ($stmt->execute([$redemption_id])) {
            $success = 'Redemption rejected successfully';
        } else {
            $error = 'Failed to reject redemption';
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $user_id = (int)$_POST['user_id'];
        $new_password = password_hash('123456', PASSWORD_DEFAULT); // Default password
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$new_password, $user_id])) {
            $success = 'Password reset to "123456" successfully';
        } else {
            $error = 'Failed to reset password';
        }
    }
}

// Get users with statistics
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(o.id) as total_orders,
           COALESCE(SUM(o.final_amount), 0) as total_spent
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    GROUP BY u.id 
    ORDER BY u.points DESC, u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

// Get pending redemptions
$stmt = $pdo->prepare("
    SELECT pr.*, u.username 
    FROM points_redemption pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.status = 'pending' 
    ORDER BY pr.created_at DESC
");
$stmt->execute();
$pending_redemptions = $stmt->fetchAll();

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 1rem; }
        .admin-content { flex: 1; padding: 2rem; background: #f8f9fa; }
        .users-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .badge-silver { background: #95a5a6; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; }
        .badge-gold { background: #f39c12; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; }
        .badge-platinum { background: #9b59b6; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; }
        .badge-elite { background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.9rem; margin: 0 0.25rem; }
        .edit-form { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .redemption-card { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="admin-content">
                <h1><i class="fas fa-users"></i> Manage Users & Badges</h1>
                
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
                
                <!-- Pending Redemptions -->
                <?php if (!empty($pending_redemptions)): ?>
                <div class="users-section">
                    <h2><i class="fas fa-coins"></i> Pending Point Redemptions</h2>
                    
                    <?php foreach ($pending_redemptions as $redemption): ?>
                    <div class="redemption-card">
                        <div>
                            <strong><?php echo htmlspecialchars($redemption['username']); ?></strong><br>
                            <span>Points: <?php echo $redemption['points_redeemed']; ?></span> | 
                            <span>Value: <?php echo formatCurrency($redemption['cash_value']); ?></span><br>
                            <small>Requested: <?php echo date('M d, Y h:i A', strtotime($redemption['created_at'])); ?></small>
                        </div>
                        <div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="redemption_id" value="<?php echo $redemption['id']; ?>">
                                <button type="submit" name="approve_redemption" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="redemption_id" value="<?php echo $redemption['id']; ?>">
                                <button type="submit" name="reject_redemption" class="btn btn-sm btn-danger">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Edit User Form -->
                <?php if ($edit_user): ?>
                <div class="users-section">
                    <h2>Edit User: <?php echo htmlspecialchars($edit_user['username']); ?></h2>
                    
                    <form method="POST" class="edit-form">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="points">Points</label>
                                <input type="number" id="points" name="points" min="0" 
                                       value="<?php echo $edit_user['points']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="badge">Badge</label>
                                <select id="badge" name="badge">
                                    <option value="SILVER" <?php echo $edit_user['badge'] == 'SILVER' ? 'selected' : ''; ?>>Silver</option>
                                    <option value="GOLD" <?php echo $edit_user['badge'] == 'GOLD' ? 'selected' : ''; ?>>Gold</option>
                                    <option value="PLATINUM" <?php echo $edit_user['badge'] == 'PLATINUM' ? 'selected' : ''; ?>>Platinum</option>
                                    <option value="ELITE" <?php echo $edit_user['badge'] == 'ELITE' ? 'selected' : ''; ?>>Elite</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="active" <?php echo $edit_user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $edit_user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_user" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
                            </button>
                            
                            <button type="submit" name="reset_password" class="btn btn-warning" 
                                    onclick="return confirm('Reset password to 123456?')">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                            
                            <a href="users.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Users Table -->
                <div class="users-section">
                    <h2>All Users</h2>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Badge</th>
                                <th>Points</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($user['email']); ?></small><br>
                                    <small><?php echo htmlspecialchars($user['mobile']); ?></small>
                                    <?php if ($user['referral_code']): ?>
                                    <br><small>Ref: <?php echo $user['referral_code']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-<?php echo strtolower($user['badge']); ?>">
                                        <?php echo $user['badge']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo $user['points']; ?></strong></td>
                                <td><?php echo $user['total_orders']; ?></td>
                                <td><?php echo formatCurrency($user['total_spent']); ?></td>
                                <td>
                                    <span class="status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>