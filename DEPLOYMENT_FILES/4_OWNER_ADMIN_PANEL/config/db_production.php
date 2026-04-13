<?php
/**
 * Database Configuration for Owner Panel - PRODUCTION
 * Connects to Hostinger database
 */

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

// Database credentials - UPDATE THESE FOR HOSTINGER
$host = "localhost";           // Change to Hostinger DB host
$dbname = "u946320467_binest"; // Change to your Hostinger DB name
$username = "u946320467_binest";  // Change to your Hostinger DB user
$password = "Binest@28";     // Change to your Hostinger DB password

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set error mode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set MySQL timezone to match PHP
    $pdo->exec("SET time_zone = '+05:30'");
    
} catch(PDOException $e) {
    // Show a more helpful error message in a nice box
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: sans-serif;'>";
    echo "<h3>🗄️ Database Connection Failed</h3>";
    echo "<p>Please check your <code>owner/config/db_production.php</code> settings.</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr><p>Make sure you have imported the SQL file and updated credentials.</p>";
    echo "</div>";
    die();
}

// ===== EMAIL CONFIGURATION =====
// Using EmailService.php working configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'ffprince761@gmail.com');  // From EmailService.php
define('SMTP_PASSWORD', 'sqaezyalrvkedzut');     // From EmailService.php (App Password)
define('SMTP_FROM_EMAIL', 'ffprince761@gmail.com');
define('SMTP_FROM_NAME', 'Bizinote');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
