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

// Add unit column to products table
$sql = "ALTER TABLE products ADD COLUMN IF NOT EXISTS unit VARCHAR(20) DEFAULT 'Nos' AFTER stock";

if ($conn->query($sql) === TRUE) {
    echo "✅ Unit column added successfully to products table!<br>";
    
    // Update existing products to have 'Nos' as default unit
    $updateSql = "UPDATE products SET unit = 'Nos' WHERE unit IS NULL OR unit = ''";
    if ($conn->query($updateSql) === TRUE) {
        echo "✅ Existing products updated with default unit 'Nos'!<br>";
    } else {
        echo "⚠️ Note: " . $conn->error . "<br>";
    }
} else {
    echo "❌ Error adding column: " . $conn->error . "<br>";
}

$conn->close();
?>
