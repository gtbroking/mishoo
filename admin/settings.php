<?php
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_theme' => $_POST['site_theme'],
        'banner_text' => trim($_POST['banner_text']),
        'whatsapp_number' => trim($_POST['whatsapp_number']),
        'upi_id' => trim($_POST['upi_id']),
        'points_to_rupee_ratio' => (int)$_POST['points_to_rupee_ratio'],
        'min_redeem_points' => (int)$_POST['min_redeem_points']
    ];
    
    try {
        foreach ($settings as $key => $value) {
            updateSetting($key, $value);
        }
        $success = 'Settings updated successfully';
    } catch (Exception $e) {
        $error = 'Failed to update settings';
    }
}

// Get current settings
$current_settings = [
    'site_theme' => getSetting('site_theme') ?: 'light',
    'banner_text' => getSetting('banner_text'),
    'whatsapp_number' => getSetting('whatsapp_number'),
    'upi_id' => getSetting('upi_id'),
    'points_to_rupee_ratio' => getSetting('points_to_rupee_ratio') ?: '2',
    'min_redeem_points' => getSetting('min_redeem_points') ?: '100'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 1rem; }
        .admin-content { flex: 1; padding: 2rem; background: #f8f9fa; }
        .settings-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .theme-preview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .theme-option { border: 2px solid #ddd; border-radius: 8px; padding: 1rem; cursor: pointer; transition: all 0.3s; }
        .theme-option.selected { border-color: #3498db; background: #f0f8ff; }
        .theme-option h4 { margin: 0 0 0.5rem 0; }
        .theme-preview-box { height: 60px; border-radius: 4px; margin-bottom: 0.5rem; }
        .theme-light .theme-preview-box { background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); }
        .theme-dark .theme-preview-box { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); }
        .theme-blue .theme-preview-box { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .theme-green .theme-preview-box { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); }
        .theme-purple .theme-preview-box { background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); }
        .theme-red .theme-preview-box { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .points-info { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="admin-content">
                <h1><i class="fas fa-cog"></i> Website Settings</h1>
                
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
                
                <form method="POST">
                    <!-- Theme Settings -->
                    <div class="settings-section">
                        <h2><i class="fas fa-palette"></i> Theme Settings</h2>
                        
                        <div class="form-group">
                            <label>Website Theme</label>
                            <div class="theme-preview">
                                <div class="theme-option theme-light <?php echo $current_settings['site_theme'] == 'light' ? 'selected' : ''; ?>" 
                                     onclick="selectTheme('light')">
                                    <div class="theme-preview-box"></div>
                                    <h4>Light Theme</h4>
                                    <p>Clean and bright design</p>
                                    <input type="radio" name="site_theme" value="light" <?php echo $current_settings['site_theme'] == 'light' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                                
                                <div class="theme-option theme-dark <?php echo $current_settings['site_theme'] == 'dark' ? 'selected' : ''; ?>" 
                                     onclick="selectTheme('dark')">
                                    <div class="theme-preview-box"></div>
                                    <h4>Dark Theme</h4>
                                    <p>Modern dark interface</p>
                                    <input type="radio" name="site_theme" value="dark" <?php echo $current_settings['site_theme'] == 'dark' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                                
                                <div class="theme-option theme-blue <?php echo $current_settings['site_theme'] == 'blue' ? 'selected' : ''; ?>" 
                                     onclick="selectTheme('blue')">
                                    <div class="theme-preview-box"></div>
                                    <h4>Blue Theme</h4>
                                    <p>Professional blue tones</p>
                                    <input type="radio" name="site_theme" value="blue" <?php echo $current_settings['site_theme'] == 'blue' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                                
                                <div class="theme-option theme-green <?php echo $current_settings['site_theme'] == 'green' ? 'selected' : ''; ?>" 
                                     onclick="selectTheme('green')">
                                    <div class="theme-preview-box"></div>
                                    <h4>Green Theme</h4>
                                    <p>Fresh green colors</p>
                                    <input type="radio" name="site_theme" value="green" <?php echo $current_settings['site_theme'] == 'green' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                                
                                <div class="theme-option theme-purple <?php echo $current_settings['site_theme'] == 'purple' ? 'selected' : ''; ?>" 
                                     onclick="selectTheme('purple')">
                                    <div class="theme-preview-box"></div>
                                    <h4>Purple Theme</h4>
                                    <p>Creative purple design</p>
                                    <input type="radio" name="site_theme" value="purple" <?php echo $current_settings['site_theme'] == 'purple' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                                
                                <div class="theme-option theme-red <?php echo $current_settings['site_theme'] == 'red' ? 'selected' : ''; ?>" 
                                     onclick="selectTheme('red')">
                                    <div class="theme-preview-box"></div>
                                    <h4>Red Theme</h4>
                                    <p>Bold red accents</p>
                                    <input type="radio" name="site_theme" value="red" <?php echo $current_settings['site_theme'] == 'red' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Banner Settings -->
                    <div class="settings-section">
                        <h2><i class="fas fa-bullhorn"></i> Banner Settings</h2>
                        
                        <div class="form-group">
                            <label for="banner_text">Top Banner Text</label>
                            <input type="text" id="banner_text" name="banner_text" 
                                   value="<?php echo htmlspecialchars($current_settings['banner_text']); ?>"
                                   placeholder="e.g., Welcome to Our Store - Best Deals Online!">
                            <small>This text appears at the top of your website</small>
                        </div>
                    </div>
                    
                    <!-- Contact Settings -->
                    <div class="settings-section">
                        <h2><i class="fas fa-phone"></i> Contact Settings</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="whatsapp_number">WhatsApp Number</label>
                                <input type="text" id="whatsapp_number" name="whatsapp_number" 
                                       value="<?php echo htmlspecialchars($current_settings['whatsapp_number']); ?>"
                                       placeholder="e.g., +919876543210">
                                <small>Include country code (e.g., +91 for India)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="upi_id">UPI ID for Payments</label>
                                <input type="text" id="upi_id" name="upi_id" 
                                       value="<?php echo htmlspecialchars($current_settings['upi_id']); ?>"
                                       placeholder="e.g., merchant@paytm">
                                <small>Your UPI ID for receiving payments</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Points System Settings -->
                    <div class="settings-section">
                        <h2><i class="fas fa-coins"></i> Points System Settings</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="points_to_rupee_ratio">Points to Rupee Ratio</label>
                                <select id="points_to_rupee_ratio" name="points_to_rupee_ratio">
                                    <option value="1" <?php echo $current_settings['points_to_rupee_ratio'] == '1' ? 'selected' : ''; ?>>1 Point = ₹1</option>
                                    <option value="2" <?php echo $current_settings['points_to_rupee_ratio'] == '2' ? 'selected' : ''; ?>>2 Points = ₹1</option>
                                    <option value="3" <?php echo $current_settings['points_to_rupee_ratio'] == '3' ? 'selected' : ''; ?>>3 Points = ₹1</option>
                                    <option value="5" <?php echo $current_settings['points_to_rupee_ratio'] == '5' ? 'selected' : ''; ?>>5 Points = ₹1</option>
                                    <option value="10" <?php echo $current_settings['points_to_rupee_ratio'] == '10' ? 'selected' : ''; ?>>10 Points = ₹1</option>
                                </select>
                                <small>How many points equal 1 rupee</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="min_redeem_points">Minimum Redemption Points</label>
                                <input type="number" id="min_redeem_points" name="min_redeem_points" min="10" 
                                       value="<?php echo $current_settings['min_redeem_points']; ?>">
                                <small>Minimum points required to redeem</small>
                            </div>
                        </div>
                        
                        <div class="points-info">
                            <h4>Current Points System:</h4>
                            <p><strong>Conversion Rate:</strong> <?php echo $current_settings['points_to_rupee_ratio']; ?> points = ₹1</p>
                            <p><strong>Minimum Redemption:</strong> <?php echo $current_settings['min_redeem_points']; ?> points (₹<?php echo $current_settings['min_redeem_points'] / $current_settings['points_to_rupee_ratio']; ?>)</p>
                            <p><strong>Badge Benefits:</strong></p>
                            <ul>
                                <li>Silver: 3 points per product</li>
                                <li>Gold: 5 points per product + invite links</li>
                                <li>Platinum: 10 points per product + invite links</li>
                                <li>Elite: 20 points per product + invite links</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function selectTheme(theme) {
            // Remove selected class from all options
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            document.querySelector('.theme-' + theme).classList.add('selected');
            
            // Check the radio button
            document.querySelector('input[value="' + theme + '"]').checked = true;
        }
    </script>
</body>
</html>