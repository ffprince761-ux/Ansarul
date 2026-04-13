<?php
require_once 'config.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        business_name VARCHAR(255),
        mobile VARCHAR(20),
        address TEXT,
        is_blocked TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        action VARCHAR(50) NOT NULL,
        success TINYINT(1) DEFAULT 0,
        user_id INT NULL,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS blocked_ips (
        ip_address VARCHAR(45) PRIMARY KEY,
        blocked_until TIMESTAMP NOT NULL,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS email_otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        otp VARCHAR(10) NOT NULL,
        purpose VARCHAR(50) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        price DECIMAL(10, 2) NOT NULL,
        stock INT DEFAULT 0,
        unit VARCHAR(20),
        low_stock_threshold INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category VARCHAR(100) NOT NULL,
        description TEXT,
        amount DECIMAL(10, 2) NOT NULL,
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS bills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        invoice_number VARCHAR(50) NOT NULL,
        customer_id INT NULL,
        customer_name VARCHAR(100) NOT NULL,
        customer_mobile VARCHAR(20),
        customer_email VARCHAR(100),
        customer_address TEXT,
        items JSON,
        subtotal DECIMAL(10, 2),
        discount DECIMAL(10, 2),
        tax DECIMAL(10, 2),
        total DECIMAL(10, 2),
        grand_total DECIMAL(10, 2),
        payment_mode VARCHAR(50),
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS app_error_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        error_message TEXT NOT NULL,
        stack_trace TEXT NULL,
        device_info TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $sql) {
    try {
        if ($conn->query($sql) === TRUE) {
            echo "Query executed successfully\n";
        } else {
            echo "Error executing query: " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

echo "Database initialization complete.\n";
?>
