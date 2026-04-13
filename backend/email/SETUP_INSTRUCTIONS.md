# Gmail OTP Setup Instructions

## 📋 Prerequisites

1. Gmail account for sending emails
2. PHPMailer library
3. MySQL database with `email_otps` table

---

## 🔧 Step-by-Step Setup

### Step 1: Install PHPMailer

**Option A: Using Composer (Recommended)**
```bash
cd c:/xampp/htdocs/bizinote/backend
composer require phpmailer/phpmailer
```

**Option B: Manual Download**
1. Download from: https://github.com/PHPMailer/PHPMailer/archive/master.zip
2. Extract to: `c:/xampp/htdocs/bizinote/backend/vendor/phpmailer/`
3. Update EmailService.php to include correct path

---

### Step 2: Create Database Table

Run this SQL in phpMyAdmin:
```sql
CREATE TABLE IF NOT EXISTS email_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    purpose ENUM('registration', 'password_reset') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_otp (otp),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### Step 3: Configure Gmail App Password

**IMPORTANT: Don't use your regular Gmail password!**

1. Go to Google Account: https://myaccount.google.com/
2. Click **Security** (left sidebar)
3. Enable **2-Step Verification** (if not already enabled)
4. Scroll to **App passwords**
5. Click **App passwords**
6. Select app: **Mail**
7. Select device: **Other (Custom name)**
8. Enter name: **Bizinote**
9. Click **Generate**
10. Copy the 16-character password (e.g., `abcd efgh ijkl mnop`)

---

### Step 4: Update EmailService.php

Open: `backend/email/EmailService.php`

Update these lines:
```php
$this->mailer->Username = 'your-email@gmail.com'; // Your Gmail address
$this->mailer->Password = 'abcdefghijklmnop'; // 16-char App Password (no spaces)
```

Also update sender info:
```php
$this->mailer->setFrom('your-email@gmail.com', 'Bizinote');
```

---

### Step 5: Test Email Sending

Create test file: `backend/test_email.php`
```php
<?php
require_once 'email/EmailService.php';

$emailService = new EmailService();
$result = $emailService->sendOTP('test@example.com', '123456', 'registration');

if ($result) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Email sending failed. Check error logs.";
}
?>
```

Run: http://localhost/bizinote/backend/test_email.php

---

## 🚫 Prevent Emails Going to Spam

### 1. Use Proper Headers (Already configured in EmailService.php)
- ✅ SPF records
- ✅ DKIM signature
- ✅ Proper From address
- ✅ HTML + Plain text versions

### 2. Gmail Best Practices
- Use your actual Gmail account (not fake)
- Send to real email addresses
- Don't send too many emails at once
- Include unsubscribe option (for production)

### 3. Email Content Tips
- ✅ Professional HTML design
- ✅ Clear subject line
- ✅ No spam words (FREE, CLICK HERE, etc.)
- ✅ Proper formatting
- ✅ Company branding

---

## 📱 Mobile App Integration

### Registration Flow:
```
1. User enters email, password, business details
2. App calls: POST /api/otp.php?action=send
   Body: { "email": "user@example.com", "purpose": "registration" }
3. User receives OTP email
4. User enters OTP in app
5. App calls: POST /api/otp.php?action=verify
   Body: { "email": "user@example.com", "otp": "123456", "purpose": "registration" }
6. If verified, proceed with registration
```

### Password Reset Flow:
```
1. User clicks "Forgot Password"
2. User enters email
3. App calls: POST /api/otp.php?action=send
   Body: { "email": "user@example.com", "purpose": "password_reset" }
4. User receives OTP email
5. User enters OTP
6. App calls: POST /api/otp.php?action=verify
   Body: { "email": "user@example.com", "otp": "123456", "purpose": "password_reset" }
7. If verified, allow password reset
```

---

## 🧪 Testing Checklist

- [ ] PHPMailer installed correctly
- [ ] Database table created
- [ ] Gmail App Password configured
- [ ] Test email sends successfully
- [ ] OTP generation works
- [ ] OTP verification works
- [ ] OTP expiry works (10 minutes)
- [ ] Resend OTP works (with 1 minute cooldown)
- [ ] Email doesn't go to spam
- [ ] HTML email displays correctly
- [ ] Plain text fallback works

---

## 🔒 Security Features

✅ **6-digit random OTP**
✅ **10-minute expiry**
✅ **One-time use only**
✅ **Rate limiting (1 minute between requests)**
✅ **Secure password hashing**
✅ **SQL injection prevention**
✅ **Email validation**

---

## 🐛 Troubleshooting

### Email not sending?
- Check Gmail App Password is correct (no spaces)
- Verify 2-Step Verification is enabled
- Check PHP error logs
- Ensure port 587 is not blocked

### Emails going to spam?
- Use real Gmail account
- Add proper SPF/DKIM records (for production domain)
- Send to real email addresses
- Don't send too frequently

### OTP not verifying?
- Check database table exists
- Verify OTP hasn't expired
- Ensure OTP hasn't been used already
- Check email matches exactly

---

## 📞 Support

For issues, check:
1. PHP error logs: `c:/xampp/php/logs/php_error_log`
2. Apache error logs: `c:/xampp/apache/logs/error.log`
3. MySQL error logs: `c:/xampp/mysql/data/*.err`

---

**✅ Setup complete! OTP verification ready to use.**
