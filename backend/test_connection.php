<?php
require_once 'config.php';

echo "Database Connection Test\n";
echo "====================\n";
echo "Host: " . $host . "\n";
echo "Database: " . $dbname . "\n";
echo "Username: " . $username . "\n";
echo "Connection Status: ";

if ($conn->ping()) {
    echo "✅ CONNECTED\n";
    echo "Database is ready for use!\n";
} else {
    echo "❌ FAILED\n";
    echo "Error: " . $conn->error . "\n";
}

// Test table creation
echo "\nTesting Tables:\n";
$tables = ['users', 'products', 'customers', 'bills', 'expenses'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ $table table exists\n";
    } else {
        echo "❌ $table table missing\n";
    }
}

$conn->close();
?>
