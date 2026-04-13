<?php
/**
 * Database Configuration for Owner Panel
 * Connects to the same bizinote database
 */

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

// Database credentials
$host = "localhost";
$dbname = "bizinote_db";
$username = "root";
$password = "";

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

// ===== GLOBAL EMAIL ERROR LOGGER =====
function logEmailError($to, $subject, $error, $from = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO app_error_logs 
            (error_type, error_message, file_path, line_number, ip_address, request_data) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'email_failure',
            "Email send failed to $to | Subject: $subject | Error: $error",
            'mail_function',
            0,
            $_SERVER['REMOTE_ADDR'] ?? 'system',
            json_encode([
                'to' => $to,
                'subject' => $subject,
                'from' => $from,
                'error' => $error,
                'timestamp' => date('Y-m-d H:i:s')
            ])
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log email error: " . $e->getMessage());
        return false;
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
