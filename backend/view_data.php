<!DOCTYPE html>
<html>
<head>
    <title>Bizinote Database Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #2563EB; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2563EB; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .success { color: green; font-weight: bold; }
        .count { background: #10B981; color: white; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>📊 Bizinote Database Viewer</h1>
    
<?php
$host = 'localhost';
$dbname = 'bizinote_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Database Connected Successfully</p>";
    
    // Show Customers
    echo "<h2>👥 Customers <span class='count'>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers");
    echo $stmt->fetch()['count'];
    echo "</span></h2>";
    
    $stmt = $conn->query("SELECT * FROM customers ORDER BY created_at DESC LIMIT 10");
    echo "<table><tr><th>ID</th><th>User ID</th><th>Name</th><th>Mobile</th><th>Email</th><th>Created At</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['mobile']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show Bills
    echo "<h2>🧾 Bills <span class='count'>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM bills");
    echo $stmt->fetch()['count'];
    echo "</span></h2>";
    
    $stmt = $conn->query("SELECT * FROM bills ORDER BY created_at DESC LIMIT 10");
    echo "<table><tr><th>ID</th><th>Invoice #</th><th>Customer</th><th>Total</th><th>Payment Mode</th><th>Date</th><th>Created At</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['invoice_number']}</td>";
        echo "<td>{$row['customer_name']}</td>";
        echo "<td>₹{$row['grand_total']}</td>";
        echo "<td>{$row['payment_mode']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show Products
    echo "<h2>📦 Products <span class='count'>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    echo $stmt->fetch()['count'];
    echo "</span></h2>";
    
    $stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10");
    echo "<table><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Created At</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['category']}</td>";
        echo "<td>₹{$row['price']}</td>";
        echo "<td>{$row['stock']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show Expenses
    echo "<h2>💰 Expenses <span class='count'>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM expenses");
    echo $stmt->fetch()['count'];
    echo "</span></h2>";
    
    $stmt = $conn->query("SELECT * FROM expenses ORDER BY date DESC LIMIT 10");
    echo "<table><tr><th>ID</th><th>User ID</th><th>Category</th><th>Amount</th><th>Description</th><th>Date</th><th>Created At</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['category']}</td>";
        echo "<td>₹{$row['amount']}</td>";
        echo "<td>{$row['description']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<p><a href="javascript:location.reload()">🔄 Refresh Data</a></p>

</body>
</html>
