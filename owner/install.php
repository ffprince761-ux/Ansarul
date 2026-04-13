<?php
/**
 * ONE-CLICK INSTALLER FOR OWNER PANEL
 * Just open this file in browser and it will setup everything
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "bizinote";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Owner Panel Installer</title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .step {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .center {
            text-align: center;
        }
        pre {
            background: #2d3748;
            color: #fff;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 Owner Panel Installer</h1>
    <p class="subtitle">Automatic Setup for Bizinote Owner Monitoring Panel</p>

<?php
$errors = [];
$success = [];

// Step 1: Check MySQL connection
echo "<div class='step'><strong>Step 1:</strong> Checking MySQL connection...</div>";

try {
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        $errors[] = "MySQL connection failed: " . $conn->connect_error;
        echo "<div class='step error'>❌ MySQL connection failed!</div>";
        echo "<div class='step warning'><strong>Fix:</strong> Make sure XAMPP MySQL is running</div>";
    } else {
        echo "<div class='step success'>✅ MySQL connection successful</div>";
        
        // Step 2: Check/Create database
        echo "<div class='step'><strong>Step 2:</strong> Checking database...</div>";
        
        $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
        if ($result->num_rows == 0) {
            // Create database
            if ($conn->query("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                echo "<div class='step success'>✅ Database '$dbname' created</div>";
            } else {
                $errors[] = "Failed to create database";
                echo "<div class='step error'>❌ Failed to create database</div>";
            }
        } else {
            echo "<div class='step success'>✅ Database '$dbname' exists</div>";
        }
        
        // Select database
        $conn->select_db($dbname);
        
        // Step 3: Create tables
        echo "<div class='step'><strong>Step 3:</strong> Creating tables...</div>";
        
        // Drop existing tables
        $conn->query("DROP TABLE IF EXISTS owner_activity_logs");
        $conn->query("DROP TABLE IF EXISTS app_analytics");
        $conn->query("DROP TABLE IF EXISTS owner_users");
        
        // Create owner_users table
        $sql = "CREATE TABLE owner_users (
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
        
        if ($conn->query($sql)) {
            echo "<div class='step success'>✅ Table 'owner_users' created</div>";
        } else {
            $errors[] = "Failed to create owner_users table";
            echo "<div class='step error'>❌ Failed to create owner_users table</div>";
        }
        
        // Create owner_activity_logs table
        $sql = "CREATE TABLE owner_activity_logs (
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
        
        if ($conn->query($sql)) {
            echo "<div class='step success'>✅ Table 'owner_activity_logs' created</div>";
        } else {
            $errors[] = "Failed to create owner_activity_logs table";
        }
        
        // Create app_analytics table
        $sql = "CREATE TABLE app_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            metric_name VARCHAR(100) NOT NULL,
            metric_value VARCHAR(255),
            user_id INT NULL,
            metadata JSON,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_metric (metric_name, recorded_at),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            echo "<div class='step success'>✅ Table 'app_analytics' created</div>";
        } else {
            $errors[] = "Failed to create app_analytics table";
        }
        
        // Step 4: Create default owner account
        echo "<div class='step'><strong>Step 4:</strong> Creating default owner account...</div>";
        
        $passwordHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // owner123
        
        $sql = "INSERT INTO owner_users (username, email, password, full_name, is_active) 
                VALUES ('owner', 'owner@bizinote.com', '$passwordHash', 'Bizinote Owner', 1)";
        
        if ($conn->query($sql)) {
            echo "<div class='step success'>✅ Default owner account created</div>";
            $success[] = "Installation completed successfully!";
        } else {
            $errors[] = "Failed to create owner account";
            echo "<div class='step error'>❌ Failed to create owner account</div>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    $errors[] = $e->getMessage();
    echo "<div class='step error'>❌ Error: " . $e->getMessage() . "</div>";
}

// Show results
if (empty($errors)) {
    echo "<div class='step success' style='margin-top: 30px; padding: 20px;'>";
    echo "<h2 style='color: #28a745; margin-bottom: 15px;'>🎉 Installation Successful!</h2>";
    echo "<p><strong>Your Owner Panel is ready to use!</strong></p>";
    echo "</div>";
    
    echo "<div class='step info'>";
    echo "<h3>📋 Login Credentials:</h3>";
    echo "<p><strong>URL:</strong> <a href='index.php'>http://localhost/bizinote/owner/</a></p>";
    echo "<p><strong>Username:</strong> <code>owner</code></p>";
    echo "<p><strong>Password:</strong> <code>owner123</code></p>";
    echo "<p style='margin-top: 10px;'><small>⚠️ Change password after first login!</small></p>";
    echo "</div>";
    
    echo "<div class='center'>";
    echo "<a href='index.php' class='btn'>Go to Login Page →</a>";
    echo "</div>";
} else {
    echo "<div class='step error' style='margin-top: 30px;'>";
    echo "<h3>❌ Installation Failed</h3>";
    echo "<p>Please fix the following errors:</p>";
    echo "<ul style='margin-left: 20px; margin-top: 10px;'>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='step warning'>";
    echo "<h3>🔧 Troubleshooting:</h3>";
    echo "<ol style='margin-left: 20px;'>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check if port 3306 is not blocked</li>";
    echo "<li>Verify MySQL username is 'root' with no password</li>";
    echo "<li>Try refreshing this page</li>";
    echo "</ol>";
    echo "</div>";
}
?>

</div>
</body>
</html>
