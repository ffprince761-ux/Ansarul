<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);

$userId       = intval($data['userId'] ?? 0);
$businessName = trim($data['businessName'] ?? '');
$mobile       = trim($data['mobile'] ?? '');
$email        = trim($data['email'] ?? '');
$address      = trim($data['address'] ?? '');

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

try {
    // Ensure address column exists
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL");

    $stmt = $conn->prepare("UPDATE users SET business_name = ?, mobile = ?, email = ?, address = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $businessName, $mobile, $email, $address, $userId);
    $stmt->execute();
    $stmt->close();

    $sel = $conn->prepare("SELECT id, name, email, mobile, business_name, address FROM users WHERE id = ?");
    $sel->bind_param("i", $userId);
    $sel->execute();
    $result = $sel->get_result();
    $user = $result->fetch_assoc();
    $sel->close();

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
