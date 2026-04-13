<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['errorMessage'])) {
        $userId = $data['userId'] ?? null;
        $errorMessage = $data['errorMessage'];
        $stackTrace = $data['stackTrace'] ?? null;
        $deviceInfo = $data['deviceInfo'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO app_error_logs (user_id, error_message, stack_trace, device_info) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $errorMessage, $stackTrace, $deviceInfo);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Error reported successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to log error: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Missing error data"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

$conn->close();
