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
    if (isset($_POST['add_banner'])) {
        $title = trim($_POST['title']);
        $image = $_POST['image'];
        $link = trim($_POST['link']);
        $position = (int)$_POST['position'];
        
        if (empty($image)) {
            $error = 'Banner image is required';
        } else {
            $stmt = $pdo->prepare("INSERT INTO banners (title, image, link, position) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $image, $link, $position])) {
                $success = 'Banner added successfully';
            } else {
                $error = 'Failed to add banner';
            }
        }
    }
    
    if (isset($_POST['update_banner'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $image = $_POST['image'];
        $link = trim($_POST['link']);
        $position = (int)$_POST['position'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE banners SET title = ?, image = ?, link = ?, position = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$title, $image, $link, $position, $status, $id])) {
            $success = 'Banner updated successfully';
        } else {
            $error = 'Failed to update banner';
        }
    }
    
    if (isset($_POST['delete_banner'])) {
        $id = (int)$_POST['banner_id'];
        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Banner deleted successfully';
        } else {
            $error = 'Failed to delete banner';
        }
    }
}

// Get banners
$stmt = $pdo->prepare("SELECT * FROM banners ORDER BY position, created_at DESC");
$stmt->execute();
$banners = $stmt->fetchAll();

// Get banner for editing
$edit_banner = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_banner = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Banners - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 1rem; }
        .admin-content { flex: 1; padding: 2rem; background: #f8f9fa; }
        .banner-form { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .banners-table { background: white; border-radius: 8px; overflow: hidden; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .banner-image { width: 120px; height: 60px; object-fit: cover; border-radius: 4px; }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.9rem; margin: 0 0.25rem; }
        .banner-preview { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .preview-slider { position: relative; height: 300px; border-radius: 8px; overflow: hidden; }
        .preview-slide { position: absolute; width: 100%; height: 100%; opacity: 0; transition: opacity 0.5s; }
        .preview-slide.active { opacity: 1; }
        .preview-slide img { width: 100%; height: 100%; object-fit: cover; }
        .preview-content { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white; padding: 2rem; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <?php include 'includes/header.php'; ?>
            
            <div class="admin-content">
                <h1><i class="fas fa-image"></i> Manage Banners</h1>
                
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
                
                <!-- Banner Preview -->
                <?php 
                $active_banners = array_filter($banners, function($banner) {
                    return $banner['status'] === 'active';
                });
                if (!empty($active_banners)): 
                ?>
                <div class="banner-preview">
                    <h2>Banner Preview</h2>
                    <div class="preview-slider">
                        <?php foreach ($active_banners as $index => $banner): ?>
                        <div class="preview-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                            <img src="../assets/images/<?php echo $banner['image']; ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                            <div class="preview-content">
                                <h3><?php echo htmlspecialchars($banner['title']); ?></h3>
                                <?php if ($banner['link']): ?>
                                <p>Links to: <?php echo htmlspecialchars($banner['link']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Add/Edit Banner Form -->
                <div class="banner-form">
                    <h2><?php echo $edit_banner ? 'Edit Banner' : 'Add New Banner'; ?></h2>
                    
                    <form method="POST">
                        <?php if ($edit_banner): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_banner['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Banner Title</label>
                                <input type="text" id="title" name="title" 
                                       value="<?php echo $edit_banner ? htmlspecialchars($edit_banner['title']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image Filename *</label>
                                <input type="text" id="image" name="image" required placeholder="e.g., banner1.jpg" 
                                       value="<?php echo $edit_banner ? htmlspecialchars($edit_banner['image']) : ''; ?>">
                                <small>Upload image to assets/images/ folder first (Recommended: 1200x400px)</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="link">Link URL</label>
                                <input type="text" id="link" name="link" placeholder="e.g., products.php" 
                                       value="<?php echo $edit_banner ? htmlspecialchars($edit_banner['link']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="position">Position (Order)</label>
                                <input type="number" id="position" name="position" min="0" 
                                       value="<?php echo $edit_banner ? $edit_banner['position'] : '0'; ?>">
                                <small>Lower numbers appear first</small>
                            </div>
                        </div>
                        
                        <?php if ($edit_banner): ?>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $edit_banner['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_banner['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="submit" name="<?php echo $edit_banner ? 'update_banner' : 'add_banner'; ?>" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_banner ? 'Update Banner' : 'Add Banner'; ?>
                            </button>
                            
                            <?php if ($edit_banner): ?>
                            <a href="banners.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Banners Table -->
                <div class="banners-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Link</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td>
                                    <img src="../assets/images/<?php echo $banner['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                         class="banner-image">
                                </td>
                                <td><strong><?php echo htmlspecialchars($banner['title']); ?></strong></td>
                                <td>
                                    <?php if ($banner['link']): ?>
                                    <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($banner['link']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span style="color: #999;">No link</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $banner['position']; ?></td>
                                <td>
                                    <span class="status-<?php echo $banner['status']; ?>">
                                        <?php echo ucfirst($banner['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($banner['created_at'])); ?></td>
                                <td>
                                    <a href="banners.php?edit=<?php echo $banner['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this banner?')">
                                        <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                        <button type="submit" name="delete_banner" class="btn btn-sm btn-danger">
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
    
    <script>
        // Auto-rotate banner preview
        let currentSlide = 0;
        const slides = document.querySelectorAll('.preview-slide');
        
        if (slides.length > 1) {
            setInterval(() => {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }, 3000);
        }
    </script>
</body>
</html>