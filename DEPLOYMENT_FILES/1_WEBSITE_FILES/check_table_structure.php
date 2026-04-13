<?php
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'bizinote_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = ['customers', 'bills', 'products'];
    
    foreach ($tables as $table) {
        echo "\n========== $table TABLE STRUCTURE ==========\n";
        $stmt = $conn->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            echo sprintf("%-20s %-20s %-10s %-10s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Key']
            );
        }
        echo "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
