<?php
/**
 * Owner Panel - Settings
 * Manage app settings and owner account
 */
require_once 'config/db.php';
require_once 'config/functions.php';

requireOwnerLogin();

$pageTitle = 'Settings';
$success = '';
$error = '';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep it 0 for production but logs will show errors

// Get current owner details
$owner = null;
try {
    if (isset($_SESSION['owner_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM owner_users WHERE id = ?");
        $stmt->execute([$_SESSION['owner_id']]);
        $owner = $stmt->fetch();
    }
} catch(PDOException $e) {
    error_log("Settings owner fetch error: " . $e->getMessage());
}

// Redirect if owner not found (session might be invalid)
if (!$owner && basename($_SERVER['PHP_SELF']) !== 'index.php') {
    // If we're here and owner is null, session might be stale or DB table missing
    // Don't redirect immediately to avoid loops, but handle null $owner below
    $owner = [
        'username' => $_SESSION['owner_username'] ?? 'owner',
        'email' => 'owner@example.com',
        'full_name' => $_SESSION['owner_name'] ?? 'Owner',
        'password' => ''
    ];
}

// Get app settings (create table if not exists)
$settings = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert default settings if not exist
    $pdo->exec("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES 
        ('support_email', 'support@binest.com'),
        ('support_phone', '+91 1234567890'),
        ('app_version', '1.0.0')
    ");
    
    $stmt = $pdo->query("SELECT * FROM app_settings");
    if ($stmt) {
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch(PDOException $e) {
    error_log("Settings error: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_app_settings') {
            $supportEmail = trim($_POST['support_email']);
            $supportPhone = trim($_POST['support_phone']);
            $appVersion = trim($_POST['app_version']);
            
            // Update in owner panel database
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$supportEmail, 'support_email']);
            $stmt->execute([$supportPhone, 'support_phone']);
            $stmt->execute([$appVersion, 'app_version']);
            
            // Also update in backend database (bizinote_db) for mobile app
            $backendConn = new mysqli("localhost", "root", "", "bizinote_db");
            if (!$backendConn->connect_error) {
                $backendConn->query("CREATE TABLE IF NOT EXISTS app_settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    setting_key VARCHAR(100) UNIQUE,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                
                $stmt2 = $backendConn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt2->bind_param("sss", $key, $value, $value);
                
                $key = 'support_email'; $value = $supportEmail;
                $stmt2->execute();
                
                $key = 'support_phone'; $value = $supportPhone;
                $stmt2->execute();
                
                $key = 'app_version'; $value = $appVersion;
                $stmt2->execute();
                
                $stmt2->close();
                $backendConn->close();
            }
            
            logOwnerActivity($pdo, $_SESSION['owner_id'], 'update_settings', 'Updated app settings');
            $success = 'App settings updated successfully! Mobile app will show updated info.';
            
            // Refresh settings
            $settings['support_email'] = $supportEmail;
            $settings['support_phone'] = $supportPhone;
            $settings['app_version'] = $appVersion;
            
        } elseif ($action === 'update_account') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $fullName = trim($_POST['full_name']);
            
            // Check if username already exists (for other users)
            $stmt = $pdo->prepare("SELECT id FROM owner_users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['owner_id']]);
            if ($stmt->fetch()) {
                $error = 'Username already exists!';
            } else {
                $stmt = $pdo->prepare("UPDATE owner_users SET username = ?, email = ?, full_name = ? WHERE id = ?");
                $stmt->execute([$username, $email, $fullName, $_SESSION['owner_id']]);
                
                $_SESSION['owner_username'] = $username;
                $_SESSION['owner_name'] = $fullName;
                
                logOwnerActivity($pdo, $_SESSION['owner_id'], 'update_account', 'Updated account details');
                $success = 'Account details updated successfully!';
                
                // Refresh owner data
                $owner['username'] = $username;
                $owner['email'] = $email;
                $owner['full_name'] = $fullName;
            }
            
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (!password_verify($currentPassword, $owner['password'])) {
                $error = 'Current password is incorrect!';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match!';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password must be at least 6 characters!';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE owner_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['owner_id']]);
                
                logOwnerActivity($pdo, $_SESSION['owner_id'], 'change_password', 'Changed password');
                $success = 'Password changed successfully!';
            }
        }
    } catch(PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
        error_log("Settings update error: " . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<style>
.form-label{font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:4px}
.form-control{border:1px solid var(--border);border-radius:8px;font-size:13px;padding:8px 12px}
.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.1)}
</style>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;border:1px solid var(--green);background:var(--green-light);color:var(--green);font-size:13px">
        <i class="fas fa-check-circle"></i> <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px;border:1px solid var(--red);background:var(--red-light);color:var(--red);font-size:13px">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- App Settings -->
<div class="table-container mb-4">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:4px">App Settings</h6>
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px">Displayed in the mobile app's Help & Support section.</p>
    <form method="POST">
        <input type="hidden" name="action" value="update_app_settings">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Support Email</label>
                <input type="email" name="support_email" class="form-control" value="<?= e($settings['support_email'] ?? '') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Support Phone</label>
                <input type="text" name="support_phone" class="form-control" value="<?= e($settings['support_phone'] ?? '') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">App Version</label>
                <input type="text" name="app_version" class="form-control" value="<?= e($settings['app_version'] ?? '') ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px"><i class="fas fa-save"></i> Save</button>
    </form>
</div>

<!-- Owner Account -->
<div class="table-container mb-4">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Owner Account</h6>
    <form method="POST">
        <input type="hidden" name="action" value="update_account">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= e($owner['username']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($owner['email']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= e($owner['full_name']) ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:8px"><i class="fas fa-save"></i> Update Account</button>
    </form>
</div>

<!-- Change Password -->
<div class="table-container">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Change Password</h6>
    <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="6" required>
                <small style="color:var(--text-muted);font-size:11px">Min 6 characters</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>
        </div>
        <button type="submit" class="btn btn-sm btn-outline-warning" style="border-radius:8px"><i class="fas fa-key"></i> Change Password</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
