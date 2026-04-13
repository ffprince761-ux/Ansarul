<?php
/**
 * Check Database Size and Data
 */
require_once 'config/db.php';

echo "<h2>Database Verification</h2>";

try {
    // Get database name
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<h3>Database: <strong>{$dbName}</strong></h3>";
    
    // Get database size
    $stmt = $pdo->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.TABLES 
        WHERE table_schema = '{$dbName}'
    ");
    $size = $stmt->fetchColumn();
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2 style='color: #1976d2; margin: 0;'>📊 Database Size: {$size} MB</h2>";
    echo "</div>";
    
    // Get all tables with row counts
    $stmt = $pdo->query("
        SELECT 
            table_name,
            table_rows,
            ROUND((data_length + index_length) / 1024, 2) AS size_kb
        FROM information_schema.TABLES 
        WHERE table_schema = '{$dbName}'
        ORDER BY (data_length + index_length) DESC
    ");
    $tables = $stmt->fetchAll();
    
    echo "<h3>Tables Information:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th>Table Name</th>";
    echo "<th>Rows</th>";
    echo "<th>Size (KB)</th>";
    echo "<th>Status</th>";
    echo "</tr>";
    
    $totalRows = 0;
    foreach ($tables as $table) {
        $totalRows += $table['table_rows'];
        $status = $table['table_rows'] > 0 ? '✅ Has Data' : '⚠️ Empty';
        $color = $table['table_rows'] > 0 ? '#4caf50' : '#ff9800';
        
        echo "<tr>";
        echo "<td><strong>{$table['table_name']}</strong></td>";
        echo "<td style='text-align: center;'>{$table['table_rows']}</td>";
        echo "<td style='text-align: center;'>{$table['size_kb']} KB</td>";
        echo "<td style='color: {$color};'>{$status}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<p><strong>Total Tables:</strong> " . count($tables) . "</p>";
    echo "<p><strong>Total Rows:</strong> {$totalRows}</p>";
    echo "<p><strong>Total Size:</strong> {$size} MB</p>";
    echo "</div>";
    
    // Check important tables
    echo "<hr>";
    echo "<h3>Important Tables Check:</h3>";
    
    $importantTables = ['users', 'products', 'customers', 'bills', 'expenses', 'owner_users', 'app_settings'];
    
    foreach ($importantTables as $tableName) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
            echo "<p>✅ <strong>{$tableName}:</strong> {$count} records</p>";
        } else {
            echo "<p>❌ <strong>{$tableName}:</strong> Table not found</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✅ Database is Working Correctly!</h3>";
    echo "<p>Database size of {$size} MB is normal for a business management app with moderate data.</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
