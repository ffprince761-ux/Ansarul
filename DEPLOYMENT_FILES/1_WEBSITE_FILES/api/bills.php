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
        getBills($userId);
        break;
    case 'add':
        addBill($userId);
        break;
    case 'update':
        updateBill($userId);
        break;
    case 'delete':
        deleteBill($userId);
        break;
    case 'search':
        searchBill($userId);
        break;
    case 'update_due_status':
        updateDueStatus($userId);
        break;
    case 'add_payment':
        addDuePayment($userId);
        break;
    case 'get_payments':
        getDuePayments($userId);
        break;
    default:
        echo json_encode(['success' => true, 'message' => 'Bills API is working', 'timestamp' => date('Y-m-d H:i:s')]);
        break;
}

function getBills($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM bills WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bills = [];
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
    
    echo json_encode(['success' => true, 'bills' => $bills]);
}

function checkBillingLimit($conn, $userId) {
    // Check if billing limits are enabled (master switch)
    $result = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'billing_limit_enabled' LIMIT 1");
    if (!$result || $result->num_rows == 0) return true; // table missing = no limit
    $enabled = $result->fetch_assoc()['setting_value'];
    if ($enabled !== '1') return true; // master switch OFF

    // Get user's plan
    $stmt = $conn->prepare("SELECT plan_type, bill_limit FROM user_plans WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $planResult = $stmt->get_result();

    if ($planResult->num_rows > 0) {
        $plan = $planResult->fetch_assoc();
        if ($plan['plan_type'] === 'paid') return true; // paid = unlimited
        $limit = intval($plan['bill_limit']);
    } else {
        // No plan row = use default limit
        $defResult = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_bill_limit' LIMIT 1");
        $limit = $defResult ? intval($defResult->fetch_assoc()['setting_value'] ?? 500) : 500;
    }

    // Count user's existing bills
    $stmt2 = $conn->prepare("SELECT COUNT(*) as cnt FROM bills WHERE user_id = ?");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $count = $stmt2->get_result()->fetch_assoc()['cnt'];

    return $count < $limit;
}

function addBill($userId) {
    global $conn;
    
    // Silent billing limit check
    if (!checkBillingLimit($conn, $userId)) {
        echo json_encode(['success' => false, 'error' => 'Bill limit over! Please buy subscription to continue.', 'limit_reached' => true]);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $invoiceNumber = $data['invoiceNumber'] ?? '';
    $customerId = $data['customerId'] ?? null;
    $customerName = $data['customerName'] ?? '';
    $customerMobile = $data['customerMobile'] ?? '';
    $customerEmail = $data['customerEmail'] ?? '';
    $customerAddress = $data['customerAddress'] ?? '';
    $items = json_encode($data['items'] ?? []);
    $subtotal = $data['subtotal'] ?? 0;
    $discount = $data['discount'] ?? 0;
    $tax = $data['tax'] ?? 0;
    $total = $data['total'] ?? 0;
    $grandTotal = $data['grandTotal'] ?? 0;
    $paymentMode = $data['paymentMode'] ?? '';
    $date = $data['date'] ?? date('Y-m-d');
    $dueStatus = ($paymentMode === 'Due') ? 'unpaid' : 'paid';
    $dueDate = $data['due_date'] ?? null;
    
    if (empty($invoiceNumber) || empty($customerName)) {
        echo json_encode(['success' => false, 'error' => 'Invoice number and customer name are required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO bills (user_id, invoice_number, customer_id, customer_name, customer_mobile, customer_email, customer_address, items, subtotal, discount, tax, total, grand_total, payment_mode, date, due_status, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisssssdddddssss", $userId, $invoiceNumber, $customerId, $customerName, $customerMobile, $customerEmail, $customerAddress, $items, $subtotal, $discount, $tax, $total, $grandTotal, $paymentMode, $date, $dueStatus, $dueDate);
        
        if ($stmt->execute()) {
            $billId = $conn->insert_id;
            
            // Update product stock for each item in the bill
            $itemsArray = json_decode($items, true);
            if (is_array($itemsArray)) {
                foreach ($itemsArray as $item) {
                    // Only update stock if item has productId and is not manual
                    if (isset($item['productId']) && !isset($item['isManual'])) {
                        $productId = $item['productId'];
                        $quantity = $item['quantity'] ?? 0;
                        
                        // Update stock in products table
                        $updateStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND user_id = ?");
                        $updateStmt->bind_param("iii", $quantity, $productId, $userId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
            }
            
            echo json_encode(['success' => true, 'billId' => $billId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add bill: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateBill($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $billId = $data['id'] ?? '';
    $invoiceNumber = $data['invoiceNumber'] ?? '';
    $customerId = $data['customerId'] ?? null;
    $customerName = $data['customerName'] ?? '';
    $customerMobile = $data['customerMobile'] ?? '';
    $customerEmail = $data['customerEmail'] ?? '';
    $customerAddress = $data['customerAddress'] ?? '';
    $items = json_encode($data['items'] ?? []);
    $subtotal = $data['subtotal'] ?? 0;
    $discount = $data['discount'] ?? 0;
    $tax = $data['tax'] ?? 0;
    $total = $data['total'] ?? 0;
    $grandTotal = $data['grandTotal'] ?? 0;
    $paymentMode = $data['paymentMode'] ?? '';
    $date = $data['date'] ?? date('Y-m-d');
    $dueStatus = $data['due_status'] ?? (($paymentMode === 'Due') ? 'unpaid' : 'paid');
    $dueDate = $data['due_date'] ?? null;
    
    if (empty($billId)) {
        echo json_encode(['success' => false, 'error' => 'Bill ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE bills SET invoice_number = ?, customer_id = ?, customer_name = ?, customer_mobile = ?, customer_email = ?, customer_address = ?, items = ?, subtotal = ?, discount = ?, tax = ?, total = ?, grand_total = ?, payment_mode = ?, date = ?, due_status = ?, due_date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sisssssdddddssssii", $invoiceNumber, $customerId, $customerName, $customerMobile, $customerEmail, $customerAddress, $items, $subtotal, $discount, $tax, $total, $grandTotal, $paymentMode, $date, $dueStatus, $dueDate, $billId, $userId);
    
    if ($stmt->execute()) {
        // Fetch updated bill to return
        $fetchStmt = $conn->prepare("SELECT * FROM bills WHERE id = ? AND user_id = ?");
        $fetchStmt->bind_param("ii", $billId, $userId);
        $fetchStmt->execute();
        $result = $fetchStmt->get_result();
        $updatedBill = $result->fetch_assoc();
        echo json_encode(['success' => true, 'bill' => $updatedBill]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update bill']);
    }
}

function deleteBill($userId) {
    global $conn;
    
    $billId = $_GET['id'] ?? '';
    
    if (empty($billId)) {
        echo json_encode(['error' => 'Bill ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM bills WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $billId, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete bill']);
    }
}

function searchBill($userId) {
    global $conn;
    
    $searchTerm = $_GET['search'] ?? '';
    
    if (empty($searchTerm)) {
        echo json_encode(['error' => 'Search term is required']);
        return;
    }
    
    // Remove # and search by invoice number or ID
    $cleanSearch = str_replace('#', '', $searchTerm);
    
    $stmt = $conn->prepare("SELECT * FROM bills WHERE user_id = ? AND (invoice_number LIKE ? OR id = ?) LIMIT 1");
    $searchPattern = "%{$cleanSearch}%";
    $stmt->bind_param("isi", $userId, $searchPattern, $cleanSearch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $bill = $result->fetch_assoc();
        echo json_encode(['success' => true, 'bill' => $bill]);
    } else {
        echo json_encode(['error' => 'Bill not found']);
    }
}

function updateDueStatus($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $billId = $data['billId'] ?? '';
    $dueStatus = $data['dueStatus'] ?? 'paid';
    $duePaidDate = ($dueStatus === 'paid') ? date('Y-m-d') : null;
    
    if (empty($billId)) {
        echo json_encode(['success' => false, 'error' => 'Bill ID required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE bills SET due_status = ?, due_paid_date = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $dueStatus, $duePaidDate, $billId, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Due status updated']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update due status']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function addDuePayment($userId) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $billId = $data['billId'] ?? '';
    $amount = $data['amount'] ?? 0;
    $note = $data['note'] ?? '';
    $paymentDate = $data['paymentDate'] ?? date('Y-m-d');
    
    if (empty($billId) || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Bill ID and valid amount required']);
        return;
    }
    
    try {
        // Get current bill
        $stmt = $conn->prepare("SELECT grand_total, paid_amount FROM bills WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $billId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo json_encode(['success' => false, 'error' => 'Bill not found']);
            return;
        }
        
        $bill = $result->fetch_assoc();
        $grandTotal = floatval($bill['grand_total']);
        $currentPaid = floatval($bill['paid_amount']);
        $newPaid = $currentPaid + floatval($amount);
        
        // Don't allow overpayment
        if ($newPaid > $grandTotal) {
            echo json_encode(['success' => false, 'error' => 'Payment exceeds remaining amount']);
            return;
        }
        
        // Insert payment record
        $stmt2 = $conn->prepare("INSERT INTO udhari_payments (bill_id, user_id, amount, payment_date, note) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("iidss", $billId, $userId, $amount, $paymentDate, $note);
        $stmt2->execute();
        
        // Update bill paid_amount and due_status
        $dueStatus = ($newPaid >= $grandTotal) ? 'paid' : 'partial';
        $duePaidDate = ($newPaid >= $grandTotal) ? date('Y-m-d') : null;
        
        $stmt3 = $conn->prepare("UPDATE bills SET paid_amount = ?, due_status = ?, due_paid_date = ? WHERE id = ? AND user_id = ?");
        $stmt3->bind_param("dssii", $newPaid, $dueStatus, $duePaidDate, $billId, $userId);
        $stmt3->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded',
            'paid_amount' => $newPaid,
            'remaining' => $grandTotal - $newPaid,
            'due_status' => $dueStatus
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getDuePayments($userId) {
    global $conn;
    
    $billId = $_GET['billId'] ?? '';
    
    if (empty($billId)) {
        echo json_encode(['success' => false, 'error' => 'Bill ID required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM udhari_payments WHERE bill_id = ? AND user_id = ? ORDER BY payment_date DESC, created_at DESC");
        $stmt->bind_param("ii", $billId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        echo json_encode(['success' => true, 'payments' => $payments]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
