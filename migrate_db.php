<?php
require_once 'backend/config.php';

$tables_to_check = [
    'security_logs' => [
        'success' => "TINYINT(1) DEFAULT 0",
        'user_id' => "INT NULL",
        'user_agent' => "TEXT"
    ],
    'users' => [
        'is_blocked' => "TINYINT(1) DEFAULT 0"
    ]
];

foreach ($tables_to_check as $table => $columns) {
    foreach ($columns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check->num_rows == 0) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Added column $column to $table\n";
        }
    }
}

echo "Schema migration complete.\n";
