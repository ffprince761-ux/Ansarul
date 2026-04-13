<?php
/**
 * Email Service using PHPMailer
 * For sending OTP and other emails
 */

// Include PHPMailer files
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }
    
    private function configure() {
        try {
            // SMTP Configuration
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            
            // Gmail credentials - IMPORTANT: Use App Password, not regular password
            // Generate App Password: Google Account > Security > 2-Step Verification > App passwords
            $this->mailer->Username = 'ffprince761@gmail.com';
            $this->mailer->Password = 'sqaezyalrvkedzut';
            
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            
            // Sender info
            $this->mailer->setFrom('ffprince761@gmail.com', 'Binest');
            
            // Prevent spam
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
    
    public function sendOTP($email, $otp, $purpose = 'registration') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            
            $this->mailer->isHTML(true);
            
            if ($purpose === 'registration') {
                $this->mailer->Subject = 'Verify Your Binest Account';
                $this->mailer->Body = $this->getRegistrationEmailHTML($otp);
            } else {
                $this->mailer->Subject = 'Reset Your Binest Password';
                $this->mailer->Body = $this->getPasswordResetEmailHTML($otp);
            }
            
            $this->mailer->AltBody = "Your OTP is: $otp. Valid for 10 minutes.";
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    private function getRegistrationEmailHTML($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-box { background: white; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Binest!</h1>
                    <p>Verify your email to get started</p>
                </div>
                <div class='content'>
                    <h2>Email Verification</h2>
                    <p>Thank you for registering with Binest. Please use the OTP below to verify your email address:</p>
                    
                    <div class='otp-box'>
                        <p style='margin: 0; font-size: 14px; color: #666;'>Your OTP Code</p>
                        <div class='otp-code'>{$otp}</div>
                        <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>Valid for 10 minutes</p>
                    </div>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This OTP is valid for 10 minutes only</li>
                        <li>Do not share this OTP with anyone</li>
                        <li>If you didn't request this, please ignore this email</li>
                    </ul>
                    
                    <p>Need help? Contact us at support@binest.com</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 Binest. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPasswordResetEmailHTML($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-box { background: white; border: 2px dashed #f5576c; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #f5576c; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                    <p>Binest Account Security</p>
                </div>
                <div class='content'>
                    <h2>Reset Your Password</h2>
                    <p>We received a request to reset your Binest account password. Use the OTP below to proceed:</p>
                    
                    <div class='otp-box'>
                        <p style='margin: 0; font-size: 14px; color: #666;'>Your OTP Code</p>
                        <div class='otp-code'>{$otp}</div>
                        <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>Valid for 10 minutes</p>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Alert:</strong>
                        <p style='margin: 5px 0 0 0;'>If you didn't request a password reset, please ignore this email and ensure your account is secure.</p>
                    </div>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This OTP expires in 10 minutes</li>
                        <li>Never share your OTP with anyone</li>
                        <li>Binest staff will never ask for your OTP</li>
                    </ul>
                    
                    <p>Need help? Contact us at support@binest.com</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 Binest. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
