<?php
/**
 * API: Get Expense Details
 */
require_once '../config/db.php';

header('Content-Type: application/json');

$expenseId = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$expenseId]);
    $expense = $stmt->fetch();
    
    if ($expense) {
        echo json_encode(['success' => true, 'expense' => $expense]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Expense not found']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
