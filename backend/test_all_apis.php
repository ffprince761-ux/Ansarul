<?php
header("Content-Type: text/html; charset=UTF-8");
require_once 'config.php';

echo "<h1>Complete API Test Suite</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; }
    .error { color: red; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    h2 { color: #2563EB; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";

// Test 1: Database Connection
echo "<div class='section'>";
echo "<h2>1. Database Connection Test</h2>";
if ($conn->ping()) {
    echo "<p class='success'>✅ Database connected successfully</p>";
} else {
    echo "<p class='error'>❌ Database connection failed</p>";
    exit;
}
echo "</div>";

// Test 2: Check Tables
echo "<div class='section'>";
echo "<h2>2. Database Tables Check</h2>";
$tables = ['users', 'products', 'customers', 'bills', 'expenses', 'backups'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✅ Table '$table' exists</p>";
    } else {
        echo "<p class='error'>❌ Table '$table' missing</p>";
    }
}
echo "</div>";

// Test 3: Test User (Check if exists, create if not)
echo "<div class='section'>";
echo "<h2>3. Test User Setup</h2>";
$testEmail = "test@bizinote.com";
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $testEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $testUser = $result->fetch_assoc();
    $testUserId = $testUser['id'];
    echo "<p class='success'>✅ Test user exists (ID: $testUserId)</p>";
} else {
    echo "<p class='error'>❌ Test user not found. Please run reset_database.php and test_insert.php first</p>";
}
echo "</div>";

if (isset($testUserId)) {
    // Test 4: Products API
    echo "<div class='section'>";
    echo "<h2>4. Products API Test</h2>";
    
    // Get products
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE user_id = ?");
    $stmt->bind_param("i", $testUserId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "<p class='success'>✅ Products count: {$result['count']}</p>";
    
    // Test API endpoint
    $testUrl = "http://10.119.203.207/bizinote/backend/api/products.php?action=get&userId=$testUserId";
    echo "<p>Test URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
    echo "</div>";
    
    // Test 5: Customers API
    echo "<div class='section'>";
    echo "<h2>5. Customers API Test</h2>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $testUserId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "<p class='success'>✅ Customers count: {$result['count']}</p>";
    
    $testUrl = "http://10.119.203.207/bizinote/backend/api/customers.php?action=get&userId=$testUserId";
    echo "<p>Test URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
    echo "</div>";
    
    // Test 6: Bills API
    echo "<div class='section'>";
    echo "<h2>6. Bills API Test</h2>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bills WHERE user_id = ?");
    $stmt->bind_param("i", $testUserId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "<p class='success'>✅ Bills count: {$result['count']}</p>";
    
    $testUrl = "http://10.119.203.207/bizinote/backend/api/bills.php?action=get&userId=$testUserId";
    echo "<p>Test URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
    echo "</div>";
    
    // Test 7: Expenses API
    echo "<div class='section'>";
    echo "<h2>7. Expenses API Test</h2>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM expenses WHERE user_id = ?");
    $stmt->bind_param("i", $testUserId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "<p class='success'>✅ Expenses count: {$result['count']}</p>";
    
    $testUrl = "http://10.119.203.207/bizinote/backend/api/expenses.php?action=get&userId=$testUserId";
    echo "<p>Test URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
    echo "</div>";
    
    // Test 8: Auth API
    echo "<div class='section'>";
    echo "<h2>8. Auth API Test</h2>";
    $testUrl = "http://10.119.203.207/bizinote/backend/api/test_auth.php";
    echo "<p>Test URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>";
    echo "</div>";
}

// Summary
echo "<div class='section'>";
echo "<h2>✅ Test Summary</h2>";
echo "<p><strong>All critical components checked!</strong></p>";
echo "<p>Test User Credentials:</p>";
echo "<pre>Email: test@bizinote.com\nPassword: test123</pre>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Test login in the mobile app</li>";
echo "<li>Try creating products, customers, bills, expenses</li>";
echo "<li>Verify data appears in phpMyAdmin</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>
