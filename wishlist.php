<?php
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $product_id = (int)$_POST['product_id'];
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    header('Location: wishlist.php');
    exit;
}

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT w.*, p.name, p.price, p.discount_price, p.image, p.stock_quantity, p.points_reward, c.name as category_name
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? AND p.status = 'active'
    ORDER BY w.created_at DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="wishlist-page">
            <h1><i class="fas fa-heart"></i> My Wishlist</h1>
            
            <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart"></i>
                <h2>Your wishlist is empty</h2>
                <p>Save your favorite products to your wishlist</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Browse Products
                </a>
            </div>
            <?php else: ?>
            
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                <div class="wishlist-item">
                    <div class="product-image">
                        <a href="product_details.php?id=<?php echo $item['product_id']; ?>">
                            <img src="assets/images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </a>
                        <?php if ($item['discount_price']): ?>
                        <div class="discount-badge">
                            <?php echo round((($item['price'] - $item['discount_price']) / $item['price']) * 100); ?>% OFF
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="remove-form">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <button type="submit" name="remove_item" class="remove-btn" title="Remove from wishlist">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="product-info">
                        <h3>
                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </h3>
                        <p class="category"><?php echo htmlspecialchars($item['category_name']); ?></p>
                        
                        <div class="price">
                            <?php if ($item['discount_price']): ?>
                            <span class="current-price"><?php echo formatCurrency($item['discount_price']); ?></span>
                            <span class="original-price"><?php echo formatCurrency($item['price']); ?></span>
                            <?php else: ?>
                            <span class="current-price"><?php echo formatCurrency($item['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="points-reward">
                            <i class="fas fa-coins"></i> Earn <?php echo $item['points_reward']; ?> points
                        </div>
                        
                        <div class="stock-status">
                            <?php if ($item['stock_quantity'] > 0): ?>
                            <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock</span>
                            <?php else: ?>
                            <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions">
                            <?php if ($item['stock_quantity'] > 0): ?>
                            <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $item['product_id']; ?>">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-times"></i> Out of Stock
                            </button>
                            <?php endif; ?>
                            
                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                        
                        <div class="added-date">
                            Added on <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="wishlist-actions">
                <a href="products.php" class="btn btn-outline">
                    <i class="fas fa-plus"></i> Add More Items
                </a>
                
                <button class="btn btn-primary" onclick="addAllToCart()">
                    <i class="fas fa-shopping-cart"></i> Add All to Cart
                </button>
            </div>
            
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function addAllToCart() {
            const inStockItems = document.querySelectorAll('.add-to-cart');
            let addedCount = 0;
            let totalItems = inStockItems.length;
            
            if (totalItems === 0) {
                showNotification('No items available to add to cart', 'warning');
                return;
            }
            
            inStockItems.forEach((btn, index) => {
                setTimeout(() => {
                    btn.click();
                    addedCount++;
                    
                    if (addedCount === totalItems) {
                        setTimeout(() => {
                            showNotification(`Added ${addedCount} items to cart!`, 'success');
                        }, 500);
                    }
                }, index * 200); // Stagger the requests
            });
        }
        
        // Confirm before removing items
        document.querySelectorAll('.remove-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Remove this item from your wishlist?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>