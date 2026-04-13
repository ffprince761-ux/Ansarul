<?php
/**
 * API: Get Bill Details with Payment History
 */
require_once '../config/db.php';

header('Content-Type: application/json');

$billId = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT b.*, u.business_name, u.name as owner_name FROM bills b LEFT JOIN users u ON b.user_id = u.id WHERE b.id = ?");
    $stmt->execute([$billId]);
    $bill = $stmt->fetch();
    
    if ($bill) {
        // Get payment history
        $payments = [];
        try {
            $stmt = $pdo->prepare("SELECT * FROM udhari_payments WHERE bill_id = ? ORDER BY payment_date ASC, created_at ASC");
            $stmt->execute([$billId]);
            $payments = $stmt->fetchAll();
        } catch(Exception $e) {}
        
        echo json_encode(['success' => true, 'bill' => $bill, 'payments' => $payments]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Bill not found']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
