<?php
/**
 * Database Data Checker
 * Check if data exists in bizinote database
 */

$host = "localhost";
$username = "root";
$password = "";
$dbname = "bizinote";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Checker</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .warning { color: orange; background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        h3 { color: #667eea; margin-top: 30px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Database Data Checker</h1>

<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Connected to database: <strong>$dbname</strong></div>";
    
    // Check Users
    echo "<h3>👥 Users Table:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount > 0) {
        echo "<div class='success'>✅ Found $userCount users</div>";
        
        $stmt = $pdo->query("SELECT id, name, email, business_name, created_at FROM users LIMIT 10");
        $users = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Business</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['business_name'] ?: 'N/A') . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No users found in database</div>";
    }
    
    // Check Bills
    echo "<h3>🧾 Bills Table:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bills");
    $billCount = $stmt->fetch()['count'];
    
    if ($billCount > 0) {
        echo "<div class='success'>✅ Found $billCount bills</div>";
        
        $stmt = $pdo->query("SELECT SUM(grand_total) as total FROM bills");
        $totalRevenue = $stmt->fetch()['total'];
        echo "<div class='success'>💰 Total Revenue: ₹" . number_format($totalRevenue, 2) . "</div>";
        
        $stmt = $pdo->query("SELECT id, invoice_number, customer_name, grand_total, date FROM bills ORDER BY date DESC LIMIT 10");
        $bills = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Invoice</th><th>Customer</th><th>Amount</th><th>Date</th></tr>";
        foreach ($bills as $bill) {
            echo "<tr>";
            echo "<td>" . $bill['id'] . "</td>";
            echo "<td>" . htmlspecialchars($bill['invoice_number']) . "</td>";
            echo "<td>" . htmlspecialchars($bill['customer_name']) . "</td>";
            echo "<td>₹" . number_format($bill['grand_total'], 2) . "</td>";
            echo "<td>" . $bill['date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No bills found in database</div>";
    }
    
    // Check Products
    echo "<h3>📦 Products Table:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch()['count'];
    
    if ($productCount > 0) {
        echo "<div class='success'>✅ Found $productCount products</div>";
    } else {
        echo "<div class='warning'>⚠️ No products found in database</div>";
    }
    
    // Check Customers
    echo "<h3>👤 Customers Table:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
    $customerCount = $stmt->fetch()['count'];
    
    if ($customerCount > 0) {
        echo "<div class='success'>✅ Found $customerCount customers</div>";
    } else {
        echo "<div class='warning'>⚠️ No customers found in database</div>";
    }
    
    // Check Expenses
    echo "<h3>💸 Expenses Table:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM expenses");
    $expenseCount = $stmt->fetch()['count'];
    
    if ($expenseCount > 0) {
        echo "<div class='success'>✅ Found $expenseCount expenses</div>";
        
        $stmt = $pdo->query("SELECT SUM(amount) as total FROM expenses");
        $totalExpenses = $stmt->fetch()['total'];
        echo "<div class='success'>💸 Total Expenses: ₹" . number_format($totalExpenses, 2) . "</div>";
    } else {
        echo "<div class='warning'>⚠️ No expenses found in database</div>";
    }
    
    // Summary
    echo "<h3>📊 Summary:</h3>";
    echo "<table>";
    echo "<tr><th>Table</th><th>Count</th></tr>";
    echo "<tr><td>Users</td><td><strong>$userCount</strong></td></tr>";
    echo "<tr><td>Bills</td><td><strong>$billCount</strong></td></tr>";
    echo "<tr><td>Products</td><td><strong>$productCount</strong></td></tr>";
    echo "<tr><td>Customers</td><td><strong>$customerCount</strong></td></tr>";
    echo "<tr><td>Expenses</td><td><strong>$expenseCount</strong></td></tr>";
    echo "</table>";
    
    if ($userCount > 0 || $billCount > 0) {
        echo "<div class='success'>";
        echo "<h4>✅ Database has data!</h4>";
        echo "<p>If owner panel shows empty, there might be a query issue.</p>";
        echo "<p><a href='dashboard.php' style='color: #667eea;'>Go to Dashboard</a> and refresh the page.</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h4>⚠️ Database is empty</h4>";
        echo "<p>No users or bills found. Owner panel will show zero data.</p>";
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}
?>

</div>
</body>
</html>
