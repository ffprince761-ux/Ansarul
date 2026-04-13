<?php
require_once 'config.php';

$tables = ['security_logs', 'blocked_ips', 'users', 'products', 'bills', 'customers', 'expenses'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "$table: EXISTS\n";
    } else {
        echo "$table: MISSING\n";
    }
}
?>
