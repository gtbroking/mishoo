<div class="admin-header">
    <div class="header-left">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></h2>
    </div>
    <div class="header-right">
        <a href="../index.php" target="_blank" class="btn btn-outline">
            <i class="fas fa-external-link-alt"></i> View Site
        </a>
        <a href="logout.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>