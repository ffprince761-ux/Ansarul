<?php
require_once 'config.php';

// SQL to create tables
$sql_queries = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        business_name VARCHAR(255),
        mobile VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Products table
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        price DECIMAL(10,2),
        stock INT DEFAULT 0,
        low_stock_threshold INT DEFAULT 10,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Stock adjustments table
    "CREATE TABLE IF NOT EXISTS stock_adjustments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        quantity INT NOT NULL,
        date DATE NOT NULL,
        note VARCHAR(255) DEFAULT 'Stock Added',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Customers table
    "CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        mobile VARCHAR(20),
        email VARCHAR(255),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Bills table
    "CREATE TABLE IF NOT EXISTS bills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        invoice_number VARCHAR(50) UNIQUE NOT NULL,
        customer_id INT,
        customer_name VARCHAR(255),
        customer_mobile VARCHAR(20),
        customer_email VARCHAR(255),
        customer_address TEXT,
        items JSON,
        subtotal DECIMAL(10,2),
        discount DECIMAL(10,2) DEFAULT 0,
        tax DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2),
        grand_total DECIMAL(10,2),
        payment_mode VARCHAR(50),
        date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
    )",
    
    // Expenses table
    "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category VARCHAR(100),
        description TEXT,
        amount DECIMAL(10,2),
        date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

// Execute all queries
foreach ($sql_queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

echo "Database setup completed!";
$conn->close();
?>
