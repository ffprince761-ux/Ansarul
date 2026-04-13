<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';
$userId = $_GET['userId'] ?? '';

if (empty($userId)) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

switch ($action) {
    case 'get':
        getProducts($userId);
        break;
    case 'add':
        addProduct($userId);
        break;
    case 'update':
        updateProduct($userId);
        break;
    case 'delete':
        deleteProduct($userId);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function getProducts($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
}

function addProduct($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $category = $data['category'] ?? '';
    $price = $data['price'] ?? 0;
    $stock = $data['stock'] ?? 0;
    $unit = $data['unit'] ?? 'Nos';
    $lowStockThreshold = $data['lowStockThreshold'] ?? 10;
    $description = $data['description'] ?? '';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Product name is required']);
        return;
    }
    
    try {
        $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS unit VARCHAR(20) DEFAULT 'Nos'");
        $stmt = $conn->prepare("INSERT INTO products (user_id, name, category, price, stock, unit, low_stock_threshold, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddsid", $userId, $name, $category, $price, $stock, $unit, $lowStockThreshold, $description);
        
        if ($stmt->execute()) {
            $productId = $conn->insert_id;
            echo json_encode(['success' => true, 'productId' => $productId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add product: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateProduct($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productId = $data['id'] ?? '';
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }
    
    // Build dynamic UPDATE query based on provided fields
    $updateFields = [];
    $params = [];
    $types = '';
    
    if (isset($data['name'])) {
        $updateFields[] = "name = ?";
        $params[] = $data['name'];
        $types .= 's';
    }
    if (isset($data['category'])) {
        $updateFields[] = "category = ?";
        $params[] = $data['category'];
        $types .= 's';
    }
    if (isset($data['price'])) {
        $updateFields[] = "price = ?";
        $params[] = $data['price'];
        $types .= 'd';
    }
    if (isset($data['stock'])) {
        $updateFields[] = "stock = ?";
        $params[] = $data['stock'];
        $types .= 'i';
    }
    if (isset($data['unit'])) {
        $updateFields[] = "unit = ?";
        $params[] = $data['unit'];
        $types .= 's';
    }
    if (isset($data['lowStockThreshold'])) {
        $updateFields[] = "low_stock_threshold = ?";
        $params[] = $data['lowStockThreshold'];
        $types .= 'i';
    }
    if (isset($data['description'])) {
        $updateFields[] = "description = ?";
        $params[] = $data['description'];
        $types .= 's';
    }
    
    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    // Add productId and userId to params
    $params[] = $productId;
    $params[] = $userId;
    $types .= 'ii';
    
    try {
        $sql = "UPDATE products SET " . implode(", ", $updateFields) . " WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update product: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteProduct($userId) {
    global $conn;
    
    $productId = $_GET['id'] ?? '';
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $productId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete product: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
