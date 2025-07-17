<?php
require_once '../config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    $order_id = (int)($input['order_id'] ?? 0);
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid order']);
        exit;
    }
    
    // Verify order belongs to user
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    switch ($action) {
        case 'cancel':
            // Check if order can be cancelled
            if (!in_array($order['order_status'], ['pending', 'confirmed'])) {
                echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
                exit;
            }
            
            try {
                $pdo->beginTransaction();
                
                // Update order status
                $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
                $stmt->execute([$order_id]);
                
                // Restore stock quantities
                $stmt = $pdo->prepare("
                    SELECT oi.product_id, oi.quantity 
                    FROM order_items oi 
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order_id]);
                $order_items = $stmt->fetchAll();
                
                foreach ($order_items as $item) {
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                // Restore user points if used
                if ($order['points_used'] > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                    $stmt->execute([$order['points_used'], $user_id]);
                }
                
                // Remove earned points
                if ($order['points_earned'] > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET points = GREATEST(0, points - ?) WHERE id = ?");
                    $stmt->execute([$order['points_earned'], $user_id]);
                }
                
                $pdo->commit();
                
                echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>