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
        getExpenses($userId);
        break;
    case 'add':
        addExpense($userId);
        break;
    case 'update':
        updateExpense($userId);
        break;
    case 'delete':
        deleteExpense($userId);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function getExpenses($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    
    echo json_encode(['success' => true, 'expenses' => $expenses]);
}

function addExpense($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $category = $data['category'] ?? '';
    $description = $data['description'] ?? '';
    $amount = $data['amount'] ?? 0;
    $date = $data['date'] ?? date('Y-m-d');
    
    if (empty($category) || $amount <= 0) {
        echo json_encode(['error' => 'Category and amount are required']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, description, amount, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $userId, $category, $description, $amount, $date);
    
    if ($stmt->execute()) {
        $expenseId = $conn->insert_id;
        echo json_encode(['success' => true, 'expenseId' => $expenseId]);
    } else {
        echo json_encode(['error' => 'Failed to add expense']);
    }
}

function updateExpense($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $expenseId = $data['id'] ?? '';
    $category = $data['category'] ?? '';
    $description = $data['description'] ?? '';
    $amount = $data['amount'] ?? 0;
    $date = $data['date'] ?? date('Y-m-d');
    
    if (empty($expenseId) || empty($category) || $amount <= 0) {
        echo json_encode(['error' => 'Expense ID, category and amount are required']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE expenses SET category = ?, description = ?, amount = ?, date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssdsii", $category, $description, $amount, $date, $expenseId, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update expense']);
    }
}

function deleteExpense($userId) {
    global $conn;
    
    $expenseId = $_GET['id'] ?? '';
    
    if (empty($expenseId)) {
        echo json_encode(['error' => 'Expense ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $expenseId, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete expense']);
    }
}
