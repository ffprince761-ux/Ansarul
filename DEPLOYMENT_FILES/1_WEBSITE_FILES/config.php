<?php
// Global Exception Handler for JSON API
set_exception_handler(function($e) {
    if (!headers_sent()) {
        header("Content-Type: application/json");
    }
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'type' => get_class($e)
    ]);
    exit;
});

// Database Configuration
// For Local XAMPP: Use root with empty password
// For Hostinger: Update with your actual credentials
$host = "localhost";
$dbname = "u946320467_binest";
$username = "u946320467_binest";  // Change to "bizinote_user" for Hostinger
$password = "Binest@28";      // Change to your actual password for Hostinger

try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
    // Enable error reporting for other queries
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} catch (mysqli_sql_exception $e) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
