-- Shopping Website Database Structure
-- Import this file into phpMyAdmin at your Hostinger server

CREATE DATABASE IF NOT EXISTS shopping_website;
USE shopping_website;

-- Admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    secret_key VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admin (username, password, email, secret_key) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'secret_admin_bypass_2024');

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    category_id INT,
    image VARCHAR(255),
    gallery TEXT, -- JSON array of images
    stock_quantity INT DEFAULT 0,
    points_reward INT DEFAULT 3,
    button_type ENUM('add_to_cart', 'shop_now', 'inquiry_now') DEFAULT 'add_to_cart',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    referral_code VARCHAR(20) UNIQUE,
    referred_by VARCHAR(20) DEFAULT NULL,
    points INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    badge ENUM('SILVER', 'GOLD', 'PLATINUM', 'ELITE') DEFAULT 'SILVER',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    points_used INT DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    points_earned INT DEFAULT 0,
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'UPI',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Product ratings table
CREATE TABLE IF NOT EXISTS product_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Banners table
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    position INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Points redemption table
CREATE TABLE IF NOT EXISTS points_redemption (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points_redeemed INT NOT NULL,
    cash_value DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample categories
INSERT INTO categories (name, description, image) VALUES
('Electronics', 'Mobile phones, laptops, accessories', 'electronics.jpg'),
('Fashion', 'Clothing, shoes, accessories', 'fashion.jpg'),
('Home & Kitchen', 'Home appliances, kitchen items', 'home.jpg'),
('Beauty', 'Cosmetics, skincare, personal care', 'beauty.jpg');

-- Insert sample products
INSERT INTO products (name, description, price, discount_price, category_id, image, points_reward, stock_quantity) VALUES
('Smartphone Pro Max', 'Latest smartphone with advanced features', 25999.00, 23999.00, 1, 'phone1.jpg', 50, 100),
('Wireless Earbuds', 'Premium quality wireless earbuds', 2999.00, 1999.00, 1, 'earbuds1.jpg', 10, 200),
('Cotton T-Shirt', 'Comfortable cotton t-shirt for men', 599.00, 399.00, 2, 'tshirt1.jpg', 5, 500),
('Women Dress', 'Elegant dress for women', 1299.00, 999.00, 2, 'dress1.jpg', 8, 150),
('Kitchen Mixer', 'High-speed kitchen mixer grinder', 3499.00, 2999.00, 3, 'mixer1.jpg', 15, 80),
('Face Cream', 'Anti-aging face cream', 899.00, 699.00, 4, 'cream1.jpg', 7, 300);

-- Insert sample banners
INSERT INTO banners (title, image, link, position) VALUES
('Big Sale - Up to 70% Off', 'banner1.jpg', 'products.php', 1),
('New Arrivals', 'banner2.jpg', 'products.php?category=new', 2),
('Electronics Deal', 'banner3.jpg', 'products.php?category=1', 3);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_theme', 'light'),
('banner_text', 'Welcome to Our Shopping Store - Best Deals Online!'),
('whatsapp_number', '+919876543210'),
('upi_id', 'merchant@paytm'),
('points_to_rupee_ratio', '2'),
('min_redeem_points', '100');

-- Create sample user for testing
INSERT INTO users (username, email, mobile, password, referral_code, points, total_orders, badge) VALUES
('testuser', 'test@example.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TEST123', 150, 5, 'GOLD');