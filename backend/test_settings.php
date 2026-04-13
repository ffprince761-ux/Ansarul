<?php
/**
 * Direct Database Test - Check App Settings
 */

$host = "localhost";
$username = "root";
$password = "";
$dbname = "bizinote_db";

echo "<h2>Testing App Settings in bizinote_db Database</h2>";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Connected to database: $dbname</p>";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'app_settings'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ app_settings table exists</p>";
        
        // Get all settings
        $result = $conn->query("SELECT * FROM app_settings");
        
        if ($result->num_rows > 0) {
            echo "<h3>Current Settings:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Key</th><th>Value</th><th>Updated At</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($row['setting_key']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($row['setting_value']) . "</td>";
                echo "<td>" . htmlspecialchars($row['updated_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ No settings found in table</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ app_settings table does NOT exist</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Test API Endpoint:</h3>";
echo "<p><a href='api/get_app_settings.php' target='_blank'>Click here to test API</a></p>";
?>
