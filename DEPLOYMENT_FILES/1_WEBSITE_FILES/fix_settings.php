<?php
/**
 * Direct Fix - Insert/Update App Settings
 */

$host = "localhost";
$username = "root";
$password = "";
$dbname = "bizinote_db";

// Your updated settings
$supportEmail = "admin@biswamart.com";
$supportPhone = "+91 7608081767";
$appVersion = "1.0.1";

echo "<h2>Fixing App Settings in bizinote_db</h2>";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Connected to database</p>";
    
    // Create table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS app_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Table created/verified</p>";
    }
    
    // Delete old settings
    $conn->query("DELETE FROM app_settings");
    echo "<p style='color: orange;'>🗑️ Cleared old settings</p>";
    
    // Insert new settings
    $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
    
    $stmt->bind_param("ss", $key, $value);
    
    $key = 'support_email';
    $value = $supportEmail;
    $stmt->execute();
    echo "<p style='color: green;'>✅ Inserted support_email: $supportEmail</p>";
    
    $key = 'support_phone';
    $value = $supportPhone;
    $stmt->execute();
    echo "<p style='color: green;'>✅ Inserted support_phone: $supportPhone</p>";
    
    $key = 'app_version';
    $value = $appVersion;
    $stmt->execute();
    echo "<p style='color: green;'>✅ Inserted app_version: $appVersion</p>";
    
    $stmt->close();
    
    // Verify
    echo "<hr><h3>Verification:</h3>";
    $result = $conn->query("SELECT * FROM app_settings");
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td><strong>" . $row['setting_key'] . "</strong></td><td>" . $row['setting_value'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Settings Fixed Successfully!</h3>";
    echo "<p><strong>Now:</strong></p>";
    echo "<ol>";
    echo "<li>Close your mobile app completely</li>";
    echo "<li>Reopen the app</li>";
    echo "<li>Go to Profile → Help & Support</li>";
    echo "<li>You should see the updated email and phone</li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<p><a href='api/get_app_settings.php' target='_blank' style='font-size: 18px; color: blue;'>🔗 Click here to test API endpoint</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
