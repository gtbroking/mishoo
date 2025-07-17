<?php
require_once '../config/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $category_id = (int)$_POST['category_id'];
        $stock_quantity = (int)$_POST['stock_quantity'];
        $points_reward = (int)$_POST['points_reward'];
        $button_type = $_POST['button_type'];
        $image = $_POST['image'];
        
        if (empty($name) || empty($description) || $price <= 0 || $category_id <= 0) {
            $error = 'Please fill in all required fields';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO products (name, description, price, discount_price, category_id, image, stock_quantity, points_reward, button_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$name, $description, $price, $discount_price, $category_id, $image, $stock_quantity, $points_reward, $button_type])) {
                $success = 'Product added successfully';
            } else {
                $error = 'Failed to add product';
            }
        }
    }
    
    if (isset($_POST['update_product'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $category_id = (int)$_POST['category_id'];
        $stock_quantity = (int)$_POST['stock_quantity'];
        $points_reward = (int)$_POST['points_reward'];
        $button_type = $_POST['button_type'];
        $image = $_POST['image'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, discount_price = ?, category_id = ?, 
                image = ?, stock_quantity = ?, points_reward = ?, button_type = ?, status = ?
            WHERE id = ?
        ");
        if ($stmt->execute([$name, $description, $price, $discount_price, $category_id, $image, $stock_quantity, $points_reward, $button_type, $status, $id])) {
            $success = 'Product updated successfully';
        } else {
            $error = 'Failed to update product';
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $id = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Product deleted successfully';
        } else {
            $error = 'Failed to delete product';
        }
    }
}

// Get products
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll();

// Get categories
$stmt = $pdo->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 1rem; }
        .admin-content { flex: 1; padding: 2rem; background: #f8f9fa; }
        .product-form { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .products-table { background: white; border-radius: 8px; overflow: hidden; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.9rem; margin: 0 0.25rem; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="admin-content">
                <h1><i class="fas fa-box"></i> Manage Products</h1>
                
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
                
                <!-- Add/Edit Product Form -->
                <div class="product-form">
                    <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
                    
                    <form method="POST">
                        <?php if ($edit_product): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Product Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Category *</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($edit_product && $edit_product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="4" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price (₹) *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required 
                                       value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="discount_price">Discount Price (₹)</label>
                                <input type="number" id="discount_price" name="discount_price" step="0.01" min="0" 
                                       value="<?php echo $edit_product ? $edit_product['discount_price'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock_quantity">Stock Quantity *</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                                       value="<?php echo $edit_product ? $edit_product['stock_quantity'] : '0'; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="points_reward">Points Reward *</label>
                                <input type="number" id="points_reward" name="points_reward" min="1" required 
                                       value="<?php echo $edit_product ? $edit_product['points_reward'] : '3'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="button_type">Button Type *</label>
                                <select id="button_type" name="button_type" required>
                                    <option value="add_to_cart" <?php echo ($edit_product && $edit_product['button_type'] == 'add_to_cart') ? 'selected' : ''; ?>>Add to Cart</option>
                                    <option value="shop_now" <?php echo ($edit_product && $edit_product['button_type'] == 'shop_now') ? 'selected' : ''; ?>>Shop Now</option>
                                    <option value="inquiry_now" <?php echo ($edit_product && $edit_product['button_type'] == 'inquiry_now') ? 'selected' : ''; ?>>Inquiry Now</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image Filename *</label>
                                <input type="text" id="image" name="image" required placeholder="e.g., product1.jpg" 
                                       value="<?php echo $edit_product ? htmlspecialchars($edit_product['image']) : ''; ?>">
                                <small>Upload image to assets/images/ folder first</small>
                            </div>
                        </div>
                        
                        <?php if ($edit_product): ?>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $edit_product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                            </button>
                            
                            <?php if ($edit_product): ?>
                            <a href="products.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Products Table -->
                <div class="products-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="../assets/images/<?php echo $product['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <br><small><?php echo ucfirst($product['button_type']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>
                                    <?php if ($product['discount_price']): ?>
                                    <span style="color: #e74c3c;">₹<?php echo number_format($product['discount_price'], 2); ?></span>
                                    <br><small style="text-decoration: line-through;">₹<?php echo number_format($product['price'], 2); ?></small>
                                    <?php else: ?>
                                    ₹<?php echo number_format($product['price'], 2); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $product['stock_quantity'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $product['points_reward']; ?></td>
                                <td>
                                    <span class="status-<?php echo $product['status']; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>