<div class="admin-sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li><a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a></li>
            <li><a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Categories
            </a></li>
            <li><a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Orders
            </a></li>
            <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users & Badges
            </a></li>
            <li><a href="banners.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'active' : ''; ?>">
                <i class="fas fa-image"></i> Banners
            </a></li>
            <li><a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a></li>
        </ul>
    </nav>
</div>