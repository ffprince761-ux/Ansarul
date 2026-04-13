<?php
/**
 * Test Owner Login - Check Database
 */
require_once 'config/db.php';

echo "<h2>Owner Login Test</h2>";

try {
    // Check if owner_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'owner_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ owner_users table exists</p>";
        
        // Get all owner users
        $stmt = $pdo->query("SELECT id, username, email, full_name, is_active, created_at FROM owner_users");
        $owners = $stmt->fetchAll();
        
        if (count($owners) > 0) {
            echo "<h3>Owner Users:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Active</th><th>Created</th></tr>";
            
            foreach ($owners as $owner) {
                $active = $owner['is_active'] ? '✅ Yes' : '❌ No';
                echo "<tr>";
                echo "<td>{$owner['id']}</td>";
                echo "<td><strong>{$owner['username']}</strong></td>";
                echo "<td>{$owner['email']}</td>";
                echo "<td>{$owner['full_name']}</td>";
                echo "<td>{$active}</td>";
                echo "<td>{$owner['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<hr>";
            echo "<h3>Default Login Credentials:</h3>";
            echo "<p><strong>Username:</strong> owner</p>";
            echo "<p><strong>Password:</strong> owner123</p>";
            
        } else {
            echo "<p style='color: red;'>❌ No owner users found in database</p>";
            echo "<p>Creating default owner user...</p>";
            
            // Create default owner
            $username = 'owner';
            $email = 'owner@bizinote.com';
            $password = password_hash('owner123', PASSWORD_DEFAULT);
            $fullName = 'Bizinote Owner';
            
            $stmt = $pdo->prepare("INSERT INTO owner_users (username, email, password, full_name, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$username, $email, $password, $fullName]);
            
            echo "<p style='color: green;'>✅ Default owner created successfully!</p>";
            echo "<p><strong>Username:</strong> owner</p>";
            echo "<p><strong>Password:</strong> owner123</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ owner_users table does NOT exist</p>";
        echo "<p>Please run setup_owner_tables.sql first</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
?>
