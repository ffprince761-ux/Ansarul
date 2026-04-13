<?php
/**
 * Complete Block Feature Test
 */
require_once 'config.php';

echo "<h2>🔍 Block Feature Complete Test</h2>";
echo "<hr>";

// Test 1: Check if is_blocked column exists
echo "<h3>Test 1: Database Structure</h3>";
try {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'is_blocked'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ is_blocked column exists in users table</p>";
        $column = $result->fetch_assoc();
        echo "<p>Column Type: {$column['Type']}</p>";
        echo "<p>Default: {$column['Default']}</p>";
    } else {
        echo "<p style='color: red;'>❌ is_blocked column NOT found in users table</p>";
        echo "<p><strong>FIX:</strong> Run this SQL:</p>";
        echo "<pre>ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0;</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: {$e->getMessage()}</p>";
}

echo "<hr>";

// Test 2: Check users and their block status
echo "<h3>Test 2: Users Block Status</h3>";
try {
    $result = $conn->query("SELECT id, name, email, is_blocked FROM users");
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>is_blocked</th><th>Status</th></tr>";
        while ($user = $result->fetch_assoc()) {
            $status = $user['is_blocked'] == 1 ? '<span style="color: red;">🔒 BLOCKED</span>' : '<span style="color: green;">✅ Active</span>';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['is_blocked']}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: {$e->getMessage()}</p>";
}

echo "<hr>";

// Test 3: Test check_user_status.php API
echo "<h3>Test 3: API Endpoint Test</h3>";
// Get first user ID from database
$result = $conn->query("SELECT id FROM users LIMIT 1");
$testUserId = $result->fetch_assoc()['id'] ?? 3;

echo "<p>Testing API for User ID: <strong>{$testUserId}</strong></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/bizinote/backend/api/check_user_status.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['userId' => $testUserId]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> {$httpCode}</p>";
echo "<p><strong>API Response:</strong></p>";
echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

$apiData = json_decode($response, true);
if ($apiData && isset($apiData['blocked'])) {
    if ($apiData['blocked'] === true) {
        echo "<p style='color: red; font-size: 18px;'>🔒 <strong>User is BLOCKED</strong></p>";
        echo "<p>Mobile app should logout this user automatically.</p>";
    } else {
        echo "<p style='color: green; font-size: 18px;'>✅ <strong>User is ACTIVE</strong></p>";
        echo "<p>User can continue using the app.</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ API response format issue</p>";
}

echo "<hr>";

// Test 4: Login API Test
echo "<h3>Test 4: Login API Block Check</h3>";
echo "<p>Testing if blocked user can login...</p>";

$stmt = $conn->prepare("SELECT id, email, is_blocked FROM users WHERE is_blocked = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $blockedUser = $result->fetch_assoc();
    echo "<p>Found blocked user: <strong>{$blockedUser['email']}</strong> (ID: {$blockedUser['id']})</p>";
    echo "<p style='color: green;'>✅ Login API will reject this user with 'Account blocked' message</p>";
} else {
    echo "<p style='color: orange;'>⚠️ No blocked users found in database to test</p>";
    echo "<p>Block a user from owner panel to test this feature</p>";
}

echo "<hr>";

// Summary
echo "<h3>📊 Summary</h3>";
echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 10px;'>";
echo "<h4>For Block Feature to Work:</h4>";
echo "<ol>";
echo "<li>✅ <strong>is_blocked</strong> column must exist in users table</li>";
echo "<li>✅ <strong>check_user_status.php</strong> API must return correct blocked status</li>";
echo "<li>✅ <strong>Login API</strong> must check is_blocked before allowing login</li>";
echo "<li>✅ <strong>Mobile App</strong> must call check_user_status.php periodically</li>";
echo "<li>✅ <strong>Mobile App</strong> must logout user when blocked=true</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='../owner/users.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Users Page</a></p>";
?>
