-- ============================================
-- Bizinote - Complete Database Structure
-- Business Management Application
-- Version: 1.0.0
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS bizinote CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bizinote;

-- ============================================
-- Table: users
-- Description: Stores user account information
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    business_name VARCHAR(255),
    mobile VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: products
-- Description: Stores product/inventory information
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0.00,
    stock INT DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'Nos',
    low_stock_threshold INT DEFAULT 10,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_stock (stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: stock_adjustments
-- Description: Tracks stock changes and adjustments
-- ============================================
CREATE TABLE IF NOT EXISTS stock_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    quantity INT NOT NULL,
    date DATE NOT NULL,
    note VARCHAR(255) DEFAULT 'Stock Added',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: customers
-- Description: Stores customer information
-- ============================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    mobile VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    INDEX idx_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: bills
-- Description: Stores billing/invoice information
-- ============================================
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    customer_name VARCHAR(255),
    customer_mobile VARCHAR(20),
    customer_email VARCHAR(255),
    customer_address TEXT,
    items JSON,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) DEFAULT 0.00,
    grand_total DECIMAL(10,2) DEFAULT 0.00,
    payment_mode VARCHAR(50) DEFAULT 'Cash',
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_date (date),
    INDEX idx_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: expenses
-- Description: Stores business expense records
-- ============================================
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100),
    description TEXT,
    amount DECIMAL(10,2) DEFAULT 0.00,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Security Tables
-- ============================================

-- Table: security_logs
-- Description: Logs security events and actions
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    user_id INT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip_address, action),
    INDEX idx_created (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: blocked_ips
-- Description: Stores blocked IP addresses
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    blocked_until DATETIME NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_blocked (ip_address, blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: failed_login_attempts
-- Description: Tracks failed login attempts for security
CREATE TABLE IF NOT EXISTS failed_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data (Optional - Comment out if not needed)
-- ============================================

-- Sample User (Password: admin123)
-- INSERT INTO users (name, email, password, business_name, mobile, address) 
-- VALUES (
--     'Admin User', 
--     'admin@bizinote.com', 
--     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
--     'Bizinote Demo Store',
--     '+91 9876543210',
--     '123 Business Street, City, State - 123456'
-- );

-- ============================================
-- Views (Optional - for reporting)
-- ============================================

-- View: Low Stock Products
CREATE OR REPLACE VIEW low_stock_products AS
SELECT 
    p.id,
    p.user_id,
    p.name,
    p.category,
    p.stock,
    p.unit,
    p.low_stock_threshold,
    p.price
FROM products p
WHERE p.stock <= p.low_stock_threshold;

-- View: Monthly Sales Summary
CREATE OR REPLACE VIEW monthly_sales_summary AS
SELECT 
    user_id,
    YEAR(date) as year,
    MONTH(date) as month,
    COUNT(*) as total_bills,
    SUM(grand_total) as total_sales,
    AVG(grand_total) as average_sale
FROM bills
GROUP BY user_id, YEAR(date), MONTH(date);

-- View: Monthly Expenses Summary
CREATE OR REPLACE VIEW monthly_expenses_summary AS
SELECT 
    user_id,
    YEAR(date) as year,
    MONTH(date) as month,
    category,
    COUNT(*) as total_expenses,
    SUM(amount) as total_amount
FROM expenses
GROUP BY user_id, YEAR(date), MONTH(date), category;

-- ============================================
-- Stored Procedures (Optional)
-- ============================================

DELIMITER //

-- Procedure: Get User Dashboard Stats
CREATE PROCEDURE IF NOT EXISTS get_dashboard_stats(IN p_user_id INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM products WHERE user_id = p_user_id) as total_products,
        (SELECT COUNT(*) FROM products WHERE user_id = p_user_id AND stock <= low_stock_threshold) as low_stock_products,
        (SELECT COUNT(*) FROM customers WHERE user_id = p_user_id) as total_customers,
        (SELECT COUNT(*) FROM bills WHERE user_id = p_user_id AND date = CURDATE()) as today_bills,
        (SELECT COALESCE(SUM(grand_total), 0) FROM bills WHERE user_id = p_user_id AND date = CURDATE()) as today_sales,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = p_user_id AND date = CURDATE()) as today_expenses;
END //

-- Procedure: Update Product Stock
CREATE PROCEDURE IF NOT EXISTS update_product_stock(
    IN p_product_id INT,
    IN p_user_id INT,
    IN p_quantity INT,
    IN p_note VARCHAR(255)
)
BEGIN
    DECLARE current_stock INT;
    
    -- Get current stock
    SELECT stock INTO current_stock FROM products WHERE id = p_product_id;
    
    -- Update stock
    UPDATE products SET stock = stock + p_quantity WHERE id = p_product_id;
    
    -- Log adjustment
    INSERT INTO stock_adjustments (product_id, user_id, quantity, date, note)
    VALUES (p_product_id, p_user_id, p_quantity, CURDATE(), p_note);
END //

DELIMITER ;

-- ============================================
-- Triggers
-- ============================================

DELIMITER //

-- Trigger: Update product stock after bill creation
CREATE TRIGGER IF NOT EXISTS after_bill_insert
AFTER INSERT ON bills
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE cur CURSOR FOR 
        SELECT JSON_UNQUOTE(JSON_EXTRACT(item, '$.productId')),
               JSON_UNQUOTE(JSON_EXTRACT(item, '$.quantity'))
        FROM JSON_TABLE(NEW.items, '$[*]' COLUMNS (item JSON PATH '$')) AS jt;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_product_id, v_quantity;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Decrease product stock
        UPDATE products 
        SET stock = stock - v_quantity 
        WHERE id = v_product_id AND user_id = NEW.user_id;
    END LOOP;
    
    CLOSE cur;
END //

DELIMITER ;

-- ============================================
-- Indexes for Performance
-- ============================================

-- Additional indexes for better query performance
CREATE INDEX idx_products_user_category ON products(user_id, category);
CREATE INDEX idx_bills_user_date ON bills(user_id, date);
CREATE INDEX idx_expenses_user_date ON expenses(user_id, date);
CREATE INDEX idx_customers_user_name ON customers(user_id, name);

-- ============================================
-- Database Setup Complete
-- ============================================

-- Grant privileges (adjust as needed for your setup)
-- GRANT ALL PRIVILEGES ON bizinote.* TO 'bizinote_user'@'localhost' IDENTIFIED BY 'your_password';
-- FLUSH PRIVILEGES;
