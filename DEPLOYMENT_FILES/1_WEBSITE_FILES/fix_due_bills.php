<?php
require_once 'config.php';

$result = $conn->query("UPDATE bills SET due_status = 'unpaid' WHERE payment_mode = 'Due' AND (due_status = 'paid' OR due_status IS NULL)");
echo "Updated " . $conn->affected_rows . " existing Due bills to unpaid status\n";

$result2 = $conn->query("SELECT id, customer_name, grand_total, payment_mode, due_status FROM bills WHERE payment_mode = 'Due'");
echo "\nAll Due bills:\n";
while ($row = $result2->fetch_assoc()) {
    echo "ID: {$row['id']} | {$row['customer_name']} | ₹{$row['grand_total']} | due_status: {$row['due_status']}\n";
}
?>
