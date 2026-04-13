<?php
require_once 'config.php';

echo "Adding partial payment support...\n";

try {
    // Add paid_amount column to bills
    $result = $conn->query("SHOW COLUMNS FROM bills LIKE 'paid_amount'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE bills ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0");
        echo "✅ Added paid_amount column to bills\n";
    } else {
        echo "⚠️ paid_amount column already exists\n";
    }

    // Create udhari_payments table
    $conn->query("CREATE TABLE IF NOT EXISTS udhari_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT NOT NULL,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bill_id (bill_id),
        INDEX idx_user_id (user_id)
    )");
    echo "✅ Created udhari_payments table\n";

    // Set paid_amount = grand_total for already paid Due bills
    $conn->query("UPDATE bills SET paid_amount = grand_total WHERE payment_mode = 'Due' AND due_status = 'paid' AND (paid_amount IS NULL OR paid_amount = 0)");
    echo "✅ Updated paid Due bills\n";

    // Set paid_amount = 0 for unpaid Due bills
    $conn->query("UPDATE bills SET paid_amount = 0 WHERE payment_mode = 'Due' AND due_status = 'unpaid' AND paid_amount IS NULL");
    echo "✅ Updated unpaid Due bills\n";

    echo "\n✅ Migration complete!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
