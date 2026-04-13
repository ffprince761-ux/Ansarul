<?php
require_once 'config.php';

echo "Adding due_date column to bills table...\n";

try {
    $result = $conn->query("SHOW COLUMNS FROM bills LIKE 'due_date'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE bills ADD COLUMN due_date DATE DEFAULT NULL");
        echo "✅ Added due_date column\n";
    } else {
        echo "⚠️ due_date column already exists\n";
    }
    echo "✅ Migration complete!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
