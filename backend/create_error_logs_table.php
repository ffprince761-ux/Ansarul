<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS app_error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    error_message TEXT NOT NULL,
    stack_trace TEXT NULL,
    device_info TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'app_error_logs' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
