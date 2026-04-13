<?php
/**
 * Helper Functions for Owner Panel
 */

// Check if owner is logged in
function isOwnerLoggedIn() {
    return isset($_SESSION['owner_id']) && !empty($_SESSION['owner_id']);
}

// Redirect if not logged in
function requireOwnerLogin() {
    if (!isOwnerLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// Log owner activity
function logOwnerActivity($pdo, $owner_id, $action, $details = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO owner_activity_logs (owner_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$owner_id, $action, $details, $ip, $user_agent]);
    } catch(PDOException $e) {
        // Silently fail to not disrupt user experience
        error_log("Failed to log owner activity: " . $e->getMessage());
    }
}

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Format number with K, M suffix
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return number_format($num);
}

// Get time ago string with detailed format
function timeAgo($datetime) {
    if (empty($datetime)) {
        return 'Unknown';
    }
    
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return 'Invalid date';
    }
    
    $now = time();
    $diff = $now - $timestamp;
    
    // Handle future timestamps
    if ($diff < 0) {
        $diff = abs($diff);
    }
    
    // Calculate time components
    $days = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    $minutes = floor(($diff % 3600) / 60);
    $seconds = $diff % 60;
    
    // Build time string
    $parts = [];
    
    if ($days > 0) {
        $parts[] = $days . 'd';
    }
    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0) {
        $parts[] = $minutes . 'm';
    }
    // Show seconds only if less than 1 day
    if ($seconds > 0 && $days == 0) {
        $parts[] = $seconds . 's';
    }
    
    // If no parts, show just now
    if (empty($parts)) {
        return 'Just now';
    }
    
    return implode(' ', $parts) . ' ago';
}

// Sanitize output
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

// Get percentage change
function getPercentageChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

// Get trend indicator
function getTrendIndicator($current, $previous) {
    $change = getPercentageChange($current, $previous);
    if ($change > 0) {
        return '<span class="text-success"><i class="fas fa-arrow-up"></i> ' . $change . '%</span>';
    } elseif ($change < 0) {
        return '<span class="text-danger"><i class="fas fa-arrow-down"></i> ' . abs($change) . '%</span>';
    } else {
        return '<span class="text-muted"><i class="fas fa-minus"></i> 0%</span>';
    }
}

// Format device info nicely
function formatDeviceInfo($deviceInfo) {
    if (empty($deviceInfo) || $deviceInfo === 'Unknown') {
        return 'Unknown Device';
    }
    
    $device = strtolower(trim($deviceInfo));
    
    // Common patterns
    // android 34 -> Android 14 (API 34)
    // ios 16.5 -> iOS 16.5
    // web -> Web Browser
    
    // Extract platform and version
    if (strpos($device, 'android') !== false) {
        preg_match('/android\s*(\d+)?/', $device, $matches);
        $version = isset($matches[1]) ? (int)$matches[1] : null;
        
        // Convert API level to Android version
        $apiToVersion = [
            21 => '5.0', 22 => '5.1', 23 => '6.0', 24 => '7.0', 25 => '7.1',
            26 => '8.0', 27 => '8.1', 28 => '9', 29 => '10', 30 => '11',
            31 => '12', 32 => '12L', 33 => '13', 34 => '14', 35 => '15'
        ];
        
        if ($version && isset($apiToVersion[$version])) {
            return 'Android ' . $apiToVersion[$version] . ' (API ' . $version . ')';
        }
        return 'Android' . ($version ? ' ' . $version : '');
    }
    
    if (strpos($device, 'ios') !== false || strpos($device, 'iphone') !== false || strpos($device, 'ipad') !== false) {
        preg_match('/(?:ios|iphone|ipad)\s*(\d+(?:\.\d+)?)?/', $device, $matches);
        $version = isset($matches[1]) ? $matches[1] : null;
        return 'iOS' . ($version ? ' ' . $version : '');
    }
    
    if (strpos($device, 'web') !== false) {
        return 'Web Browser';
    }
    
    if (strpos($device, 'windows') !== false) {
        return 'Windows';
    }
    
    if (strpos($device, 'mac') !== false || strpos($device, 'darwin') !== false) {
        return 'macOS';
    }
    
    if (strpos($device, 'linux') !== false) {
        return 'Linux';
    }
    
    // Return original with first letter capitalized
    return ucfirst($deviceInfo);
}
?>
