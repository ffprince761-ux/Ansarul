<?php
require_once 'config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS stock_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    quantity INT NOT NULL,
    date DATE NOT NULL,
    note VARCHAR(255) DEFAULT 'Stock Added',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Stock adjustments table created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
