<!-- Header -->
<header class="main-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <div class="logo">
                <a href="index.php">
                    <h1><i class="fas fa-shopping-bag"></i> ShopSite</h1>
                </a>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <form action="products.php" method="GET">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <!-- User Actions -->
            <div class="user-actions">
                <?php if(isLoggedIn()): ?>
                    <?php $userData = getUserData($_SESSION['user_id']); ?>
                    <div class="user-menu">
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($userData['username']); ?></span>
                            <div class="user-badge <?php echo strtolower($userData['badge']); ?>">
                                <?php echo $userData['badge']; ?>
                            </div>
                            <span class="points"><i class="fas fa-coins"></i> <?php echo $userData['points']; ?></span>
                        </div>
                        <div class="dropdown">
                            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                            <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                    
                    <a href="wishlist.php" class="action-btn">
                        <i class="fas fa-heart"></i>
                        <span class="count"><?php echo getWishlistCount($_SESSION['user_id']); ?></span>
                    </a>
                    
                    <a href="cart.php" class="action-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="count"><?php echo getCartCount($_SESSION['user_id']); ?></span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <div class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li class="dropdown">
                    <a href="products.php">Categories <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 'active'");
                        $stmt->execute();
                        $navCategories = $stmt->fetchAll();
                        foreach($navCategories as $cat):
                        ?>
                        <li><a href="products.php?category=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li><a href="products.php">All Products</a></li>
                <li><a href="products.php?featured=1">Featured</a></li>
                <li><a href="products.php?discount=1">Deals</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Mobile Navigation -->
<div class="mobile-nav">
    <div class="mobile-nav-content">
        <div class="mobile-nav-header">
            <h3>Menu</h3>
            <button class="close-mobile-nav"><i class="fas fa-times"></i></button>
        </div>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="products.php"><i class="fas fa-th-large"></i> All Products</a></li>
            <?php foreach($navCategories as $cat): ?>
            <li><a href="products.php?category=<?php echo $cat['id']; ?>"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat['name']); ?></a></li>
            <?php endforeach; ?>
            <?php if(isLoggedIn()): ?>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="orders.php"><i class="fas fa-box"></i> Orders</a></li>
            <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <?php else: ?>
            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            <li><a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>