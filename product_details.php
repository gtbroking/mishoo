<?php
require_once 'config/functions.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

// Get product details
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get product ratings and reviews
$stmt = $pdo->prepare("
    SELECT pr.*, u.username 
    FROM product_ratings pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.product_id = ? 
    ORDER BY pr.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Get related products
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? AND status = 'active' 
    LIMIT 4
");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll();

// Handle review submission
$review_error = '';
$review_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $review_error = 'Please login to submit a review';
    } else {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        
        if ($rating < 1 || $rating > 5) {
            $review_error = 'Please select a valid rating';
        } else {
            // Check if user already reviewed this product
            $stmt = $pdo->prepare("SELECT id FROM product_ratings WHERE product_id = ? AND user_id = ?");
            $stmt->execute([$product_id, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                $review_error = 'You have already reviewed this product';
            } else {
                $stmt = $pdo->prepare("INSERT INTO product_ratings (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment])) {
                    $review_success = 'Review submitted successfully!';
                    // Refresh reviews
                    $stmt = $pdo->prepare("
                        SELECT pr.*, u.username 
                        FROM product_ratings pr 
                        JOIN users u ON pr.user_id = u.id 
                        WHERE pr.product_id = ? 
                        ORDER BY pr.created_at DESC
                    ");
                    $stmt->execute([$product_id]);
                    $reviews = $stmt->fetchAll();
                } else {
                    $review_error = 'Failed to submit review';
                }
            }
        }
    }
}

$rating_summary = getProductRating($product_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Shopping Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="product-details-page">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">Home</a> > 
                <a href="products.php">Products</a> > 
                <a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> > 
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>

            <div class="product-details-container">
                <!-- Product Images -->
                <div class="product-images">
                    <div class="main-image">
                        <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="mainProductImage">
                        <?php if ($product['discount_price']): ?>
                        <div class="discount-badge">
                            <?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>% OFF
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($product['gallery']): ?>
                    <div class="image-thumbnails">
                        <img src="assets/images/<?php echo $product['image']; ?>" alt="Main" class="thumbnail active" onclick="changeMainImage(this)">
                        <?php 
                        $gallery = json_decode($product['gallery'], true);
                        if ($gallery) {
                            foreach ($gallery as $image) {
                                echo '<img src="assets/images/' . $image . '" alt="Gallery" class="thumbnail" onclick="changeMainImage(this)">';
                            }
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info-section">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="product-category">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                    </p>
                    
                    <div class="product-rating-summary">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= ($rating_summary['avg_rating'] ?? 0) ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php echo number_format($rating_summary['avg_rating'] ?? 0, 1); ?> 
                            (<?php echo $rating_summary['total_ratings'] ?? 0; ?> reviews)
                        </span>
                    </div>

                    <div class="product-price">
                        <?php if ($product['discount_price']): ?>
                        <span class="current-price"><?php echo formatCurrency($product['discount_price']); ?></span>
                        <span class="original-price"><?php echo formatCurrency($product['price']); ?></span>
                        <span class="savings">You save <?php echo formatCurrency($product['price'] - $product['discount_price']); ?></span>
                        <?php else: ?>
                        <span class="current-price"><?php echo formatCurrency($product['price']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="points-info">
                        <i class="fas fa-coins"></i>
                        <span>Earn <?php echo $product['points_reward']; ?> points on this purchase</span>
                    </div>

                    <div class="stock-info">
                        <?php if ($product['stock_quantity'] > 0): ?>
                        <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                        <?php else: ?>
                        <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-description">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <div class="product-actions">
                        <div class="quantity-selector">
                            <label>Quantity:</label>
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <?php if ($product['button_type'] === 'add_to_cart'): ?>
                                <button class="btn btn-primary btn-large add-to-cart-detail" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <?php elseif ($product['button_type'] === 'shop_now'): ?>
                                <button class="btn btn-primary btn-large" onclick="buyNow()">
                                    <i class="fas fa-shopping-bag"></i> Buy Now
                                </button>
                                <?php else: ?>
                                <button class="btn btn-secondary btn-large" onclick="inquireNow()">
                                    <i class="fas fa-info-circle"></i> Inquiry Now
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-large" disabled>
                                <i class="fas fa-times"></i> Out of Stock
                            </button>
                            <?php endif; ?>

                            <?php if (isLoggedIn()): ?>
                            <button class="btn btn-outline wishlist-btn-detail <?php echo isInWishlist($_SESSION['user_id'], $product['id']) ? 'active' : ''; ?>" 
                                    data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-heart"></i> Wishlist
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <h3><i class="fas fa-star"></i> Customer Reviews</h3>
                
                <?php if (isLoggedIn()): ?>
                <div class="review-form">
                    <h4>Write a Review</h4>
                    
                    <?php if ($review_error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $review_error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($review_success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $review_success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="rating-input">
                            <label>Your Rating:</label>
                            <div class="star-rating">
                                <input type="radio" name="rating" value="5" id="star5">
                                <label for="star5"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="4" id="star4">
                                <label for="star4"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="3" id="star3">
                                <label for="star3"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="2" id="star2">
                                <label for="star2"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="1" id="star1">
                                <label for="star1"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Your Review:</label>
                            <textarea name="comment" id="comment" rows="4" placeholder="Share your experience with this product..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_review" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div class="login-prompt">
                    <p><a href="login.php">Login</a> to write a review</p>
                </div>
                <?php endif; ?>

                <div class="reviews-list">
                    <?php if (empty($reviews)): ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                    <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <?php if ($review['comment']): ?>
                        <div class="review-comment">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h3>Related Products</h3>
                <div class="products-grid">
                    <?php foreach ($related_products as $related): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="product_details.php?id=<?php echo $related['id']; ?>">
                                <img src="assets/images/<?php echo $related['image']; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                            </a>
                            <?php if ($related['discount_price']): ?>
                            <div class="discount-badge">
                                <?php echo round((($related['price'] - $related['discount_price']) / $related['price']) * 100); ?>% OFF
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h4><a href="product_details.php?id=<?php echo $related['id']; ?>"><?php echo htmlspecialchars($related['name']); ?></a></h4>
                            <div class="price">
                                <?php if ($related['discount_price']): ?>
                                <span class="current-price"><?php echo formatCurrency($related['discount_price']); ?></span>
                                <span class="original-price"><?php echo formatCurrency($related['price']); ?></span>
                                <?php else: ?>
                                <span class="current-price"><?php echo formatCurrency($related['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function changeMainImage(thumbnail) {
            document.getElementById('mainProductImage').src = thumbnail.src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }
        
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            const currentValue = parseInt(quantityInput.value);
            const newValue = currentValue + change;
            const max = parseInt(quantityInput.max);
            
            if (newValue >= 1 && newValue <= max) {
                quantityInput.value = newValue;
            }
        }
        
        // Enhanced add to cart for product details
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartBtn = document.querySelector('.add-to-cart-detail');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const quantity = document.getElementById('quantity').value;
                    this.dataset.quantity = quantity;
                    
                    // Use existing add to cart functionality
                    const event = new Event('click');
                    this.classList.add('add-to-cart');
                    this.dispatchEvent(event);
                });
            }
            
            // Wishlist functionality for details page
            const wishlistBtn = document.querySelector('.wishlist-btn-detail');
            if (wishlistBtn) {
                wishlistBtn.addEventListener('click', function() {
                    this.classList.add('wishlist-btn');
                    const event = new Event('click');
                    this.dispatchEvent(event);
                });
            }
        });
        
        function buyNow() {
            // Add to cart first, then redirect to checkout
            const quantity = document.getElementById('quantity').value;
            const productId = <?php echo $product['id']; ?>;
            
            fetch('api/cart_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: parseInt(quantity),
                    action: 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'checkout.php';
                } else {
                    showNotification(data.message || 'Error occurred', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
            });
        }
        
        function inquireNow() {
            const whatsappNumber = '<?php echo getSetting("whatsapp_number"); ?>';
            const productName = '<?php echo addslashes($product["name"]); ?>';
            const message = `Hi, I'm interested in ${productName}. Can you provide more details?`;
            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }
    </script>
</body>
</html>