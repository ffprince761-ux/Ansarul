<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get user_id from request
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

switch ($method) {
    case 'GET':
        // Get stock adjustments for a product
        $productId = $_GET['product_id'] ?? null;
        
        if ($productId) {
            $stmt = $conn->prepare("SELECT * FROM stock_adjustments WHERE product_id = ? AND user_id = ? ORDER BY date DESC");
            $stmt->bind_param("ii", $productId, $userId);
        } else {
            $stmt = $conn->prepare("SELECT * FROM stock_adjustments WHERE user_id = ? ORDER BY date DESC");
            $stmt->bind_param("i", $userId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $adjustments = [];
        
        while ($row = $result->fetch_assoc()) {
            $adjustments[] = $row;
        }
        
        echo json_encode(['success' => true, 'adjustments' => $adjustments]);
        $stmt->close();
        break;
        
    case 'POST':
        // Add new stock adjustment
        $productId = $data['product_id'] ?? null;
        $quantity = $data['quantity'] ?? 0;
        $date = $data['date'] ?? date('Y-m-d');
        $note = $data['note'] ?? 'Stock Added';
        
        if (!$productId || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO stock_adjustments (product_id, user_id, quantity, date, note) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $productId, $userId, $quantity, $date, $note);
        
        if ($stmt->execute()) {
            $adjustmentId = $conn->insert_id;
            echo json_encode(['success' => true, 'message' => 'Stock adjustment added', 'id' => $adjustmentId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add stock adjustment']);
        }
        
        $stmt->close();
        break;
        
    case 'DELETE':
        // Delete stock adjustment
        $adjustmentId = $data['id'] ?? null;
        
        if (!$adjustmentId) {
            echo json_encode(['success' => false, 'message' => 'Adjustment ID required']);
            exit();
        }
        
        $stmt = $conn->prepare("DELETE FROM stock_adjustments WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $adjustmentId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Stock adjustment deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete stock adjustment']);
        }
        
        $stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

$conn->close();
