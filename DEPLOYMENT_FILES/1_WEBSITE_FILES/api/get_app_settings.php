<?php
/**
 * API: Get App Settings
 * Returns app settings for mobile app
 */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

try {
    // Create app_settings table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS app_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert default settings if not exist
    $conn->query("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES 
        ('support_email', 'support@bizinote.com'),
        ('support_phone', '+91 1234567890'),
        ('app_version', '1.0.0')
    ");
    
    // Get app settings
    $stmt = $conn->query("SELECT * FROM app_settings");
    $settings = [];
    
    while ($row = $stmt->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
