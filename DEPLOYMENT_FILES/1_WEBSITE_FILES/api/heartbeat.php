<?php
/**
 * Heartbeat API - Tracks active app users in real-time
 * Mobile app calls this every 60 seconds to report user is online
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../config.php';

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS active_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_name VARCHAR(255),
    business_name VARCHAR(255),
    device_info VARCHAR(500),
    app_screen VARCHAR(100) DEFAULT 'Home',
    last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id)
)");

$data = json_decode(file_get_contents("php://input"), true);
$userId = $data['userId'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'userId required']);
    exit;
}

$userName = $data['userName'] ?? '';
$businessName = $data['businessName'] ?? '';
$deviceInfo = $data['deviceInfo'] ?? '';
$screen = $data['screen'] ?? 'Home';

// Upsert - insert or update last_ping
$stmt = $conn->prepare("INSERT INTO active_sessions (user_id, user_name, business_name, device_info, app_screen, last_ping, session_start) 
    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE user_name=VALUES(user_name), business_name=VALUES(business_name), 
    device_info=VALUES(device_info), app_screen=VALUES(app_screen), last_ping=NOW()");
$stmt->bind_param("issss", $userId, $userName, $businessName, $deviceInfo, $screen);
$stmt->execute();
$stmt->close();

// Cleanup sessions older than 2 minutes (user closed app)
$conn->query("DELETE FROM active_sessions WHERE last_ping < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");

echo json_encode(['success' => true, 'message' => 'Heartbeat recorded']);
$conn->close();
