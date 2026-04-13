<?php
/**
 * API: Manage Users (Block/Unblock/Delete)
 */
require_once '../config/db.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

// Check if owner is logged in
if (!isOwnerLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';
$userId = $_POST['user_id'] ?? 0;

try {
    switch ($action) {
        case 'block':
            // Add is_blocked column if it doesn't exist
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN DEFAULT FALSE");
            
            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            
            logOwnerActivity($pdo, $_SESSION['owner_id'], 'block_user', "Blocked user ID: $userId");
            
            echo json_encode(['success' => true, 'message' => 'User blocked successfully']);
            break;
            
        case 'unblock':
            $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            
            logOwnerActivity($pdo, $_SESSION['owner_id'], 'unblock_user', "Unblocked user ID: $userId");
            
            echo json_encode(['success' => true, 'message' => 'User unblocked successfully']);
            break;
            
        case 'delete':
            // Get user info before deleting
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit();
            }
            
            // Delete user (CASCADE will delete related data)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            logOwnerActivity($pdo, $_SESSION['owner_id'], 'delete_user', "Deleted user: {$user['name']} ({$user['email']})");
            
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch(PDOException $e) {
    error_log("Manage user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
