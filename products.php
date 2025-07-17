<?php
require_once 'config/functions.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$featured = isset($_GET['featured']) ? 1 : 0;
$discount = isset($_GET['discount']) ? 1 : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

if ($discount) {
    $where_conditions[] = "p.discount_price IS NOT NULL";
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$order_by = "p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "COALESCE(p.discount_price, p.price) ASC";
        break;
    case 'price_high':
        $order_by = "COALESCE(p.discount_price, p.price) DESC";
        break;
    case 'rating':
        $order_by = "p.id DESC"; // Can be enhanced with actual rating
        break;
    case 'popular':
        $order_by = "p.id DESC"; // Can be enhanced with popularity metric
        break;
}

// Get products
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $where_clause 
    ORDER BY $order_by
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

$page_title = $search ? "Search Results for '$search'" : ($category_id ? "Products" : "All Products");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="products-page">
            <!-- Page Header -->
            <div class="page-header">
                <h1><?php echo $page_title; ?></h1>
                <?php if ($search): ?>
                <p>Found <?php echo count($products); ?> results</p>
                <?php endif; ?>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div class="filters-row">
                    <div class="filter-group">
                        <label>Category:</label>
                        <select onchange="filterProducts()" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Sort by:</label>
                        <select onchange="sortProducts()" id="sortFilter">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                            <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>
                            <input type="checkbox" onchange="toggleDiscount()" <?php echo $discount ? 'checked' : ''; ?>>
                            Discounted Items Only
                        </label>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php if (empty($products)): ?>
                <div class="no-products">
                    <i class="fas fa-search"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filters</p>
                    <a href="products.php" class="btn btn-primary">View All Products</a>
                </div>
                <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if ($product['discount_price']): ?>
                        <div class="discount-badge">
                            <?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>% OFF
                        </div>
                        <?php endif; ?>
                        <?php if (isLoggedIn()): ?>
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
                            <?php if ($product['discount_price']): ?>
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
                            for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $avgRating ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                            <span>(<?php echo $rating['total_ratings'] ?? 0; ?>)</span>
                        </div>
                        <div class="points-reward">
                            <i class="fas fa-coins"></i> Earn <?php echo $product['points_reward']; ?> points
                        </div>
                        <div class="product-actions">
                            <?php if ($product['button_type'] === 'add_to_cart'): ?>
                            <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <?php elseif ($product['button_type'] === 'shop_now'): ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function filterProducts() {
            const category = document.getElementById('categoryFilter').value;
            const currentUrl = new URL(window.location);
            
            if (category) {
                currentUrl.searchParams.set('category', category);
            } else {
                currentUrl.searchParams.delete('category');
            }
            
            window.location.href = currentUrl.toString();
        }
        
        function sortProducts() {
            const sort = document.getElementById('sortFilter').value;
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', sort);
            window.location.href = currentUrl.toString();
        }
        
        function toggleDiscount() {
            const currentUrl = new URL(window.location);
            const checkbox = event.target;
            
            if (checkbox.checked) {
                currentUrl.searchParams.set('discount', '1');
            } else {
                currentUrl.searchParams.delete('discount');
            }
            
            window.location.href = currentUrl.toString();
        }
    </script>
</body>
</html>