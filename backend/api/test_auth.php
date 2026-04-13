<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Test if config.php can be loaded
try {
    require_once '../config.php';
    echo json_encode([
        'success' => true,
        'message' => 'Config loaded successfully',
        'database_connected' => $conn->ping()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
