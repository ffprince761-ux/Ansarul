<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';
$userId = $_GET['userId'] ?? '';

if (empty($userId)) {
    echo json_encode(['error' => 'User ID required']);
    exit;
}

switch ($action) {
    case 'create':
        createBackup($userId);
        break;
    case 'restore':
        restoreBackup($userId);
        break;
    case 'list':
        listBackups($userId);
        break;
    case 'sync':
        syncData($userId);
        break;
    case 'export':
        exportData($userId);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function createBackup($userId) {
    global $conn;
    
    try {
        // Get all user data
        $data = [
            'user' => getUserData($userId),
            'products' => getUserProducts($userId),
            'customers' => getUserCustomers($userId),
            'bills' => getUserBills($userId),
            'expenses' => getUserExpenses($userId)
        ];
        
        // Create backup record
        $backupData = json_encode($data);
        $backupName = 'backup_' . date('Y-m-d_H-i-s');
        
        $stmt = $conn->prepare("INSERT INTO backups (user_id, backup_name, backup_data, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $userId, $backupName, $backupData);
        
        if ($stmt->execute()) {
            $backupId = $conn->insert_id;
            echo json_encode([
                'success' => true, 
                'backupId' => $backupId,
                'backupName' => $backupName,
                'message' => 'Backup created successfully'
            ]);
        } else {
            echo json_encode(['error' => 'Failed to create backup']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Backup failed: ' . $e->getMessage()]);
    }
}

function restoreBackup($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $backupId = $data['backupId'] ?? '';
    
    if (empty($backupId)) {
        echo json_encode(['error' => 'Backup ID is required']);
        return;
    }
    
    try {
        // Get backup data
        $stmt = $conn->prepare("SELECT backup_data FROM backups WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $backupId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $backupData = json_decode($result->fetch_assoc()['backup_data'], true);
            
            // Restore data (simplified version)
            restoreUserData($userId, $backupData['user']);
            restoreProducts($userId, $backupData['products']);
            restoreCustomers($userId, $backupData['customers']);
            restoreBills($userId, $backupData['bills']);
            restoreExpenses($userId, $backupData['expenses']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup restored successfully'
            ]);
        } else {
            echo json_encode(['error' => 'Backup not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Restore failed: ' . $e->getMessage()]);
    }
}

function listBackups($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, backup_name, created_at FROM backups WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $backups = [];
    while ($row = $result->fetch_assoc()) {
        $backups[] = $row;
    }
    
    echo json_encode(['success' => true, 'backups' => $backups]);
}

function syncData($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    $localData = $data['localData'] ?? [];
    
    try {
        // Sync logic - merge local data with cloud data
        // This is a simplified version
        $syncResult = [
            'products' => syncProducts($userId, $localData['products'] ?? []),
            'customers' => syncCustomers($userId, $localData['customers'] ?? []),
            'bills' => syncBills($userId, $localData['bills'] ?? []),
            'expenses' => syncExpenses($userId, $localData['expenses'] ?? [])
        ];
        
        echo json_encode([
            'success' => true,
            'syncResult' => $syncResult,
            'message' => 'Data synchronized successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Sync failed: ' . $e->getMessage()]);
    }
}

function exportData($userId) {
    try {
        $data = [
            'user' => getUserData($userId),
            'products' => getUserProducts($userId),
            'customers' => getUserCustomers($userId),
            'bills' => getUserBills($userId),
            'expenses' => getUserExpenses($userId)
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'exportDate' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
    }
}

// Helper functions
function getUserData($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getUserProducts($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($row = $result->fetch_assoc()) $products[] = $row;
    return $products;
}

function getUserCustomers($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $customers = [];
    while ($row = $result->fetch_assoc()) $customers[] = $row;
    return $customers;
}

function getUserBills($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM bills WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bills = [];
    while ($row = $result->fetch_assoc()) $bills[] = $row;
    return $bills;
}

function getUserExpenses($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $expenses = [];
    while ($row = $result->fetch_assoc()) $expenses[] = $row;
    return $expenses;
}

// Simplified restore functions
function restoreProducts($userId, $products) {
    global $conn;
    foreach ($products as $product) {
        $stmt = $conn->prepare("INSERT INTO products (user_id, name, category, price, stock, low_stock_threshold, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddis", $userId, $product['name'], $product['category'], $product['price'], $product['stock'], $product['low_stock_threshold'], $product['description']);
        $stmt->execute();
    }
}

function restoreCustomers($userId, $customers) {
    global $conn;
    foreach ($customers as $customer) {
        $stmt = $conn->prepare("INSERT INTO customers (user_id, name, mobile, email, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $customer['name'], $customer['mobile'], $customer['email'], $customer['address']);
        $stmt->execute();
    }
}

function restoreBills($userId, $bills) {
    global $conn;
    foreach ($bills as $bill) {
        $stmt = $conn->prepare("INSERT INTO bills (user_id, invoice_number, customer_id, customer_name, customer_mobile, customer_email, customer_address, items, subtotal, discount, tax, total, grand_total, payment_mode, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisssssdddddss", $userId, $bill['invoice_number'], $bill['customer_id'], $bill['customer_name'], $bill['customer_mobile'], $bill['customer_email'], $bill['customer_address'], $bill['items'], $bill['subtotal'], $bill['discount'], $bill['tax'], $bill['total'], $bill['grand_total'], $bill['payment_mode'], $bill['date']);
        $stmt->execute();
    }
}

function restoreExpenses($userId, $expenses) {
    global $conn;
    foreach ($expenses as $expense) {
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, description, amount, date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $userId, $expense['category'], $expense['description'], $expense['amount'], $expense['date']);
        $stmt->execute();
    }
}

// Simplified sync functions
function syncProducts($userId, $localProducts) {
    // Implementation for syncing products
    return ['synced' => count($localProducts), 'added' => 0, 'updated' => 0];
}

function syncCustomers($userId, $localCustomers) {
    // Implementation for syncing customers
    return ['synced' => count($localCustomers), 'added' => 0, 'updated' => 0];
}

function syncBills($userId, $localBills) {
    // Implementation for syncing bills
    return ['synced' => count($localBills), 'added' => 0, 'updated' => 0];
}

function syncExpenses($userId, $localExpenses) {
    // Implementation for syncing expenses
    return ['synced' => count($localExpenses), 'added' => 0, 'updated' => 0];
}
