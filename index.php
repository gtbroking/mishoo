<?php
require_once 'config/functions.php';

// Get banners
$stmt = $pdo->prepare("SELECT * FROM banners WHERE status = 'active' ORDER BY position");
$stmt->execute();
$banners = $stmt->fetchAll();

// Get categories
$stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 'active'");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get featured products
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 12");
$stmt->execute();
$products = $stmt->fetchAll();

// Get top users
$topUsers = getTopUsers(4);

// Get settings
$bannerText = getSetting('banner_text');
$whatsappNumber = getSetting('whatsapp_number');
$theme = getSetting('site_theme');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Website - Best Deals Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="theme-<?php echo $theme; ?>">
    <?php include 'includes/header.php'; ?>

    <!-- Banner Text -->
    <?php if($bannerText): ?>
    <div class="banner-text">
        <div class="container">
            <p><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($bannerText); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Users Section -->
    <?php if(!empty($topUsers)): ?>
    <div class="top-users-section">
        <div class="container">
            <h2><i class="fas fa-trophy"></i> Top Users</h2>
            <div class="top-users-grid">
                <?php foreach($topUsers as $user): ?>
                <div class="top-user-card">
                    <div class="user-badge <?php echo strtolower($user['badge']); ?>">
                        <?php echo $user['badge']; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p><?php echo $user['points']; ?> Points</p>
                    <div class="clap-animation">👏</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Banners -->
    <?php if(!empty($banners)): ?>
    <div class="hero-slider">
        <div class="slider-container">
            <?php foreach($banners as $index => $banner): ?>
            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                <img src="assets/images/<?php echo $banner['image']; ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                <div class="slide-content">
                    <h2><?php echo htmlspecialchars($banner['title']); ?></h2>
                    <?php if($banner['link']): ?>
                    <a href="<?php echo $banner['link']; ?>" class="btn btn-primary">Shop Now</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="slider-nav">
            <button class="prev"><i class="fas fa-chevron-left"></i></button>
            <button class="next"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Categories Section -->
    <div class="categories-section">
        <div class="container">
            <h2>Shop by Category</h2>
            <div class="categories-grid">
                <?php foreach($categories as $category): ?>
                <div class="category-card">
                    <a href="products.php?category=<?php echo $category['id']; ?>">
                        <img src="assets/images/<?php echo $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Featured Products -->
    <div class="products-section">
        <div class="container">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <?php foreach($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if($product['discount_price']): ?>
                        <div class="discount-badge">
                            <?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>% OFF
                        </div>
                        <?php endif; ?>
                        <?php if(isLoggedIn()): ?>
                        <button class="wishlist-btn <?php echo isInWishlist($_SESSION['user_id'], $product['id']) ? 'active' : ''; ?>" 
                                data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-heart"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <div class="price">
                            <?php if($product['discount_price']): ?>
                            <span class="current-price"><?php echo formatCurrency($product['discount_price']); ?></span>
                            <span class="original-price"><?php echo formatCurrency($product['price']); ?></span>
                            <?php else: ?>
                            <span class="current-price"><?php echo formatCurrency($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-rating">
                            <?php 
                            $rating = getProductRating($product['id']);
                            $avgRating = $rating['avg_rating'] ?? 0;
                            for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $avgRating ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                            <span>(<?php echo $rating['total_ratings'] ?? 0; ?>)</span>
                        </div>
                        <div class="points-reward">
                            <i class="fas fa-coins"></i> Earn <?php echo $product['points_reward']; ?> points
                        </div>
                        <div class="product-actions">
                            <?php if($product['button_type'] === 'add_to_cart'): ?>
                            <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <?php elseif($product['button_type'] === 'shop_now'): ?>
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i> Shop Now
                            </a>
                            <?php else: ?>
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-info-circle"></i> Inquiry Now
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- WhatsApp Help Button -->
    <div class="whatsapp-help">
        <a href="https://wa.me/<?php echo $whatsappNumber; ?>" target="_blank" class="whatsapp-btn">
            <i class="fab fa-whatsapp"></i>
            <span>Help & Support</span>
        </a>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
</body>
</html>