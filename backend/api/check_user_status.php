<?php
/**
 * API: Check User Status
 * Check if user is blocked or active
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

// Get input
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$userId = $data['userId'] ?? '';

// Log for debugging
error_log("check_user_status.php - Input: " . $input);
error_log("check_user_status.php - User ID: " . $userId);

if (empty($userId)) {
    echo json_encode(['success' => false, 'error' => 'User ID required', 'debug' => 'No userId provided']);
    exit;
}

try {
    // Check if is_blocked column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'is_blocked'");
    if ($checkColumn->num_rows == 0) {
        // Column doesn't exist, add it
        $conn->query("ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0");
        error_log("Added is_blocked column to users table");
    }
    
    // Get user status
    $stmt = $conn->prepare("SELECT id, COALESCE(is_blocked, 0) as is_blocked FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Query executed, rows found: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $isBlocked = (int)$user['is_blocked'];
        
        error_log("User found - ID: {$user['id']}, is_blocked: {$isBlocked}");
        
        if ($isBlocked === 1) {
            echo json_encode([
                'success' => false,
                'blocked' => true,
                'error' => 'Your account has been blocked. Please contact support.'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'blocked' => false
            ]);
        }
    } else {
        error_log("User not found with ID: " . $userId);
        echo json_encode(['success' => false, 'error' => 'User not found', 'userId' => $userId]);
    }
} catch (Exception $e) {
    error_log("check_user_status.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
