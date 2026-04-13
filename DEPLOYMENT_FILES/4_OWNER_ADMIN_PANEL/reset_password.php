<?php
/**
 * Reset Owner Password
 */
require_once 'config/db.php';

echo "<h2>Reset Owner Password</h2>";

try {
    // Check if owner user exists
    $stmt = $pdo->query("SELECT id, username, email FROM owner_users WHERE username = 'owner'");
    $owner = $stmt->fetch();
    
    if ($owner) {
        echo "<p style='color: green;'>✅ Owner user found</p>";
        echo "<p><strong>Username:</strong> {$owner['username']}</p>";
        echo "<p><strong>Email:</strong> {$owner['email']}</p>";
        
        // Reset password to 'owner123'
        $newPassword = 'owner123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE owner_users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $owner['id']]);
        
        echo "<hr>";
        echo "<h3 style='color: green;'>✅ Password Reset Successfully!</h3>";
        echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4>New Login Credentials:</h4>";
        echo "<p style='font-size: 18px;'><strong>Username:</strong> <code style='background: #fff; padding: 5px 10px; border-radius: 5px;'>owner</code></p>";
        echo "<p style='font-size: 18px;'><strong>Password:</strong> <code style='background: #fff; padding: 5px 10px; border-radius: 5px;'>owner123</code></p>";
        echo "</div>";
        
        echo "<p style='color: blue;'>ℹ️ Password has been reset. You can now login with the above credentials.</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Owner user not found</p>";
        echo "<p>Creating new owner user...</p>";
        
        // Create new owner
        $username = 'owner';
        $email = 'owner@bizinote.com';
        $password = password_hash('owner123', PASSWORD_DEFAULT);
        $fullName = 'Bizinote Owner';
        
        $stmt = $pdo->prepare("INSERT INTO owner_users (username, email, password, full_name, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$username, $email, $password, $fullName]);
        
        echo "<h3 style='color: green;'>✅ Owner User Created!</h3>";
        echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4>Login Credentials:</h4>";
        echo "<p style='font-size: 18px;'><strong>Username:</strong> <code style='background: #fff; padding: 5px 10px; border-radius: 5px;'>owner</code></p>";
        echo "<p style='font-size: 18px;'><strong>Password:</strong> <code style='background: #fff; padding: 5px 10px; border-radius: 5px;'>owner123</code></p>";
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php' style='display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
?>
