<?php
/**
 * Input Validator - Prevents XSS and injection attacks
 * Sanitizes and validates all user input
 */
class InputValidator {
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input) {
        if (empty($input)) return '';
        
        // Remove HTML tags
        $input = strip_tags($input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        // Trim whitespace
        $input = trim($input);
        
        return $input;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        $email = self::sanitizeString($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return $email;
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        $phone = self::sanitizeString($phone);
        
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if valid length (10 digits for Indian numbers)
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Validate and sanitize number
     */
    public static function validateNumber($number, $min = null, $max = null) {
        if (!is_numeric($number)) {
            return false;
        }
        
        $number = floatval($number);
        
        if ($min !== null && $number < $min) {
            return false;
        }
        
        if ($max !== null && $number > $max) {
            return false;
        }
        
        return $number;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        // Minimum 6 characters
        if (strlen($password) < 6) {
            return [
                'valid' => false,
                'message' => 'Password must be at least 6 characters long'
            ];
        }
        
        // Check for at least one letter and one number (optional - can be enabled)
        // if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        //     return [
        //         'valid' => false,
        //         'message' => 'Password must contain both letters and numbers'
        //     ];
        // }
        
        return [
            'valid' => true,
            'message' => 'Password is valid'
        ];
    }
    
    /**
     * Sanitize array of data
     */
    public static function sanitizeArray($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Prevent SQL injection in search terms
     */
    public static function sanitizeSearchTerm($term) {
        $term = self::sanitizeString($term);
        
        // Remove SQL special characters
        $term = str_replace(['%', '_', '--', ';', '/*', '*/'], '', $term);
        
        return $term;
    }
    
    /**
     * Check for suspicious patterns (potential attacks)
     */
    public static function detectSuspiciousInput($input) {
        $suspiciousPatterns = [
            '/<script/i',           // XSS
            '/javascript:/i',       // XSS
            '/on\w+\s*=/i',        // Event handlers
            '/union.*select/i',     // SQL injection
            '/drop.*table/i',       // SQL injection
            '/insert.*into/i',      // SQL injection
            '/update.*set/i',       // SQL injection
            '/delete.*from/i',      // SQL injection
            '/<iframe/i',          // XSS
            '/eval\(/i',           // Code injection
            '/base64_decode/i',    // Code injection
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
}
