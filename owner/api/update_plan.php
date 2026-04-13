<?php
/**
 * Owner API - Update User Plans & Subscription Settings
 * Actions: toggle_master, update_default_limit, update_user_plan, bulk_update
 */
require_once '../config/db.php';
require_once '../config/functions.php';
requireOwnerLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    // Ensure tables exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_plans (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        plan_type ENUM('free','paid') DEFAULT 'free',
        bill_limit INT DEFAULT 500,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES 
        ('billing_limit_enabled', '0'),
        ('default_bill_limit', '500')
    ");

    switch ($action) {
        case 'toggle_master':
            $enabled = $_POST['enabled'] ?? '0';
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'billing_limit_enabled'");
            $stmt->execute([$enabled ? '1' : '0']);
            logOwnerAction($pdo, 'Toggle Billing Limits', $enabled ? 'Enabled' : 'Disabled');
            echo json_encode(['success' => true, 'enabled' => (bool)$enabled]);
            break;

        case 'update_default_limit':
            $limit = max(1, intval($_POST['limit'] ?? 500));
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = 'default_bill_limit'");
            $stmt->execute([(string)$limit]);
            logOwnerAction($pdo, 'Update Default Bill Limit', "Set to $limit");
            echo json_encode(['success' => true, 'limit' => $limit]);
            break;

        case 'update_user_plan':
            $userId = intval($_POST['user_id'] ?? 0);
            $planType = in_array($_POST['plan_type'] ?? '', ['free', 'paid']) ? $_POST['plan_type'] : 'free';
            $billLimit = max(1, intval($_POST['bill_limit'] ?? 500));

            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO user_plans (user_id, plan_type, bill_limit) 
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE plan_type = VALUES(plan_type), bill_limit = VALUES(bill_limit)");
            $stmt->execute([$userId, $planType, $billLimit]);

            logOwnerAction($pdo, 'Update User Plan', "User #$userId → $planType (limit: $billLimit)");
            echo json_encode(['success' => true]);
            break;

        case 'bulk_set_plan':
            $planType = in_array($_POST['plan_type'] ?? '', ['free', 'paid']) ? $_POST['plan_type'] : 'free';
            $userIds = json_decode($_POST['user_ids'] ?? '[]', true);
            if (empty($userIds)) {
                echo json_encode(['success' => false, 'error' => 'No users selected']);
                break;
            }
            $defaultLimit = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_bill_limit'")->fetch()['setting_value'] ?? 500;
            $stmt = $pdo->prepare("INSERT INTO user_plans (user_id, plan_type, bill_limit) 
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE plan_type = VALUES(plan_type)");
            foreach ($userIds as $uid) {
                $stmt->execute([intval($uid), $planType, intval($defaultLimit)]);
            }
            logOwnerAction($pdo, 'Bulk Plan Update', count($userIds) . " users → $planType");
            echo json_encode(['success' => true, 'updated' => count($userIds)]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Update plan error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function logOwnerAction($pdo, $action, $details) {
    try {
        $pdo->prepare("INSERT INTO owner_activity_log (owner_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)")
            ->execute([
                $_SESSION['owner_id'] ?? 0,
                $_SESSION['owner_username'] ?? 'owner',
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
    } catch (Exception $e) {}
}
