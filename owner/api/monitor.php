<?php
/**
 * Advanced System Monitoring API
 * Returns real-time server, database, API, and user monitoring data
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log API calls
error_log("Monitor API called: " . $_SERVER['REQUEST_URI'] . " at " . date('Y-m-d H:i:s'));

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

// Auto-create missing tables
try {
    // Enhanced error logging table
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_error_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT DEFAULT NULL,
        error_type VARCHAR(100) DEFAULT 'general',
        error_message TEXT NOT NULL,
        stack_trace TEXT,
        file_path VARCHAR(500),
        line_number INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        request_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_error_type (error_type),
        INDEX idx_user_id (user_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE,
        mobile VARCHAR(20),
        business_name VARCHAR(255),
        password VARCHAR(255),
        role VARCHAR(50) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT DEFAULT 0,
        category VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        mobile VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS bills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        customer_id INT DEFAULT NULL,
        bill_number VARCHAR(100) UNIQUE,
        customer_name VARCHAR(255),
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        paid_amount DECIMAL(10,2) DEFAULT 0,
        payment_mode VARCHAR(50),
        due_status VARCHAR(20) DEFAULT 'paid',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_customer_id (customer_id),
        INDEX idx_created (created_at)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS bill_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        bill_id INT NOT NULL,
        product_id INT DEFAULT NULL,
        product_name VARCHAR(255),
        quantity DECIMAL(10,2) DEFAULT 1,
        price DECIMAL(10,2) DEFAULT 0,
        discount DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bill_id (bill_id),
        INDEX idx_product_id (product_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        category VARCHAR(100),
        description TEXT,
        amount DECIMAL(10,2) NOT NULL,
        expense_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_expense_date (expense_date)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_setting_key (setting_key)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_error_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT DEFAULT NULL,
        error_message TEXT,
        stack_trace TEXT,
        device_info VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_user_id (user_id)
    )");
    
} catch (Exception $e) {
    error_log("Table creation error: " . $e->getMessage());
}

$action = $_GET['action'] ?? 'full';

try {
    switch ($action) {
        case 'full':
            echo json_encode([
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'server' => getServerStatus(),
                'database' => getDatabaseStatus($pdo),
                'apis' => getApiStatus(),
                'users' => getUserActivity($pdo),
                'bills' => getBillTracking($pdo, []),
                'errors' => getErrorLogs($pdo),
                'performance' => getPerformanceMetrics($pdo)
            ]);
            break;
        case 'ping':
            echo json_encode(['success' => true, 'pong' => microtime(true), 'time' => date('H:i:s')]);
            break;
        case 'speed':
            echo json_encode(['success' => true, 'data' => getSpeedTest($pdo)]);
            break;
        case 'filtered_bills':
            $filters = [
                'bill_number' => $_GET['bill_number'] ?? null,
                'invoice_number' => $_GET['invoice_number'] ?? null,
                'from_date' => $_GET['from_date'] ?? null,
                'to_date' => $_GET['to_date'] ?? null,
                'limit' => $_GET['limit'] ?? 50
            ];
            echo json_encode(['success' => true, 'bills' => getBillTracking($pdo, $filters)]);
            break;
        case 'log_error':
            // Manual error logging for testing
            $error_type = $_POST['error_type'] ?? 'test';
            $error_message = $_POST['error_message'] ?? 'Test error message';
            $file_path = $_POST['file_path'] ?? 'unknown.php';
            $line_number = $_POST['line_number'] ?? 0;
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO app_error_logs 
                    (error_type, error_message, file_path, line_number, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $error_type,
                    $error_message,
                    $file_path,
                    $line_number,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                echo json_encode(['success' => true, 'message' => 'Error logged successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Monitor API Error: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ===== SERVER STATUS =====
function getServerStatus() {
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'os' => PHP_OS,
        'max_memory' => ini_get('memory_limit'),
        'memory_used' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        'max_upload' => ini_get('upload_max_filesize'),
        'max_post' => ini_get('post_max_size'),
        'max_execution' => ini_get('max_execution_time') . 's',
        'timezone' => date_default_timezone_get(),
        'current_time' => date('Y-m-d H:i:s'),
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'disk_free' => round(@disk_free_space('.') / 1024 / 1024 / 1024, 2) . ' GB',
        'disk_total' => round(@disk_total_space('.') / 1024 / 1024 / 1024, 2) . ' GB',
        'disk_used_percent' => @disk_total_space('.') > 0 
            ? round((1 - disk_free_space('.') / disk_total_space('.')) * 100, 1) 
            : 0,
        'server_uptime' => 'N/A (shell_exec disabled)',
        'extensions' => [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl'),
            'gd' => extension_loaded('gd'),
            'openssl' => extension_loaded('openssl'),
        ]
    ];
}

// ===== DATABASE STATUS =====
function getDatabaseStatus($pdo) {
    $tables = ['users', 'products', 'bills', 'bill_items', 'customers', 'expenses', 'app_settings', 'app_error_logs'];
    $tableStatus = [];
    $totalRecords = 0;
    
    foreach ($tables as $table) {
        try {
            $start = microtime(true);
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
            $time = round((microtime(true) - $start) * 1000, 2);
            $row = $stmt->fetch();
            $count = $row['cnt'];
            $totalRecords += $count;
            
            // Get table size
            $sizeStmt = $pdo->query("SELECT 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() AND table_name = '$table'");
            $sizeRow = $sizeStmt->fetch();
            
            $tableStatus[$table] = [
                'status' => 'online',
                'records' => $count,
                'query_time_ms' => $time,
                'size_mb' => $sizeRow['size_mb'] ?? '0.00'
            ];
        } catch (Exception $e) {
            $tableStatus[$table] = [
                'status' => 'error',
                'records' => 0,
                'query_time_ms' => 0,
                'size_mb' => '0.00',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // MySQL server info
    $mysqlVersion = $pdo->query("SELECT VERSION() as v")->fetch()['v'];
    $mysqlUptime = $pdo->query("SHOW STATUS LIKE 'Uptime'")->fetch();
    $mysqlQueries = $pdo->query("SHOW STATUS LIKE 'Questions'")->fetch();
    $mysqlConnections = $pdo->query("SHOW STATUS LIKE 'Threads_connected'")->fetch();
    $mysqlMaxConn = $pdo->query("SHOW VARIABLES LIKE 'max_connections'")->fetch();
    
    // DB size
    $dbSize = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as total_mb 
        FROM information_schema.TABLES WHERE table_schema = DATABASE()")->fetch();
    
    return [
        'mysql_version' => $mysqlVersion,
        'uptime_seconds' => (int)($mysqlUptime['Value'] ?? 0),
        'uptime_formatted' => formatUptime((int)($mysqlUptime['Value'] ?? 0)),
        'total_queries' => number_format((int)($mysqlQueries['Value'] ?? 0)),
        'active_connections' => (int)($mysqlConnections['Value'] ?? 0),
        'max_connections' => (int)($mysqlMaxConn['Value'] ?? 0),
        'connection_usage_percent' => ($mysqlMaxConn['Value'] ?? 1) > 0 
            ? round(($mysqlConnections['Value'] / $mysqlMaxConn['Value']) * 100, 1) : 0,
        'database_size_mb' => $dbSize['total_mb'] ?? '0.00',
        'total_records' => $totalRecords,
        'tables' => $tableStatus
    ];
}

function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $mins = floor(($seconds % 3600) / 60);
    return "{$days}d {$hours}h {$mins}m";
}

// ===== API HEALTH =====
function getApiStatus() {
    $endpoints = [
        'Auth' => '/auth.php?action=test',
        'Products' => '/products.php?action=get&userId=1',
        'Bills' => '/bills.php?action=get&userId=1',
        'Customers' => '/customers.php?action=get&userId=1',
        'Expenses' => '/expenses.php?action=get&userId=1',
        'Settings' => '/get_app_settings.php',
    ];
    
    $results = [];
    $totalTime = 0;
    $working = 0;
    
    foreach ($endpoints as $name => $endpoint) {
        $url = 'https://tensemock.in/api' . $endpoint;
        $start = microtime(true);
        
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($url, false, $context);
        $time = round((microtime(true) - $start) * 1000, 1);
        $totalTime += $time;
        
        $isSuccess = false;
        $statusCode = 0;
        $dataSize = 0;
        
        if ($response !== false) {
            $data = json_decode($response, true);
            $isSuccess = true;
            $statusCode = 200;
            $dataSize = strlen($response);
            $working++;
        } else {
            $statusCode = 500;
        }
        
        $speed = 'fast';
        if ($time > 1000) $speed = 'slow';
        elseif ($time > 500) $speed = 'medium';
        
        $results[] = [
            'name' => $name,
            'endpoint' => $endpoint,
            'status' => $isSuccess ? 'online' : 'offline',
            'status_code' => $statusCode,
            'response_time_ms' => $time,
            'speed' => $speed,
            'data_size' => $dataSize,
        ];
    }
    
    return [
        'total' => count($endpoints),
        'online' => $working,
        'offline' => count($endpoints) - $working,
        'avg_response_ms' => count($endpoints) > 0 ? round($totalTime / count($endpoints), 1) : 0,
        'total_response_ms' => round($totalTime, 1),
        'health_percent' => count($endpoints) > 0 ? round(($working / count($endpoints)) * 100) : 0,
        'endpoints' => $results
    ];
}

// ===== USER ACTIVITY =====
function getUserActivity($pdo) {
    try {
        // Create active_sessions table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS active_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            user_name VARCHAR(255),
            business_name VARCHAR(255),
            device_info VARCHAR(500),
            app_screen VARCHAR(100) DEFAULT 'Home',
            last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user (user_id)
        )");
        
        // Cleanup stale sessions (>2 min no ping = offline)
        $pdo->exec("DELETE FROM active_sessions WHERE last_ping < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        
        // Total users
        $totalUsers = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
        
        // Users registered today
        $todayUsers = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE DATE(created_at) = CURDATE()")->fetch()['cnt'];
        
        // Users registered this week
        $weekUsers = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['cnt'];
        
        // LIVE online users (from heartbeat)
        $liveOnline = [];
        try {
            $stmt = $pdo->query("SELECT * FROM active_sessions ORDER BY last_ping DESC");
            $liveOnline = $stmt->fetchAll();
        } catch (Exception $e) {}
        
        // All users with activity data
        $allUsers = [];
        try {
            $stmt = $pdo->query("
                SELECT u.id, u.name, u.business_name, u.email, u.mobile,
                    u.created_at as registered,
                    (SELECT COUNT(*) FROM bills WHERE user_id = u.id) as total_bills,
                    (SELECT COUNT(*) FROM bills WHERE user_id = u.id AND DATE(created_at) = CURDATE()) as today_bills,
                    (SELECT COUNT(*) FROM expenses WHERE user_id = u.id) as total_expenses,
                    (SELECT COUNT(*) FROM products WHERE user_id = u.id) as total_products,
                    (SELECT MAX(created_at) FROM bills WHERE user_id = u.id) as last_bill,
                    (SELECT MAX(created_at) FROM expenses WHERE user_id = u.id) as last_expense
                FROM users u
                ORDER BY u.id DESC
                LIMIT 20
            ");
            $allUsers = $stmt->fetchAll();
            
            $onlineIds = array_column($liveOnline, 'user_id');
            foreach ($allUsers as &$user) {
                if (in_array($user['id'], $onlineIds)) {
                    $user['status'] = 'online';
                    $session = array_filter($liveOnline, fn($s) => $s['user_id'] == $user['id']);
                    $session = reset($session);
                    $user['device_info'] = $session['device_info'] ?? '';
                    $user['app_screen'] = $session['app_screen'] ?? '';
                    $user['session_start'] = $session['session_start'] ?? '';
                    $user['last_ping'] = $session['last_ping'] ?? '';
                } else {
                    $lastActivity = max(
                        strtotime($user['last_bill'] ?: '2000-01-01'),
                        strtotime($user['last_expense'] ?: '2000-01-01')
                    );
                    $diff = time() - $lastActivity;
                    if ($diff < 3600) $user['status'] = 'recent';
                    elseif ($diff < 86400) $user['status'] = 'today';
                    else $user['status'] = 'offline';
                    $user['last_activity'] = date('Y-m-d H:i:s', $lastActivity);
                }
            }
            
            // Sort: online first, then recent, then today, then offline
            usort($allUsers, function($a, $b) {
                $order = ['online' => 0, 'recent' => 1, 'today' => 2, 'offline' => 3];
                return ($order[$a['status']] ?? 9) - ($order[$b['status']] ?? 9);
            });
        } catch (Exception $e) {}
        
        return [
            'total' => (int)$totalUsers,
            'today' => (int)$todayUsers,
            'this_week' => (int)$weekUsers,
            'live_online' => count($liveOnline),
            'active_users' => $allUsers,
            'live_sessions' => $liveOnline
        ];
    } catch (Exception $e) {
        return ['total' => 0, 'today' => 0, 'this_week' => 0, 'live_online' => 0, 'active_users' => [], 'live_sessions' => []];
    }
}

// ===== BILL TRACKING =====
function getBillTracking($pdo, $filters = []) {
    try {
        $todayBills = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as total FROM bills WHERE DATE(created_at) = CURDATE()")->fetch();
        $weekBills = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as total FROM bills WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch();
        $totalBills = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as total FROM bills")->fetch();
        
        // Due bills
        $dueBills = $pdo->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total - COALESCE(paid_amount, 0)), 0) as pending FROM bills WHERE due_status = 'due' OR due_status = 'partial'")->fetch();
        
        // Recent bills (emergency fix - simplest query)
        $recentBills = [];
        try {
            // Ultra-simple query - no joins, no COALESCE, no filters
            $stmt = $pdo->query("SELECT * FROM bills ORDER BY id DESC LIMIT 10");
            $recentBills = $stmt->fetchAll();
            
            // Add missing fields with defaults
            foreach ($recentBills as &$bill) {
                $bill['bill_number'] = $bill['invoice_number'] ?? ('INV' . $bill['id']);
                $bill['customer_name'] = $bill['customer_name'] ?? 'Walk-in Customer';
                $bill['payment_mode'] = $bill['payment_mode'] ?? 'Cash';
                $bill['due_status'] = $bill['due_status'] ?? 'paid';
                $bill['user_name'] = 'System';
                $bill['business_name'] = 'Default Business';
            }
            
        } catch (Exception $e) {
            error_log("Recent bills error: " . $e->getMessage());
            $recentBills = [];
        }
        
        // Bill trend (last 7 days)
        $trend = [];
        try {
            $stmt = $pdo->query("
                SELECT DATE(created_at) as date, COUNT(*) as count, COALESCE(SUM(total), 0) as revenue 
                FROM bills 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY DATE(created_at) 
                ORDER BY date ASC
            ");
            $trend = $stmt->fetchAll();
        } catch (Exception $e) {}
        
        // Hourly distribution today
        $hourly = [];
        try {
            $stmt = $pdo->query("
                SELECT HOUR(created_at) as hour, COUNT(*) as count 
                FROM bills 
                WHERE DATE(created_at) = CURDATE() 
                GROUP BY HOUR(created_at) 
                ORDER BY hour ASC
            ");
            $hourly = $stmt->fetchAll();
        } catch (Exception $e) {}
        
        return [
            'today_count' => (int)$todayBills['cnt'],
            'today_revenue' => round((float)$todayBills['total'], 2),
            'week_count' => (int)$weekBills['cnt'],
            'week_revenue' => round((float)$weekBills['total'], 2),
            'total_count' => (int)$totalBills['cnt'],
            'total_revenue' => round((float)$totalBills['total'], 2),
            'due_count' => (int)$dueBills['cnt'],
            'due_pending' => round((float)$dueBills['pending'], 2),
            'recent' => $recentBills,
            'trend' => $trend,
            'hourly' => $hourly
        ];
    } catch (Exception $e) {
        return ['today_count' => 0, 'today_revenue' => 0, 'week_count' => 0, 'week_revenue' => 0, 'total_count' => 0, 'total_revenue' => 0, 'due_count' => 0, 'due_pending' => 0, 'recent' => [], 'trend' => [], 'hourly' => []];
    }
}

// ===== ERROR LOGS =====
function getErrorLogs($pdo) {
    $logs = [];
    $totalErrors = 0;
    $todayErrors = 0;
    $errorTypes = [];
    
    try {
        // Get recent logs with enhanced info
        $stmt = $pdo->query("
            SELECT id, error_type, error_message, file_path, line_number, 
                   ip_address, created_at,
                   LEFT(error_message, 100) as short_message
            FROM app_error_logs 
            ORDER BY created_at DESC LIMIT 20
        ");
        $logs = $stmt->fetchAll();
        
        // Get error counts
        $totalErrors = $pdo->query("SELECT COUNT(*) as cnt FROM app_error_logs")->fetch()['cnt'];
        $todayErrors = $pdo->query("SELECT COUNT(*) as cnt FROM app_error_logs WHERE DATE(created_at) = CURDATE()")->fetch()['cnt'];
        
        // Get error types distribution
        $stmt = $pdo->query("
            SELECT error_type, COUNT(*) as count 
            FROM app_error_logs 
            GROUP BY error_type 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $errorTypes = $stmt->fetchAll();
        
    } catch (Exception $e) {
        // Table might not exist or other error
        error_log("Error logs function error: " . $e->getMessage());
    }
    
    return [
        'total' => (int)$totalErrors,
        'today' => (int)$todayErrors,
        'recent' => $logs,
        'error_types' => $errorTypes
    ];
}

// ===== EMAIL ERROR LOGGER =====
function logEmailError($pdo, $to, $subject, $error, $from = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO app_error_logs 
            (error_type, error_message, file_path, line_number, ip_address, request_data) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'email_failure',
            "Email send failed to $to | Subject: $subject | Error: $error",
            'mail_function',
            0,
            $_SERVER['REMOTE_ADDR'] ?? 'system',
            json_encode([
                'to' => $to,
                'subject' => $subject,
                'from' => $from,
                'error' => $error,
                'timestamp' => date('Y-m-d H:i:s')
            ])
        ]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ===== PERFORMANCE METRICS =====
function getPerformanceMetrics($pdo) {
    try {
        // Simple test query
        $start = microtime(true);
        $pdo->query("SELECT 1");
        $queryTime = round((microtime(true) - $start) * 1000, 2);
        
        $grade = 'A+';
        if ($queryTime > 100) $grade = 'A';
        if ($queryTime > 300) $grade = 'B';
        if ($queryTime > 500) $grade = 'C';
        if ($queryTime > 1000) $grade = 'D';
        if ($queryTime > 2000) $grade = 'F';
        
        return [
            'benchmarks' => ['simple_query' => $queryTime],
            'avg_query_time_ms' => $queryTime,
            'grade' => $grade,
            'php_memory_limit' => ini_get('memory_limit'),
            'php_memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'note' => 'Basic performance test completed'
        ];
    } catch (Exception $e) {
        return [
            'benchmarks' => [],
            'avg_query_time_ms' => 0,
            'grade' => 'F',
            'php_memory_limit' => ini_get('memory_limit'),
            'php_memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'error' => $e->getMessage()
        ];
    }
}

// ===== SPEED TEST =====
function getSpeedTest($pdo) {
    $results = [];
    
    // Database read speed
    $start = microtime(true);
    for ($i = 0; $i < 10; $i++) {
        $pdo->query("SELECT COUNT(*) FROM users");
    }
    $results['db_read_10x'] = round((microtime(true) - $start) * 1000, 2);
    
    // File system speed
    $tmpFile = tempnam(sys_get_temp_dir(), 'binest_speed_');
    $start = microtime(true);
    file_put_contents($tmpFile, str_repeat('x', 1024 * 100)); // 100KB
    $results['fs_write_100kb'] = round((microtime(true) - $start) * 1000, 2);
    
    $start = microtime(true);
    file_get_contents($tmpFile);
    $results['fs_read_100kb'] = round((microtime(true) - $start) * 1000, 2);
    @unlink($tmpFile);
    
    return $results;
}
