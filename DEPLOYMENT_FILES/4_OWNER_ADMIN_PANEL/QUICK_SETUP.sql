-- ============================================
-- QUICK SETUP FOR OWNER PANEL
-- Copy and paste this entire file in phpMyAdmin SQL tab
-- ============================================

USE bizinote;

-- Drop existing tables if any (to start fresh)
DROP TABLE IF EXISTS owner_activity_logs;
DROP TABLE IF EXISTS app_analytics;
DROP TABLE IF EXISTS owner_users;

-- Create owner_users table
CREATE TABLE owner_users (
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

-- Create owner_activity_logs table
CREATE TABLE owner_activity_logs (
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

-- Create app_analytics table
CREATE TABLE app_analytics (
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
-- Password: owner123
INSERT INTO owner_users (username, email, password, full_name, is_active) 
VALUES (
    'owner', 
    'owner@bizinote.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Bizinote Owner',
    1
);

-- Verify setup
SELECT 'Setup Complete!' as Status;
SELECT * FROM owner_users;
