<?php
// Direct database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bizinote_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create stock_adjustments table
$sql = "CREATE TABLE IF NOT EXISTS stock_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    quantity INT NOT NULL,
    date DATE NOT NULL,
    note VARCHAR(255) DEFAULT 'Stock Added',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Stock adjustments table created successfully!<br>";
    echo "Now you can use Update Stock feature in the app.<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

$conn->close();
?>
