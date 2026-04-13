<?php
/**
 * Middleware: Check if user is blocked
 * Add this to every API endpoint that requires authentication
 */

function checkUserBlocked($conn, $userId) {
    if (empty($userId)) {
        return ['blocked' => false];
    }
    
    try {
        $stmt = $conn->prepare("SELECT COALESCE(is_blocked, 0) as is_blocked FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $isBlocked = (int)$user['is_blocked'];
            
            // Only block if explicitly set to 1
            if ($isBlocked === 1) {
                // User is blocked - return error immediately
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'blocked' => true,
                    'error' => 'Your account has been blocked by administrator. Please contact support.'
                ]);
                exit; // Stop execution immediately
            }
        }
        
        return ['blocked' => false];
    } catch (Exception $e) {
        error_log("Block check error: " . $e->getMessage());
        // On error, allow access (don't block legitimate users)
        return ['blocked' => false];
    }
}
?>
