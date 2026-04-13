<?php
/**
 * Test Database Connection and Owner Tables
 */

// Database credentials
$host = "localhost";
$dbname = "bizinote";
$username = "root";
$password = "";

echo "<h2>Testing Owner Panel Database Setup</h2>";

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if owner_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'owner_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ owner_users table exists</p>";
        
        // Check if default owner exists
        $stmt = $pdo->query("SELECT * FROM owner_users WHERE username = 'owner'");
        $owner = $stmt->fetch();
        
        if ($owner) {
            echo "<p style='color: green;'>✅ Default owner account exists</p>";
            echo "<pre>";
            echo "Username: " . $owner['username'] . "\n";
            echo "Email: " . $owner['email'] . "\n";
            echo "Password Hash: " . substr($owner['password'], 0, 20) . "...\n";
            echo "Is Active: " . ($owner['is_active'] ? 'Yes' : 'No') . "\n";
            echo "</pre>";
            
            // Test password verification
            $testPassword = 'owner123';
            if (password_verify($testPassword, $owner['password'])) {
                echo "<p style='color: green;'>✅ Password verification works! Password 'owner123' is correct.</p>";
            } else {
                echo "<p style='color: red;'>❌ Password verification failed! The password hash might be incorrect.</p>";
                
                // Create new hash
                $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                echo "<p>New hash for 'owner123': <code>$newHash</code></p>";
                
                // Update password
                $stmt = $pdo->prepare("UPDATE owner_users SET password = ? WHERE username = 'owner'");
                $stmt->execute([$newHash]);
                echo "<p style='color: green;'>✅ Password updated! Try logging in again.</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Default owner account NOT found!</p>";
            echo "<p>Creating default owner account...</p>";
            
            // Create default owner
            $hash = password_hash('owner123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO owner_users (username, email, password, full_name) 
                VALUES ('owner', 'owner@bizinote.com', ?, 'Bizinote Owner')
            ");
            $stmt->execute([$hash]);
            echo "<p style='color: green;'>✅ Default owner account created!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ owner_users table does NOT exist!</p>";
        echo "<p>Please import the SQL file: <code>owner/setup_owner_tables.sql</code></p>";
    }
    
    // Check other tables
    echo "<h3>Other Tables Status:</h3>";
    $tables = ['owner_activity_logs', 'app_analytics'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ $table exists</p>";
        } else {
            echo "<p style='color: red;'>❌ $table does NOT exist</p>";
        }
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
?>
