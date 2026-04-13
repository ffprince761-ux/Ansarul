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
        getCustomers($userId);
        break;
    case 'add':
        addCustomer($userId);
        break;
    case 'update':
        updateCustomer($userId);
        break;
    case 'delete':
        deleteCustomer($userId);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function getCustomers($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    echo json_encode(['success' => true, 'customers' => $customers]);
}

function addCustomer($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $mobile = $data['mobile'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Customer name is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO customers (user_id, name, mobile, email, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $name, $mobile, $email, $address);
        
        if ($stmt->execute()) {
            $customerId = $conn->insert_id;
            echo json_encode(['success' => true, 'customerId' => $customerId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add customer: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateCustomer($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $customerId = $data['id'] ?? '';
    $name = $data['name'] ?? '';
    $mobile = $data['mobile'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    
    if (empty($customerId) || empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Customer ID and name are required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE customers SET name = ?, mobile = ?, email = ?, address = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssii", $name, $mobile, $email, $address, $customerId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update customer: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteCustomer($userId) {
    global $conn;
    
    $customerId = $_GET['id'] ?? '';
    
    if (empty($customerId)) {
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $customerId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete customer: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
