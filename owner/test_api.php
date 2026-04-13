<?php
/**
 * Test App Settings API
 */
require_once 'config/db.php';

header('Content-Type: application/json');

try {
    // Get app settings
    $stmt = $pdo->query("SELECT * FROM app_settings");
    $settings = [];
    
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $settings,
        'debug' => 'Testing from owner panel'
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
