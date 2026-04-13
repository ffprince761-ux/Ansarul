-- =============================================
-- Bizinote DB - Hostinger Production (Clean)
-- Database: bizinote_db
-- Error-free for MySQL 5.7+ / MariaDB 10.3+
-- =============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Drop views first
-- --------------------------------------------------------
DROP VIEW IF EXISTS `owner_daily_activity`;
DROP VIEW IF EXISTS `owner_revenue_summary`;
DROP VIEW IF EXISTS `owner_user_stats`;

-- --------------------------------------------------------
-- Drop all tables (reverse dependency order)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `udhari_payments`;
DROP TABLE IF EXISTS `user_plans`;
DROP TABLE IF EXISTS `bill_items`;
DROP TABLE IF EXISTS `bills`;
DROP TABLE IF EXISTS `stock_adjustments`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `backups`;
DROP TABLE IF EXISTS `active_sessions`;
DROP TABLE IF EXISTS `app_analytics`;
DROP TABLE IF EXISTS `app_error_logs`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `blocked_ips`;
DROP TABLE IF EXISTS `email_otps`;
DROP TABLE IF EXISTS `failed_login_attempts`;
DROP TABLE IF EXISTS `owner_notifications`;
DROP TABLE IF EXISTS `owner_activity_logs`;
DROP TABLE IF EXISTS `owner_users`;
DROP TABLE IF EXISTS `security_logs`;
DROP TABLE IF EXISTS `users`;

-- ========================================================
-- TABLE: users
-- ========================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_blocked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `business_name`, `mobile`, `address`, `created_at`, `updated_at`, `is_blocked`) VALUES
(4, 'Bizinote ', 'ffprince761@gmail.comf', '$2y$10$EyzADNSYJkxg3bANv864be1iNG2C01Qj3s1/IiYcp.H1B2HUc21Dm', 'Bizinote ', '7608081767', '', '2026-02-03 05:52:02', '2026-02-03 06:38:59', 0),
(7, 'Prince ', 'yourmadman03@gmail.com', '$2y$10$KPfWcUHoqdk6dqQFD7MAuemGC/qRpAaFF9QVoRsdmhrCVZ89ggdUi', 'Prince store ', '6369969797', '', '2026-02-06 05:09:23', '2026-02-06 05:09:23', 0);

-- ========================================================
-- TABLE: customers
-- ========================================================
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customers` (`id`, `user_id`, `name`, `mobile`, `email`, `address`, `created_at`, `updated_at`) VALUES
(5, 4, 'Cutie ', '1234567890', 'ffprince761@gmail.comf', 'Admin 678', '2026-02-03 05:52:35', '2026-02-03 05:52:35');

-- ========================================================
-- TABLE: products
-- ========================================================
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT 'Nos',
  `low_stock_threshold` int(11) DEFAULT 10,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`id`, `user_id`, `name`, `category`, `price`, `stock`, `unit`, `low_stock_threshold`, `description`, `created_at`, `updated_at`) VALUES
(7, 4, 'Samsung', 'Mobile Accessories', 19000.00, 8, 'Nos', 10, 'A17', '2026-02-03 05:53:07', '2026-02-14 14:29:42');

-- ========================================================
-- TABLE: bills
-- ========================================================
CREATE TABLE `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_mobile` varchar(20) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `items` longtext DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT NULL,
  `grand_total` decimal(10,2) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `due_status` varchar(20) DEFAULT 'paid',
  `due_paid_date` date DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bills` (`id`, `user_id`, `invoice_number`, `customer_id`, `customer_name`, `customer_mobile`, `customer_email`, `customer_address`, `items`, `subtotal`, `discount`, `tax`, `total`, `grand_total`, `payment_mode`, `date`, `created_at`, `updated_at`, `due_status`, `due_paid_date`, `paid_amount`, `due_date`) VALUES
(25, 4, '03022026001', 5, 'Cutie ', '1234567890', 'ffprince761@gmail.comf', 'Admin 678', '[{\"productId\":7,\"name\":\"Samsung\",\"price\":\"19000.00\",\"quantity\":10,\"unit\":\"Nos\",\"stock\":50}]', 190000.00, 90000.00, 0.00, 100000.00, 100000.00, 'Card', '2026-02-03', '2026-02-03 05:53:53', '2026-02-03 05:53:53', 'paid', NULL, 0.00, NULL),
(26, 4, '05022026002', 5, 'Cutie ', '1234567890', 'ffprince761@gmail.comf', 'Admin 678', '[{\"productId\":\"manual_1770268440664\",\"name\":\"Potato \",\"price\":50,\"quantity\":10,\"unit\":\"Kg\",\"stock\":999,\"isManual\":true}]', 500.00, 0.00, 0.00, 500.00, 500.00, 'UPI', '2026-02-05', '2026-02-05 05:14:08', '2026-02-05 05:14:08', 'paid', NULL, 0.00, NULL),
(27, 4, '13022026027', 5, 'Cutie ', '1234567890', 'ffprince761@gmail.comf', 'Admin 678', '[{\"productId\":7,\"name\":\"Samsung\",\"price\":\"19000.00\",\"quantity\":1,\"unit\":\"Nos\",\"stock\":10}]', 19000.00, 9000.00, 0.00, 10000.00, 10000.00, 'Due', '2026-02-13', '2026-02-13 16:24:46', '2026-02-13 19:39:20', 'paid', '2026-02-13', 10000.00, '2026-02-20'),
(28, 4, '14022026004', 5, 'Cutie ', '1234567890', 'ffprince761@gmail.comf', 'Admin 678', '[{\"productId\":7,\"name\":\"Samsung\",\"price\":\"19000.00\",\"quantity\":1,\"unit\":\"Nos\",\"stock\":9}]', 19000.00, 0.00, 0.00, 19000.00, 19000.00, 'Cash', '2026-02-14', '2026-02-14 14:29:42', '2026-02-14 14:29:42', 'paid', NULL, 0.00, NULL);

-- ========================================================
-- TABLE: bill_items
-- ========================================================
CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `price` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE: expenses
-- ========================================================
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `expenses` (`id`, `user_id`, `category`, `description`, `amount`, `date`, `created_at`, `updated_at`) VALUES
(5, 4, 'Rent', 'Every month ', 10000.00, '2026-02-03', '2026-02-03 05:55:00', '2026-02-03 05:55:00');

-- ========================================================
-- TABLE: backups
-- ========================================================
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `backup_name` varchar(255) NOT NULL,
  `backup_data` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE: stock_adjustments
-- ========================================================
CREATE TABLE `stock_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date` date NOT NULL,
  `note` varchar(255) DEFAULT 'Stock Added',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_adjustments` (`id`, `product_id`, `user_id`, `quantity`, `date`, `note`, `created_at`) VALUES
(1, 4, 3, 10, '2026-01-31', 'Stock Added', '2026-01-31 13:37:44'),
(2, 4, 3, 10, '2026-01-31', 'Stock Added', '2026-01-31 13:39:09'),
(3, 4, 3, 10, '2026-01-31', 'Stock Added', '2026-01-31 13:44:41'),
(4, 4, 3, 20, '2026-01-31', 'Stock Added', '2026-01-31 13:50:16'),
(5, 4, 3, 20, '2026-01-31', 'Stock Added', '2026-01-31 13:58:58');

-- ========================================================
-- TABLE: udhari_payments
-- ========================================================
CREATE TABLE `udhari_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bill_id` (`bill_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `udhari_payments` (`id`, `bill_id`, `user_id`, `amount`, `payment_date`, `note`, `created_at`) VALUES
(1, 27, 4, 5000.00, '2026-02-13', 'Monday', '2026-02-13 16:36:25'),
(2, 27, 4, 5000.00, '2026-02-13', 'Full payment', '2026-02-13 17:22:55');

-- ========================================================
-- TABLE: user_plans
-- ========================================================
CREATE TABLE `user_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_type` enum('free','paid') DEFAULT 'free',
  `bill_limit` int(11) DEFAULT 500,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_plans` (`id`, `user_id`, `plan_type`, `bill_limit`, `created_at`, `updated_at`) VALUES
(1, 4, 'free', 4, '2026-02-14 13:34:00', '2026-02-14 14:57:11');

-- ========================================================
-- TABLE: active_sessions
-- ========================================================
CREATE TABLE `active_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `device_info` varchar(500) DEFAULT NULL,
  `app_screen` varchar(100) DEFAULT 'Home',
  `last_ping` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `session_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE: app_analytics
-- ========================================================
CREATE TABLE `app_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE: app_error_logs
-- ========================================================
CREATE TABLE `app_error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `device_info` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE: app_settings
-- ========================================================
CREATE TABLE `app_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=882 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `app_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(70, 'support_email', 'Binestmanage@gmail.com', '2026-02-13 17:37:17'),
(71, 'support_phone', 'NIL', '2026-02-13 17:37:17'),
(72, 'app_version', '1.0.0', '2026-02-02 06:36:07'),
(687, 'billing_limit_enabled', '0', '2026-02-14 14:58:24'),
(688, 'default_bill_limit', '500', '2026-02-14 13:33:31');

-- ========================================================
-- TABLE: blocked_ips
-- ========================================================
CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `blocked_until` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `idx_ip_blocked` (`ip_address`,`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE: email_otps
-- ========================================================
CREATE TABLE `email_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` enum('registration','password_reset') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_otp` (`otp`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_otps` (`id`, `email`, `otp`, `purpose`, `created_at`, `expires_at`, `verified`) VALUES
(3, 'yourmadman03@gmail.com', '397636', 'registration', '2026-02-06 05:08:58', '2026-02-06 06:18:58', 1),
(5, 'yourmadman03@gmail.com', '407764', 'password_reset', '2026-02-06 05:12:10', '2026-02-06 06:22:10', 1),
(6, 'ffprince761@gmail.comf', '160238', 'password_reset', '2026-02-13 18:39:11', '2026-02-13 19:49:11', 0);

-- ========================================================
-- TABLE: failed_login_attempts
-- ========================================================
CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_time` (`email`,`attempt_time`),
  KEY `idx_ip_time` (`ip_address`,`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE: owner_users
-- ========================================================
CREATE TABLE `owner_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `owner_users` (`id`, `username`, `email`, `password`, `full_name`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Madman', 'owner@bizinote.com', '$2y$10$gD.55U45CuKOjFxU.zCGv.OJ3xdqCo8IOjNdQTleTPZTZC9pDz5He', 'Bizinote Owner', 1, '2026-02-14 14:56:55', '2026-02-02 04:17:38', '2026-02-14 14:56:55');

-- ========================================================
-- TABLE: owner_activity_logs
-- ========================================================
CREATE TABLE `owner_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `owner_activity_logs_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owner_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `owner_activity_logs` (`id`, `owner_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'block_user', 'Blocked user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 05:12:26'),
(2, 1, 'unblock_user', 'Unblocked user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 05:12:36'),
(3, 1, 'delete_user', 'Deleted user: Test User (test@bizinote.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 05:12:47'),
(49, 1, 'login', 'Successful login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-13 17:10:53'),
(56, 1, 'login', 'Successful login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-14 14:56:55');

-- ========================================================
-- TABLE: owner_notifications
-- ========================================================
CREATE TABLE `owner_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','urgent') DEFAULT 'info',
  `target` enum('all','specific') DEFAULT 'all',
  `target_user_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_target` (`target_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `owner_notifications` (`id`, `title`, `message`, `type`, `target`, `target_user_id`, `created_by`, `is_active`, `created_at`) VALUES
(2, 'sucsess', 'hii', 'success', 'specific', 4, 1, 0, '2026-02-14 14:00:28');

-- ========================================================
-- TABLE: security_logs
-- ========================================================
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip_address`,`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `security_logs` (`id`, `ip_address`, `action`, `success`, `user_id`, `user_agent`, `created_at`) VALUES
(33, '10.181.83.37', 'login', 1, 4, 'okhttp/4.12.0', '2026-02-13 15:41:26'),
(34, '10.181.83.37', 'login', 1, 4, 'okhttp/4.12.0', '2026-02-13 18:39:24');

-- ========================================================
-- VIEWS (using CURRENT_USER instead of root@localhost)
-- ========================================================

CREATE VIEW `owner_daily_activity` AS
SELECT
  CAST(`all_activity`.`created_at` AS DATE) AS `activity_date`,
  COUNT(DISTINCT `all_activity`.`user_id`) AS `active_users`,
  COUNT(0) AS `total_actions`
FROM (
  SELECT `user_id`, `created_at` FROM `bills`
  UNION ALL
  SELECT `user_id`, `created_at` FROM `products`
  UNION ALL
  SELECT `user_id`, `created_at` FROM `customers`
  UNION ALL
  SELECT `user_id`, `created_at` FROM `expenses`
) AS `all_activity`
GROUP BY CAST(`all_activity`.`created_at` AS DATE)
ORDER BY CAST(`all_activity`.`created_at` AS DATE) DESC;

CREATE VIEW `owner_revenue_summary` AS
SELECT
  CAST(`bills`.`date` AS DATE) AS `revenue_date`,
  COUNT(0) AS `bill_count`,
  SUM(`bills`.`grand_total`) AS `total_revenue`,
  AVG(`bills`.`grand_total`) AS `avg_bill_value`,
  COUNT(DISTINCT `bills`.`user_id`) AS `active_businesses`
FROM `bills`
GROUP BY CAST(`bills`.`date` AS DATE)
ORDER BY CAST(`bills`.`date` AS DATE) DESC;

CREATE VIEW `owner_user_stats` AS
SELECT
  `u`.`id` AS `id`,
  `u`.`name` AS `name`,
  `u`.`email` AS `email`,
  `u`.`business_name` AS `business_name`,
  `u`.`created_at` AS `registered_at`,
  `u`.`updated_at` AS `last_active`,
  COUNT(DISTINCT `p`.`id`) AS `product_count`,
  COUNT(DISTINCT `c`.`id`) AS `customer_count`,
  COUNT(DISTINCT `b`.`id`) AS `bill_count`,
  COALESCE(SUM(`b`.`grand_total`), 0) AS `total_revenue`,
  COUNT(DISTINCT `e`.`id`) AS `expense_count`,
  COALESCE(SUM(`e`.`amount`), 0) AS `total_expenses`
FROM `users` `u`
LEFT JOIN `products` `p` ON `u`.`id` = `p`.`user_id`
LEFT JOIN `customers` `c` ON `u`.`id` = `c`.`user_id`
LEFT JOIN `bills` `b` ON `u`.`id` = `b`.`user_id`
LEFT JOIN `expenses` `e` ON `u`.`id` = `e`.`user_id`
GROUP BY `u`.`id`;

-- ========================================================
-- Re-enable foreign key checks
-- ========================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- DONE! bizinote_db ready for Hostinger.
-- =============================================
