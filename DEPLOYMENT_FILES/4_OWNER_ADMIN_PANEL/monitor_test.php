<?php
/**
 * Simple Monitoring Test - Debug Database Connection
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

echo "<h2>🔍 Database Connection Test</h2>";

// Test 1: Basic connection
echo "<h3>Test 1: Basic Connection</h3>";
try {
    // Try both local and production configs
    $configs = [
        'Local' => [
            'host' => 'localhost',
            'dbname' => 'bizinote_db',
            'username' => 'root',
            'password' => ''
        ],
        'Production' => [
            'host' => 'localhost', // Change to Hostinger host
            'dbname' => 'u123456789_bizinote', // Change to Hostinger DB
            'username' => 'u123456789_owner', // Change to Hostinger user
            'password' => 'your_password' // Change to Hostinger password
        ]
    ];
    
    foreach ($configs as $name => $config) {
        echo "<p><strong>Testing $name config:</strong><br>";
        try {
            $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
                          $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<span style='color: green;'>✅ Connected successfully</span><br>";
            
            // Test basic query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch()['count'];
            echo "Users count: $count<br>";
            
        } catch(PDOException $e) {
            echo "<span style='color: red;'>❌ Failed: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
        echo "</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Check if required tables exist
echo "<h3>Test 2: Check Required Tables</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=bizinote_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $requiredTables = ['users', 'products', 'customers', 'bills', 'expenses', 'owner_users', 'security_logs'];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $status = $exists ? "✅ Exists" : "❌ Missing";
        echo "<p>$table: $status</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Table check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: PHP Info
echo "<h3>Test 3: PHP Environment</h3>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Timezone: " . date_default_timezone_get() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PDO MySQL Support: " . (extension_loaded('pdo_mysql') ? '✅ Yes' : '❌ No') . "</p>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Update database credentials in <code>config/db_production.php</code></li>";
echo "<li>Rename <code>db.php</code> to <code>db_local.php</code></li>";
echo "<li>Rename <code>db_production.php</code> to <code>db.php</code></li>";
echo "<li>Test again with <code>system.php</code></li>";
echo "</ol>";
?>
