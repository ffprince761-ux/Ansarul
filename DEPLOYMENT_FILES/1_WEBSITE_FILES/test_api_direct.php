<?php
/**
 * Direct API Test - Check if APIs are working
 */
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 Direct API Test</h2>";
echo "<hr>";

// Test 1: Database Connection
echo "<h3>Test 1: Database Connection</h3>";
if ($conn->ping()) {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

// Test 2: Check Users
echo "<hr><h3>Test 2: Users in Database</h3>";
$result = $conn->query("SELECT id, name, email, is_blocked FROM users");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>is_blocked</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['is_blocked']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No users found</p>";
}

// Test 3: Test Products API
echo "<hr><h3>Test 3: Products API Test</h3>";
$userId = 3; // Use actual user ID
echo "<p>Testing for User ID: <strong>{$userId}</strong></p>";

$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

echo "<p>Products found: <strong>{$result->num_rows}</strong></p>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['price']}</td>";
        echo "<td>{$row['stock']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No products found for this user</p>";
}

// Test 4: Direct API Call Simulation
echo "<hr><h3>Test 4: Simulate API Call</h3>";
$apiUrl = "http://localhost/bizinote/backend/api/products.php?action=get&userId={$userId}";
echo "<p>API URL: <code>{$apiUrl}</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status: <strong>{$httpCode}</strong></p>";
echo "<p>Response:</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$data = json_decode($response, true);
if ($data && isset($data['success']) && $data['success']) {
    echo "<p style='color: green;'>✅ API is working correctly</p>";
    echo "<p>Products returned: " . count($data['products'] ?? []) . "</p>";
} else {
    echo "<p style='color: red;'>❌ API returned error or no data</p>";
}

// Test 5: Check middleware file
echo "<hr><h3>Test 5: Middleware File Check</h3>";
$middlewarePath = __DIR__ . '/middleware/check_blocked.php';
if (file_exists($middlewarePath)) {
    echo "<p style='color: green;'>✅ Middleware file exists</p>";
    echo "<p>Path: <code>{$middlewarePath}</code></p>";
} else {
    echo "<p style='color: red;'>❌ Middleware file not found</p>";
}

echo "<hr>";
echo "<h3>📊 Summary</h3>";
echo "<p>If all tests pass, the issue is in the mobile app, not the backend.</p>";
echo "<p>If tests fail, there's a backend/database issue.</p>";
?>
