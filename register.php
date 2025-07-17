<?php
require_once 'config/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $referral_code = trim($_POST['referral_code']);
    
    // Validation
    if (empty($username) || empty($email) || empty($mobile) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (!isValidMobile($mobile)) {
        $error = 'Please enter a valid mobile number';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            // Check referral code if provided
            $referred_by = null;
            if (!empty($referral_code)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                $stmt->execute([$referral_code]);
                $referrer = $stmt->fetch();
                if ($referrer) {
                    $referred_by = $referral_code;
                    // Give points to referrer
                    $stmt = $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?");
                    $stmt->execute([$referrer['id']]);
                }
            }
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_referral_code = generateReferralCode($username);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, mobile, password, referral_code, referred_by) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $mobile, $hashed_password, $user_referral_code, $referred_by])) {
                $success = 'Account created successfully! You can now login.';
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-user-plus"></i> Sign Up</h2>
                <p>Create your account to start shopping!</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <p><a href="login.php">Click here to login</a></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" id="mobile" name="mobile" required 
                           value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="referral_code">Referral Code (Optional)</label>
                    <input type="text" id="referral_code" name="referral_code" 
                           value="<?php echo isset($_POST['referral_code']) ? htmlspecialchars($_POST['referral_code']) : ''; ?>">
                    <small>Enter referral code to earn bonus points</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
                <p><a href="index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>