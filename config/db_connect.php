<?php
// Database connection configuration
// Update these values according to your Hostinger database settings

$servername = "localhost";  // Usually localhost for Hostinger
$username = "your_db_username";  // Your database username from Hostinger
$password = "your_db_password";  // Your database password from Hostinger  
$dbname = "shopping_website";    // Your database name

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>