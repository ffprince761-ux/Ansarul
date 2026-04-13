<?php
require_once 'config/db.php';

echo "<h2>Check Timestamps</h2>";

// Check owner_activity_logs timestamps
$stmt = $pdo->query("SELECT id, action, created_at, NOW() as current_time FROM owner_activity_logs ORDER BY created_at DESC LIMIT 10");
$logs = $stmt->fetchAll();

echo "<h3>Owner Activity Logs Timestamps:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Action</th><th>created_at</th><th>Current Time</th><th>Difference</th></tr>";

foreach ($logs as $log) {
    $created = strtotime($log['created_at']);
    $current = strtotime($log['current_time']);
    $diff = $current - $created;
    
    echo "<tr>";
    echo "<td>{$log['id']}</td>";
    echo "<td>{$log['action']}</td>";
    echo "<td>{$log['created_at']}</td>";
    echo "<td>{$log['current_time']}</td>";
    echo "<td>" . ($diff >= 0 ? "+{$diff}s" : "{$diff}s") . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Timezone Check:</h3>";
echo "<p>PHP Timezone: " . date_default_timezone_get() . "</p>";
echo "<p>PHP Current Time: " . date('Y-m-d H:i:s') . "</p>";

$stmt = $pdo->query("SELECT NOW() as db_time, @@session.time_zone as db_timezone");
$result = $stmt->fetch();
echo "<p>MySQL Current Time: {$result['db_time']}</p>";
echo "<p>MySQL Timezone: {$result['db_timezone']}</p>";
?>
