<?php
/**
 * Owner Panel - Login Page
 */
require_once 'config/db.php';
require_once 'config/functions.php';

// Redirect if already logged in
if (isOwnerLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM owner_users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $owner = $stmt->fetch();
            
            if ($owner) {
                // User found, check password
                if (password_verify($password, $owner['password'])) {
                    // Login successful
                    $_SESSION['owner_id'] = $owner['id'];
                    $_SESSION['owner_username'] = $owner['username'];
                    $_SESSION['owner_name'] = $owner['full_name'];
                    
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE owner_users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$owner['id']]);
                    
                    // Log activity
                    logOwnerActivity($pdo, $owner['id'], 'login', 'Successful login');
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password. Please try again.';
                }
            } else {
                $error = 'Username not found. Please use: owner';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Login - Binest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #EEF2FF;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --bg: #F9FAFB;
            --card-bg: #FFFFFF;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            font-size: 14px;
        }
        .login-container { max-width: 400px; width: 100%; padding: 20px; }
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 36px 32px;
        }
        .login-header { text-align: center; margin-bottom: 28px; }
        .login-header img { height: 56px; border-radius: 12px; margin-bottom: 14px; }
        .login-header h1 { font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
        .login-header p { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .form-group { margin-bottom: 16px; }
        .form-label-sm {
            font-size: 11px; font-weight: 600; color: var(--text-muted);
            margin-bottom: 6px; display: block; text-transform: uppercase; letter-spacing: .04em;
        }
        .input-wrap {
            display: flex; align-items: center;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 8px; transition: all .2s;
        }
        .input-wrap:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
        .input-wrap .icon {
            width: 40px; display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); font-size: 13px;
        }
        .input-wrap input {
            flex: 1; background: transparent; border: none; outline: none;
            padding: 11px 12px 11px 0; color: var(--text-primary); font-size: 13px; font-weight: 500;
            font-family: inherit;
        }
        .input-wrap input::placeholder { color: var(--text-muted); }
        .input-wrap .toggle-pw {
            width: 36px; display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); cursor: pointer; font-size: 13px; transition: color .2s;
        }
        .input-wrap .toggle-pw:hover { color: var(--text-secondary); }
        .btn-login {
            width: 100%; padding: 11px; border: none; border-radius: 8px;
            background: var(--primary); color: #fff; font-weight: 600; font-size: 13px;
            cursor: pointer; transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            font-family: inherit;
        }
        .btn-login:hover { background: #4338CA; box-shadow: 0 4px 12px rgba(79,70,229,.25); }
        .alert-err {
            background: #FEF2F2; border: 1px solid #FECACA;
            border-radius: 8px; padding: 10px 14px; margin-bottom: 16px;
            color: #DC2626; font-size: 12px; display: flex; align-items: center; gap: 8px;
        }
        .footer-text {
            text-align: center; margin-top: 20px; padding-top: 16px;
            border-top: 1px solid var(--border);
            color: var(--text-muted); font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/icon.png" alt="Binest" style="width: 48px; height: 48px; margin-bottom: 12px;">
                <h1>Binest</h1>
                <p>Owner Panel &middot; Business Management</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-err">
                    <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label-sm">Username</label>
                    <div class="input-wrap">
                        <div class="icon"><i class="fas fa-user"></i></div>
                        <input type="text" name="username" placeholder="Enter username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label-sm">Password</label>
                    <div class="input-wrap">
                        <div class="icon"><i class="fas fa-lock"></i></div>
                        <input type="password" name="password" id="pwInput" placeholder="Enter password" required>
                        <div class="toggle-pw" onclick="togglePw()">
                            <i class="fas fa-eye" id="pwIcon"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-login" style="margin-top:8px">
                    <i class="fas fa-arrow-right-to-bracket"></i> Sign In
                </button>
            </form>

            <p class="footer-text"><i class="fas fa-shield-halved" style="color:var(--primary);margin-right:4px"></i> Secured login</p>
        </div>
    </div>

    <script>
    function togglePw(){
        const inp=document.getElementById('pwInput');
        const ico=document.getElementById('pwIcon');
        if(inp.type==='password'){inp.type='text';ico.className='fas fa-eye-slash';}
        else{inp.type='password';ico.className='fas fa-eye';}
    }
    </script>
</body>
</html>
