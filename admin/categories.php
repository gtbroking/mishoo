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
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $image = $_POST['image'];
        
        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $description, $image])) {
                $success = 'Category added successfully';
            } else {
                $error = 'Failed to add category';
            }
        }
    }
    
    if (isset($_POST['update_category'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $image = $_POST['image'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, image = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $image, $status, $id])) {
            $success = 'Category updated successfully';
        } else {
            $error = 'Failed to update category';
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['category_id'];
        $stmt = $pdo->prepare("UPDATE categories SET status = 'inactive' WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Category deleted successfully';
        } else {
            $error = 'Failed to delete category';
        }
    }
}

// Get categories
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 1rem; }
        .admin-content { flex: 1; padding: 2rem; background: #f8f9fa; }
        .category-form { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .categories-table { background: white; border-radius: 8px; overflow: hidden; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .category-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
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
                <h1><i class="fas fa-tags"></i> Manage Categories</h1>
                
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
                
                <!-- Add/Edit Category Form -->
                <div class="category-form">
                    <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
                    
                    <form method="POST">
                        <?php if ($edit_category): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Category Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image Filename</label>
                                <input type="text" id="image" name="image" placeholder="e.g., category1.jpg" 
                                       value="<?php echo $edit_category ? htmlspecialchars($edit_category['image']) : ''; ?>">
                                <small>Upload image to assets/images/ folder first</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                        </div>
                        
                        <?php if ($edit_category): ?>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $edit_category['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_category['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="submit" name="<?php echo $edit_category ? 'update_category' : 'add_category'; ?>" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                            </button>
                            
                            <?php if ($edit_category): ?>
                            <a href="categories.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Categories Table -->
                <div class="categories-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <?php if ($category['image']): ?>
                                    <img src="../assets/images/<?php echo $category['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                         class="category-image">
                                    <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image" style="color: #ccc;"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <span class="<?php echo $category['product_count'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $category['product_count']; ?> products
                                    </span>
                                </td>
                                <td>
                                    <span class="status-<?php echo $category['status']; ?>">
                                        <?php echo ucfirst($category['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <a href="categories.php?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <?php if ($category['product_count'] == 0): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="Cannot delete category with products">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
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