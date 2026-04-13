<?php
/**
 * Rate Limiter - Prevents brute force attacks
 * Limits number of requests from same IP address
 */
class RateLimiter {
    private $conn;
    private $maxAttempts;
    private $timeWindow; // in seconds
    
    public function __construct($conn, $maxAttempts = 5, $timeWindow = 300) {
        $this->conn = $conn;
        $this->maxAttempts = $maxAttempts;
        $this->timeWindow = $timeWindow; // 5 minutes default
    }
    
    /**
     * Check if IP is rate limited
     */
    public function isRateLimited($ip, $action = 'login') {
        $this->cleanOldAttempts();
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM security_logs 
            WHERE ip_address = ? 
            AND action = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->bind_param("ssi", $ip, $action, $this->timeWindow);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['attempt_count'] >= $this->maxAttempts;
    }
    
    /**
     * Log an attempt
     */
    public function logAttempt($ip, $action, $success = false, $userId = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO security_logs (ip_address, action, success, user_id, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt->bind_param("ssiss", $ip, $action, $success, $userId, $userAgent);
        $stmt->execute();
    }
    
    /**
     * Clean old attempts (older than time window)
     */
    private function cleanOldAttempts() {
        $this->conn->query("
            DELETE FROM security_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
    }
    
    /**
     * Block IP temporarily
     */
    public function blockIP($ip, $duration = 3600) {
        $stmt = $this->conn->prepare("
            INSERT INTO blocked_ips (ip_address, blocked_until, reason) 
            VALUES (?, DATE_ADD(NOW(), INTERVAL ? SECOND), 'Too many failed attempts')
            ON DUPLICATE KEY UPDATE blocked_until = DATE_ADD(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->bind_param("sii", $ip, $duration, $duration);
        $stmt->execute();
    }
    
    /**
     * Check if IP is blocked
     */
    public function isIPBlocked($ip) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as is_blocked 
            FROM blocked_ips 
            WHERE ip_address = ? 
            AND blocked_until > NOW()
        ");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['is_blocked'] > 0;
    }
}
