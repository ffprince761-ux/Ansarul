<?php
require_once 'config/db.php';

echo "<h2>Timestamp Diagnostic & Fix</h2>";

// Check current timestamps
echo "<h3>Current Timestamps in Database:</h3>";
$stmt = $pdo->query("
    SELECT id, action, created_at, 
           UNIX_TIMESTAMP(created_at) as unix_created,
           UNIX_TIMESTAMP(NOW()) as unix_now,
           UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at) as diff_seconds
    FROM owner_activity_logs 
    ORDER BY created_at DESC 
    LIMIT 5
");
$logs = $stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Action</th><th>created_at</th><th>Unix Created</th><th>Unix Now</th><th>Diff (seconds)</th></tr>";
foreach ($logs as $log) {
    $color = $log['diff_seconds'] < 0 ? 'red' : 'green';
    echo "<tr>";
    echo "<td>{$log['id']}</td>";
    echo "<td>{$log['action']}</td>";
    echo "<td>{$log['created_at']}</td>";
    echo "<td>{$log['unix_created']}</td>";
    echo "<td>{$log['unix_now']}</td>";
    echo "<td style='color: {$color};'><strong>{$log['diff_seconds']}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Time Information:</h3>";
echo "<p>PHP time(): " . time() . "</p>";
echo "<p>PHP date(): " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP timezone: " . date_default_timezone_get() . "</p>";

$stmt = $pdo->query("SELECT NOW() as mysql_now, UNIX_TIMESTAMP(NOW()) as mysql_unix");
$result = $stmt->fetch();
echo "<p>MySQL NOW(): {$result['mysql_now']}</p>";
echo "<p>MySQL UNIX_TIMESTAMP(NOW()): {$result['mysql_unix']}</p>";

$phpTime = time();
$mysqlTime = $result['mysql_unix'];
$timeDiff = $phpTime - $mysqlTime;

echo "<p><strong>Time Difference (PHP - MySQL): {$timeDiff} seconds</strong></p>";

if (abs($timeDiff) > 60) {
    echo "<p style='color: red;'><strong>⚠️ WARNING: Significant time difference detected!</strong></p>";
    echo "<p>This will cause timestamp issues. Fix needed.</p>";
} else {
    echo "<p style='color: green;'>✅ Time sync is OK</p>";
}

echo "<hr>";
echo "<h3>Test timeAgo Function:</h3>";

require_once 'config/functions.php';

foreach ($logs as $log) {
    $timeAgo = timeAgo($log['created_at']);
    echo "<p>ID {$log['id']}: <strong>{$timeAgo}</strong> (created_at: {$log['created_at']})</p>";
}
?>
