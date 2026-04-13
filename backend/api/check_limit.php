<?php
/**
 * Check if user has reached their billing limit
 * Returns: { limited: true/false }
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

require_once '../config.php';

$userId = $_GET['userId'] ?? '';
if (empty($userId)) {
    echo json_encode(['success' => false, 'limited' => false]);
    exit;
}

try {
    // Check master switch
    $result = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'billing_limit_enabled' LIMIT 1");
    if (!$result || $result->num_rows == 0) {
        echo json_encode(['success' => true, 'limited' => false]);
        exit;
    }
    $enabled = $result->fetch_assoc()['setting_value'];
    if ($enabled !== '1') {
        echo json_encode(['success' => true, 'limited' => false]);
        exit;
    }

    // Check user plan
    $stmt = $conn->prepare("SELECT plan_type, bill_limit FROM user_plans WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $planResult = $stmt->get_result();

    if ($planResult->num_rows > 0) {
        $plan = $planResult->fetch_assoc();
        if ($plan['plan_type'] === 'paid') {
            echo json_encode(['success' => true, 'limited' => false]);
            exit;
        }
        $limit = intval($plan['bill_limit']);
    } else {
        $defResult = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_bill_limit' LIMIT 1");
        $limit = $defResult ? intval($defResult->fetch_assoc()['setting_value'] ?? 500) : 500;
    }

    // Count bills
    $stmt2 = $conn->prepare("SELECT COUNT(*) as cnt FROM bills WHERE user_id = ?");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $count = $stmt2->get_result()->fetch_assoc()['cnt'];

    echo json_encode(['success' => true, 'limited' => $count >= $limit, 'used' => intval($count), 'limit' => $limit]);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'limited' => false]);
}
