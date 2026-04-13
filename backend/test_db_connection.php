<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database configuration
$host = 'localhost';
$dbname = 'bizinote_db';
$username = 'root';
$password = '';

try {
    // Test database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'database' => $dbname
    ]);
    
    // Test if tables exist
    $tables = ['users', 'products', 'customers', 'bills', 'expenses'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
        }
    }
    
    echo "\n\nExisting tables: " . implode(', ', $existingTables);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
