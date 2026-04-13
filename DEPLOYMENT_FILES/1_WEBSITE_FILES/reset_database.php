<?php
require_once 'config.php';

echo "<h2>Database Reset</h2>";

// Drop all tables first
$drop_queries = [
    "DROP TABLE IF EXISTS backups",
    "DROP TABLE IF EXISTS expenses",
    "DROP TABLE IF EXISTS bills",
    "DROP TABLE IF EXISTS customers",
    "DROP TABLE IF EXISTS products",
    "DROP TABLE IF EXISTS users"
];

echo "<h3>Dropping old tables...</h3>";
foreach ($drop_queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "✅ Table dropped successfully<br>";
    } else {
        echo "⚠️ " . $conn->error . "<br>";
    }
}

// Create tables with correct structure
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
        invoice_number VARCHAR(50) NOT NULL,
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Expenses table
    "CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category VARCHAR(100) NOT NULL,
        description TEXT,
        amount DECIMAL(10,2) NOT NULL,
        date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Backups table
    "CREATE TABLE IF NOT EXISTS backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        backup_name VARCHAR(255) NOT NULL,
        backup_data LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

echo "<h3>Creating new tables...</h3>";
foreach ($sql_queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "✅ Table created successfully<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

echo "<h3>✅ Database reset completed!</h3>";
echo "<p><a href='test_insert.php'>Click here to insert test data</a></p>";

$conn->close();
?>
