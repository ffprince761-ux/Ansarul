<?php
/**
 * Create OTP Table in Database
 * Run this file once to create the email_otps table
 */

// Database configuration
$host = 'localhost';
$dbname = 'bizinote_db';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Creating OTP Table...</h2>";
    
    // Drop table if exists (for clean creation)
    $pdo->exec("DROP TABLE IF EXISTS email_otps");
    echo "<p style='color: orange;'>⚠️ Dropped existing table (if any)</p>";
    
    // Create table with proper syntax
    $sql = "CREATE TABLE email_otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(6) NOT NULL,
        purpose ENUM('registration', 'password_reset') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        INDEX idx_email (email),
        INDEX idx_otp (otp),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Table 'email_otps' created successfully!</p>";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_otps'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table verified in database</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE email_otps");
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background: #2563EB; color: white;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
        echo "<h3 style='color: #2e7d32;'>✅ OTP System Ready!</h3>";
        echo "<p><strong>Next Step:</strong> Configure Gmail App Password in EmailService.php</p>";
        echo "<p><strong>File:</strong> <code>backend/email/EmailService.php</code></p>";
        echo "<p><strong>Update Lines 32-33 and 39</strong> with your Gmail credentials</p>";
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p style='color: orange;'>⚠️ Make sure MySQL is running and database 'bizinote_db' exists</p>";
}
?>
