<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

echo json_encode([
    'success' => true,
    'message' => 'API is working!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);
