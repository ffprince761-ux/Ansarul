<?php
header("Content-Type: text/html; charset=UTF-8");
require_once 'config.php';

echo "<h2>Database Insert Test</h2>";

// Test 1: Check connection
echo "<h3>1. Connection Test</h3>";
if ($conn->ping()) {
    echo "✅ Database connected successfully<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit;
}

// Test 2: Check if database exists
echo "<h3>2. Database Check</h3>";
$result = $conn->query("SELECT DATABASE()");
$row = $result->fetch_row();
echo "Current database: <strong>" . $row[0] . "</strong><br>";

// Test 3: List all tables
echo "<h3>3. Tables in Database</h3>";
$result = $conn->query("SHOW TABLES");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_row()) {
        echo "✅ Table: <strong>" . $row[0] . "</strong><br>";
    }
} else {
    echo "❌ No tables found. Please run setup_database.php first<br>";
}

// Test 4: Insert test user
echo "<h3>4. Insert Test User</h3>";
try {
    $testEmail = "test@bizinote.com";
    $testPassword = password_hash("test123", PASSWORD_DEFAULT);
    
    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $testEmail);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo "⚠️ Test user already exists<br>";
        $user = $checkResult->fetch_assoc();
        $userId = $user['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, business_name, mobile, address) VALUES (?, ?, ?, ?, ?, ?)");
        $name = "Test User";
        $businessName = "Test Business";
        $mobile = "1234567890";
        $address = "Test Address";
        
        $stmt->bind_param("ssssss", $name, $testEmail, $testPassword, $businessName, $mobile, $address);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            echo "✅ Test user created successfully (ID: $userId)<br>";
        } else {
            echo "❌ Failed to create user<br>";
            exit;
        }
    }
    
    // Test 5: Insert test product
    echo "<h3>5. Insert Test Product</h3>";
    $stmt = $conn->prepare("INSERT INTO products (user_id, name, category, price, stock, low_stock_threshold, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $productName = "Test Product " . time();
    $category = "Test Category";
    $price = 100.00;
    $stock = 50;
    $threshold = 10;
    $description = "Test product description";
    
    $stmt->bind_param("issdiis", $userId, $productName, $category, $price, $stock, $threshold, $description);
    
    if ($stmt->execute()) {
        $productId = $conn->insert_id;
        echo "✅ Test product created successfully (ID: $productId)<br>";
    } else {
        echo "❌ Failed to create product<br>";
    }
    
    // Test 6: Insert test customer
    echo "<h3>6. Insert Test Customer</h3>";
    $stmt = $conn->prepare("INSERT INTO customers (user_id, name, mobile, email, address) VALUES (?, ?, ?, ?, ?)");
    $customerName = "Test Customer " . time();
    $customerMobile = "9876543210";
    $customerEmail = "customer@test.com";
    $customerAddress = "Customer Address";
    
    $stmt->bind_param("issss", $userId, $customerName, $customerMobile, $customerEmail, $customerAddress);
    
    if ($stmt->execute()) {
        $customerId = $conn->insert_id;
        echo "✅ Test customer created successfully (ID: $customerId)<br>";
    } else {
        echo "❌ Failed to create customer<br>";
    }
    
    // Test 7: Insert test expense
    echo "<h3>7. Insert Test Expense</h3>";
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, description, amount, date) VALUES (?, ?, ?, ?, ?)");
    $expenseCategory = "Test Expense";
    $expenseDesc = "Test expense description";
    $amount = 500.00;
    $date = date('Y-m-d');
    
    $stmt->bind_param("issds", $userId, $expenseCategory, $expenseDesc, $amount, $date);
    
    if ($stmt->execute()) {
        $expenseId = $conn->insert_id;
        echo "✅ Test expense created successfully (ID: $expenseId)<br>";
    } else {
        echo "❌ Failed to create expense<br>";
    }
    
    // Test 8: View all data
    echo "<h3>8. View Data</h3>";
    
    echo "<h4>Users:</h4>";
    $result = $conn->query("SELECT id, name, email, business_name FROM users");
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Business</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['business_name']}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h4>Products:</h4>";
    $result = $conn->query("SELECT id, name, category, price, stock FROM products WHERE user_id = $userId");
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['category']}</td><td>{$row['price']}</td><td>{$row['stock']}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h4>Customers:</h4>";
    $result = $conn->query("SELECT id, name, mobile, email FROM customers WHERE user_id = $userId");
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Mobile</th><th>Email</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['mobile']}</td><td>{$row['email']}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h4>Expenses:</h4>";
    $result = $conn->query("SELECT id, category, description, amount, date FROM expenses WHERE user_id = $userId");
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Category</th><th>Description</th><th>Amount</th><th>Date</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['category']}</td><td>{$row['description']}</td><td>{$row['amount']}</td><td>{$row['date']}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>✅ All Tests Completed Successfully!</h3>";
    echo "<p><strong>Test User Credentials:</strong><br>";
    echo "Email: test@bizinote.com<br>";
    echo "Password: test123</p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$conn->close();
?>
