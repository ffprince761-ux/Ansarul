<?php
/**
 * Quick Setup Script for Owner Panel
 * Run this once to create all necessary tables and default owner account
 */

$host = "localhost";
$dbname = "bizinote";
$username = "root";
$password = "";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Owner Panel Setup</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: green; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 Owner Panel Setup</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Connected to database: <strong>$dbname</strong></div>";
    
    // Create owner_users table
    echo "<h3>Creating Tables...</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS owner_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ Table <code>owner_users</code> created</div>";
    
    // Create owner_activity_logs table
    $sql = "CREATE TABLE IF NOT EXISTS owner_activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES owner_users(id) ON DELETE CASCADE,
        INDEX idx_owner_id (owner_id),
        INDEX idx_action (action),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ Table <code>owner_activity_logs</code> created</div>";
    
    // Create app_analytics table
    $sql = "CREATE TABLE IF NOT EXISTS app_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_name VARCHAR(100) NOT NULL,
        metric_value VARCHAR(255),
        user_id INT NULL,
        metadata JSON,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_metric (metric_name, recorded_at),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<div class='success'>✅ Table <code>app_analytics</code> created</div>";
    
    // Check if default owner exists
    echo "<h3>Creating Default Owner Account...</h3>";
    
    $stmt = $pdo->query("SELECT * FROM owner_users WHERE username = 'owner'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='info'>ℹ️ Default owner account already exists</div>";
        
        // Update password to ensure it's correct
        $hash = password_hash('owner123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE owner_users SET password = ? WHERE username = 'owner'");
        $stmt->execute([$hash]);
        echo "<div class='success'>✅ Password reset to <code>owner123</code></div>";
    } else {
        // Create default owner
        $hash = password_hash('owner123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO owner_users (username, email, password, full_name) 
            VALUES ('owner', 'owner@bizinote.com', ?, 'Bizinote Owner')
        ");
        $stmt->execute([$hash]);
        echo "<div class='success'>✅ Default owner account created</div>";
    }
    
    echo "<h3>📋 Login Credentials:</h3>";
    echo "<div class='info'>";
    echo "<strong>Username:</strong> <code>owner</code><br>";
    echo "<strong>Password:</strong> <code>owner123</code><br>";
    echo "<strong>URL:</strong> <a href='index.php'>http://localhost/bizinote/owner/</a>";
    echo "</div>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>All tables have been created and the default owner account is ready.</p>";
    echo "<a href='index.php' class='btn'>Go to Login Page</a>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL is running</li>";
    echo "<li>Database '<strong>bizinote</strong>' exists</li>";
    echo "<li>Database credentials are correct</li>";
    echo "</ul>";
}

echo "</div></body></html>";
?>
