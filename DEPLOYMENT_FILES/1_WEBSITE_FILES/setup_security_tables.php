<?php
/**
 * Setup Security Tables
 * Creates tables for security logging and IP blocking
 */
require_once 'config.php';

echo "<h1>Setting up Security Tables</h1>";

// Create security_logs table
$securityLogsTable = "CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    user_id INT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip_address, action),
    INDEX idx_created (created_at)
)";

if ($conn->query($securityLogsTable)) {
    echo "<p style='color: green;'>✅ Security logs table created successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating security_logs table: " . $conn->error . "</p>";
}

// Create blocked_ips table
$blockedIPsTable = "CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    blocked_until DATETIME NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_blocked (ip_address, blocked_until)
)";

if ($conn->query($blockedIPsTable)) {
    echo "<p style='color: green;'>✅ Blocked IPs table created successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating blocked_ips table: " . $conn->error . "</p>";
}

// Create failed_login_attempts table
$failedLoginsTable = "CREATE TABLE IF NOT EXISTS failed_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
)";

if ($conn->query($failedLoginsTable)) {
    echo "<p style='color: green;'>✅ Failed login attempts table created successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Error creating failed_login_attempts table: " . $conn->error . "</p>";
}

echo "<h2>✅ Security tables setup complete!</h2>";
echo "<p><a href='test_all_apis.php'>Go to API Test Suite</a></p>";

$conn->close();
?>
