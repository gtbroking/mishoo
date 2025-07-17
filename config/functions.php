<?php
require_once 'db_connect.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Get user data
function getUserData($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Get admin data
function getAdminData($admin_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->execute([$admin_id]);
    return $stmt->fetch();
}

// Generate referral code
function generateReferralCode($username) {
    return strtoupper(substr($username, 0, 3) . rand(100, 999));
}

// Calculate badge based on total orders
function calculateBadge($total_orders) {
    if ($total_orders >= 50) return 'ELITE';
    if ($total_orders >= 30) return 'PLATINUM';
    if ($total_orders >= 15) return 'GOLD';
    return 'SILVER';
}

// Get points per product based on badge
function getPointsPerProduct($badge) {
    switch($badge) {
        case 'ELITE': return 20;
        case 'PLATINUM': return 10;
        case 'GOLD': return 5;
        case 'SILVER': return 3;
        default: return 3;
    }
}

// Get cart count
function getCartCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Get wishlist count
function getWishlistCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Get setting value
function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result['setting_value'] ?? '';
}

// Update setting
function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
}

// Send notification (placeholder for future implementation)
function sendNotification($user_id, $message) {
    // Implementation for notifications
    return true;
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate mobile
function isValidMobile($mobile) {
    return preg_match('/^[6-9]\d{9}$/', $mobile);
}

// Generate order number
function generateOrderNumber() {
    return 'ORD' . date('Ymd') . rand(1000, 9999);
}

// Get product rating
function getProductRating($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM product_ratings WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch();
}

// Check if product is in wishlist
function isInWishlist($user_id, $product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    return $stmt->fetch() ? true : false;
}

// Get top users for display
function getTopUsers($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT username, points, badge FROM users WHERE status = 'active' ORDER BY points DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
?>