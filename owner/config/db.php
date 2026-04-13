<?php
/**
 * Database Configuration for Owner Panel
 * Connects to the same bizinote database
 */

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

// Database credentials
$host = "localhost";
$dbname = "u946320467_binest";
$username = "u946320467_binest";
$password = "Binest@28";

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
    echo "<p>Please check your <code>owner/config/db.php</code> settings.</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr><p>If you haven't installed the database yet, run: <a href='../install.php'>install.php</a></p>";
    echo "</div>";
    die();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
