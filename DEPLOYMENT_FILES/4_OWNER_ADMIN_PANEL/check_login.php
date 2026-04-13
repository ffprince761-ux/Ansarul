<?php
/**
 * Login Verification Tool
 * This will test if login credentials work
 */

$host = "localhost";
$username = "root";
$password = "";
$dbname = "bizinote";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Checker</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { color: blue; background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 5px; }
        code { background: #f4f4f4; padding: 2px 6px; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Login Verification</h1>

<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Database connected</div>";
    
    // Check if owner_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'owner_users'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ owner_users table exists</div>";
        
        // Get all users
        $stmt = $pdo->query("SELECT id, username, email, is_active, created_at FROM owner_users");
        $users = $stmt->fetchAll();
        
        echo "<h3>📋 Registered Accounts:</h3>";
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Active</th><th>Created</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td><strong>" . $user['username'] . "</strong></td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . $user['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Test password for 'owner' account
            echo "<h3>🔐 Password Test:</h3>";
            $stmt = $pdo->query("SELECT * FROM owner_users WHERE username = 'owner'");
            $owner = $stmt->fetch();
            
            if ($owner) {
                $testPassword = 'owner123';
                if (password_verify($testPassword, $owner['password'])) {
                    echo "<div class='success'>";
                    echo "✅ Password verification WORKS!<br>";
                    echo "Username: <code>owner</code><br>";
                    echo "Password: <code>owner123</code><br>";
                    echo "<strong>Login should work with these credentials!</strong>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "❌ Password verification FAILED!<br>";
                    echo "The password hash in database is incorrect.<br>";
                    echo "<strong>Fixing now...</strong>";
                    echo "</div>";
                    
                    // Fix the password
                    $newHash = password_hash('owner123', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE owner_users SET password = ? WHERE username = 'owner'");
                    $stmt->execute([$newHash]);
                    
                    echo "<div class='success'>✅ Password has been reset! Try logging in again.</div>";
                }
            } else {
                echo "<div class='error'>❌ 'owner' account not found!</div>";
            }
            
        } else {
            echo "<div class='error'>❌ No users found in database!</div>";
            echo "<div class='info'>Run install.php to create default account</div>";
        }
        
    } else {
        echo "<div class='error'>❌ owner_users table does NOT exist!</div>";
        echo "<div class='info'>Run install.php to create tables</div>";
    }
    
    // Check session configuration
    echo "<h3>⚙️ PHP Session Configuration:</h3>";
    echo "<div class='info'>";
    echo "Session Save Path: <code>" . session_save_path() . "</code><br>";
    echo "Session Name: <code>" . session_name() . "</code><br>";
    echo "Session Status: <code>" . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</code>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}
?>

<hr>
<a href="index.php" class="btn">Go to Login Page</a>
<a href="install.php" class="btn">Run Installer</a>

</div>
</body>
</html>
