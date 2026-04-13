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

// Send email using direct PHPMailer (with fallback to PHP mail)
function sendEmail($to, $subject, $message, $from = null, $fromName = 'Bizinote') {
    global $pdo;
    
    // DEBUG: Log email attempt
    error_log("EMAIL DEBUG: Attempting to send email to: $to, Subject: $subject");
    
    // Method 1: Try PHPMailer (working before)
    try {
        require_once __DIR__ . '/../../1_WEBSITE_FILES/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../1_WEBSITE_FILES/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../../1_WEBSITE_FILES/PHPMailer/src/Exception.php';
        
        $mail = new PHPMailer(true);
        
        // SMTP Configuration (exact same as EmailService)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ffprince761@gmail.com';
        $mail->Password = 'sqaezyalrvkedzut';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('ffprince761@gmail.com', 'Bizinote');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        error_log("EMAIL DEBUG: PHPMailer configured, attempting to send...");
        $result = $mail->send();
        
        if ($result) {
            error_log("EMAIL DEBUG: PHPMailer SUCCESS");
            return true;
        } else {
            error_log("EMAIL DEBUG: PHPMailer FAILED: " . $mail->ErrorInfo);
        }
        
    } catch (Exception $e) {
        error_log("EMAIL DEBUG: PHPMailer Exception: " . $e->getMessage());
    }
    
    // Method 2: Fallback to PHP mail (if PHPMailer fails)
    error_log("EMAIL DEBUG: Trying PHP mail fallback...");
    $headers = "From: $fromName <ffprince761@gmail.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if (@mail($to, $subject, $message, $headers)) {
        error_log("EMAIL DEBUG: PHP mail SUCCESS");
        return true;
    } else {
        error_log("EMAIL DEBUG: PHP mail FAILED");
        logEmailError($to, $subject, "Both PHPMailer and PHP mail failed", $from);
        return false;
    }
}
?>
