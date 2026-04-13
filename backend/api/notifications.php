<?php
/**
 * Backend API - Fetch owner notifications for app users
 * Returns active notifications targeted to all users or a specific user
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

require_once '../config.php';

$userId = $_GET['userId'] ?? '';

if (empty($userId)) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

try {
    // Fetch active notifications: targeted to all users OR specifically to this user
    $stmt = $conn->prepare("
        SELECT id, title, message, type, created_at 
        FROM owner_notifications 
        WHERE is_active = 1 AND (target = 'all' OR (target = 'specific' AND target_user_id = ?))
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
