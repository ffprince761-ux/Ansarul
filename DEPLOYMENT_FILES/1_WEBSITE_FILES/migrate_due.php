<?php
require_once 'config.php';

echo "Adding due_status and due_paid_date columns to bills table...\n";

try {
    // Check if due_status column exists
    $result = $conn->query("SHOW COLUMNS FROM bills LIKE 'due_status'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE bills ADD COLUMN due_status VARCHAR(20) DEFAULT 'paid'");
        echo "✅ Added due_status column\n";
    } else {
        echo "⚠️ due_status column already exists\n";
    }

    // Check if due_paid_date column exists
    $result = $conn->query("SHOW COLUMNS FROM bills LIKE 'due_paid_date'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE bills ADD COLUMN due_paid_date DATE DEFAULT NULL");
        echo "✅ Added due_paid_date column\n";
    } else {
        echo "⚠️ due_paid_date column already exists\n";
    }

    // Update existing Due bills to unpaid status
    $conn->query("UPDATE bills SET due_status = 'unpaid' WHERE payment_mode = 'Due' AND due_status = 'paid'");
    echo "✅ Updated existing Due bills\n";

    echo "\n✅ Migration complete!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
