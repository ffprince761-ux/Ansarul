-- ============================================
-- Owner Panel Database Tables
-- ============================================

USE bizinote;

-- Table: owner_users
-- Description: Stores owner/developer account credentials
CREATE TABLE IF NOT EXISTS owner_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: owner_activity_logs
-- Description: Tracks all owner actions for security and audit
CREATE TABLE IF NOT EXISTS owner_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owner_users(id) ON DELETE CASCADE,
    INDEX idx_owner_id (owner_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: app_analytics
-- Description: Stores app-wide analytics and metrics
CREATE TABLE IF NOT EXISTS app_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value VARCHAR(255),
    user_id INT NULL,
    metadata JSON,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric (metric_name, recorded_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default owner account
-- Username: owner
-- Password: owner123 (Change this after first login!)
INSERT INTO owner_users (username, email, password, full_name) 
VALUES (
    'owner', 
    'owner@bizinote.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Bizinote Owner'
) ON DUPLICATE KEY UPDATE username=username;

-- ============================================
-- Useful Views for Owner Dashboard
-- ============================================

-- View: Daily User Activity
CREATE OR REPLACE VIEW owner_daily_activity AS
SELECT 
    DATE(created_at) as activity_date,
    COUNT(DISTINCT user_id) as active_users,
    COUNT(*) as total_actions
FROM (
    SELECT user_id, created_at FROM bills
    UNION ALL
    SELECT user_id, created_at FROM products
    UNION ALL
    SELECT user_id, created_at FROM customers
    UNION ALL
    SELECT user_id, created_at FROM expenses
) as all_activity
GROUP BY DATE(created_at)
ORDER BY activity_date DESC;

-- View: User Statistics
CREATE OR REPLACE VIEW owner_user_stats AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.business_name,
    u.created_at as registered_at,
    u.updated_at as last_active,
    COUNT(DISTINCT p.id) as product_count,
    COUNT(DISTINCT c.id) as customer_count,
    COUNT(DISTINCT b.id) as bill_count,
    COALESCE(SUM(b.grand_total), 0) as total_revenue,
    COUNT(DISTINCT e.id) as expense_count,
    COALESCE(SUM(e.amount), 0) as total_expenses
FROM users u
LEFT JOIN products p ON u.id = p.user_id
LEFT JOIN customers c ON u.id = c.user_id
LEFT JOIN bills b ON u.id = b.user_id
LEFT JOIN expenses e ON u.id = e.user_id
GROUP BY u.id;

-- View: Revenue Summary
CREATE OR REPLACE VIEW owner_revenue_summary AS
SELECT 
    DATE(date) as revenue_date,
    COUNT(*) as bill_count,
    SUM(grand_total) as total_revenue,
    AVG(grand_total) as avg_bill_value,
    COUNT(DISTINCT user_id) as active_businesses
FROM bills
GROUP BY DATE(date)
ORDER BY revenue_date DESC;
