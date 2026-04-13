<?php
/**
 * Test Email Sending
 * Use this to verify PHPMailer and Gmail configuration
 */

require_once 'email/EmailService.php';

echo "<h2>Testing Email Service</h2>";

// Test email address - CHANGE THIS to your email
$testEmail = 'ffprince761@gmail.com'; // Testing with sender email
$testOTP = '123456';

echo "<p><strong>Sending test OTP email to:</strong> {$testEmail}</p>";
echo "<p><strong>OTP:</strong> {$testOTP}</p>";

try {
    $emailService = new EmailService();
    $result = $emailService->sendOTP($testEmail, $testOTP, 'registration');
    
    if ($result) {
        echo "<h3 style='color: green;'>✅ Email Sent Successfully!</h3>";
        echo "<p>Check your inbox (and spam folder) for the OTP email.</p>";
        echo "<p>If you received the email, the setup is complete!</p>";
    } else {
        echo "<h3 style='color: red;'>❌ Email Sending Failed</h3>";
        echo "<p>Check the configuration in EmailService.php:</p>";
        echo "<ul>";
        echo "<li>Gmail address correct?</li>";
        echo "<li>App Password correct? (16 characters, no spaces)</li>";
        echo "<li>2-Step Verification enabled?</li>";
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error Occurred</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Common issues:</p>";
    echo "<ul>";
    echo "<li>PHPMailer files not found - check PHPMailer folder exists</li>";
    echo "<li>Gmail credentials incorrect</li>";
    echo "<li>SMTP port blocked by firewall</li>";
    echo "</ul>";
}
?>
