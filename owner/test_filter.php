<?php
/**
 * Test Filter Functionality
 */
require_once 'config/db.php';

$userId = 1; // Test user ID
$period = 'yearly';

// Set date filter
$dateFilter = '';
switch($period) {
    case 'daily':
        $dateFilter = "AND DATE(date) = CURDATE()";
        break;
    case 'monthly':
        $dateFilter = "AND YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
        break;
    case 'yearly':
        $dateFilter = "AND YEAR(date) = YEAR(CURDATE())";
        break;
    default:
        $dateFilter = '';
}

echo "<h2>Testing Filter: $period</h2>";
echo "<p>Date Filter: $dateFilter</p>";

// Test bills query
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as bill_count,
        COALESCE(SUM(grand_total), 0) as total_revenue
    FROM bills
    WHERE user_id = ? $dateFilter
");
$stmt->execute([$userId]);
$billStats = $stmt->fetch();

echo "<h3>Bills:</h3>";
echo "Count: " . $billStats['bill_count'] . "<br>";
echo "Revenue: ₹" . number_format($billStats['total_revenue'], 2) . "<br>";

// Test expenses query
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as expense_count,
        COALESCE(SUM(amount), 0) as total_expenses
    FROM expenses
    WHERE user_id = ? $dateFilter
");
$stmt->execute([$userId]);
$expenseStats = $stmt->fetch();

echo "<h3>Expenses:</h3>";
echo "Count: " . $expenseStats['expense_count'] . "<br>";
echo "Amount: ₹" . number_format($expenseStats['total_expenses'], 2) . "<br>";

echo "<h3>Profit:</h3>";
$profit = $billStats['total_revenue'] - $expenseStats['total_expenses'];
echo "Profit: ₹" . number_format($profit, 2) . "<br>";

// Show actual dates in database
echo "<h3>Sample Bills Dates:</h3>";
$stmt = $pdo->prepare("SELECT date FROM bills WHERE user_id = ? ORDER BY date DESC LIMIT 5");
$stmt->execute([$userId]);
$dates = $stmt->fetchAll();
foreach ($dates as $d) {
    echo $d['date'] . "<br>";
}

echo "<h3>Sample Expense Dates:</h3>";
$stmt = $pdo->prepare("SELECT date FROM expenses WHERE user_id = ? ORDER BY date DESC LIMIT 5");
$stmt->execute([$userId]);
$dates = $stmt->fetchAll();
foreach ($dates as $d) {
    echo $d['date'] . "<br>";
}
?>
