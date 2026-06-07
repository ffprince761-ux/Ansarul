<?php
require_once __DIR__ . '/db.php';
$cfg   = lpGetSettings();
$secret = $cfg['lp_api_secret'] ?? '';
$adminUser = $cfg['admin_username'] ?? 'admin';
$adminPass = $cfg['admin_password'] ?? '';

// Auto-fix: if credentials missing or password doesn't verify against default, reset to default
if (empty($adminPass) || !password_verify('admin123', $adminPass)) {
    $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
    lpSaveSettings([
        'admin_username' => 'admin',
        'admin_password' => $defaultHash,
    ]);
    $adminUser = 'admin';
    $adminPass = $defaultHash;
}

$loggedIn = false;
$error = '';
$success = '';

/* ── Session helper ─────────────────────────────────────────── */
function adminSessionKey(string $u, string $p): string {
    return hash('sha256', 'binest_admin_' . $u . '_' . $p);
}

/* ── Login ──────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username']) && !empty($_POST['password'])) {
    $u = trim($_POST['username']);
    $p = trim($_POST['password']);
    if ($u === $adminUser && password_verify($p, $adminPass)) {
        setcookie('lp_admin', adminSessionKey($u, $adminPass), time() + 86400, '/');
        $loggedIn = true;
    } else {
        $error = 'Invalid username or password.';
    }
} elseif (!empty($_COOKIE['lp_admin']) && $_COOKIE['lp_admin'] === adminSessionKey($adminUser, $adminPass)) {
    $loggedIn = true;
}

/* ── Change Password ───────────────────────────────────────── */
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['change_password'])) {
    $old = trim($_POST['old_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    if (!password_verify($old, $adminPass)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        lpSaveSettings(['admin_password' => $newHash]);
        setcookie('lp_admin', adminSessionKey($adminUser, $newHash), time() + 86400, '/');
        $success = 'Password updated successfully.';
        $adminPass = $newHash;
    }
}

/* ── Actions ────────────────────────────────────────────────── */
if ($loggedIn && !empty($_GET['action'])) {
    $id = (int)($_GET['id'] ?? 0);
    if ($_GET['action'] === 'done' && $id) {
        lpMarkRequest($id);
    } elseif ($_GET['action'] === 'delete' && $id) {
        lpDeleteRequest($id);
    } elseif ($_GET['action'] === 'logout') {
        setcookie('lp_admin', '', time() - 3600, '/');
        header('Location: admin.php');
        exit;
    }
    header('Location: admin.php');
    exit;
}

$stats = lpGetStats();
$requests = $loggedIn ? lpGetRequests() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Binest Admin — Leads Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px}
body{font-family:'Inter',sans-serif;color:#111827;background:#F9FAFB;min-height:100vh}
a{text-decoration:none;color:inherit}

:root{--indigo:#4F46E5;--indigo-d:#4338CA;--green:#059669;--red:#EF4444;--amber:#F59E0B;--text:#111827;--muted:#6B7280;--light:#9CA3AF;--border:#E5E7EB;--bg:#F9FAFB;--white:#fff}

/* Login */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-box{background:#fff;border:1.5px solid var(--border);border-radius:20px;padding:48px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.06);text-align:center}
.login-box img{width:56px;height:56px;border-radius:14px;margin-bottom:16px}
.login-box h1{font-size:22px;font-weight:800;color:var(--text);margin-bottom:4px}
.login-box p{font-size:14px;color:var(--muted);margin-bottom:28px}
.login-box input{width:100%;padding:13px 16px;background:var(--bg);border:1.5px solid var(--border);border-radius:10px;font-size:14.5px;font-family:'Inter',sans-serif;color:var(--text);outline:none;margin-bottom:12px;transition:all .2s}
.login-box input:focus{border-color:var(--indigo);background:#fff;box-shadow:0 0 0 4px rgba(79,70,229,.06)}
.login-box button{width:100%;padding:14px;background:var(--indigo);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:all .25s}
.login-box button:hover{background:var(--indigo-d)}
.login-err{color:var(--red);font-size:13px;font-weight:600;margin-top:-4px;margin-bottom:12px}

/* Dashboard */
.dash{padding:32px 5%;max-width:1400px;margin:0 auto}
.dash-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px}
.dash-header h1{font-size:24px;font-weight:800}
.dash-header a.logout{font-size:13px;font-weight:600;color:var(--muted);padding:8px 16px;border-radius:8px;border:1.5px solid var(--border);transition:all .2s}
.dash-header a.logout:hover{border-color:var(--red);color:var(--red)}

/* Stats cards */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px}
.stat-card{background:#fff;border:1.5px solid var(--border);border-radius:16px;padding:24px;display:flex;align-items:center;gap:16px;transition:all .2s}
.stat-card:hover{border-color:var(--indigo);box-shadow:0 8px 24px rgba(79,70,229,.08)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;flex-shrink:0}
.stat-info h3{font-size:28px;font-weight:800;color:var(--text);line-height:1}
.stat-info p{font-size:13px;color:var(--muted);margin-top:4px;font-weight:500}

/* Table */
.table-wrap{background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:hidden}
.table-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1.5px solid var(--border)}
.table-header h2{font-size:16px;font-weight:700}
.table-header .count{font-size:12px;font-weight:600;color:var(--muted);background:var(--bg);padding:4px 12px;border-radius:999px}
.table-scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13.5px}
th{background:var(--bg);color:var(--muted);font-weight:600;text-align:left;padding:14px 20px;border-bottom:1.5px solid var(--border);white-space:nowrap}
td{padding:14px 20px;border-bottom:1px solid var(--border);color:var(--text);vertical-align:top}
tr:hover td{background:rgba(79,70,229,.02)}
.badge{display:inline-block;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;white-space:nowrap}
.badge-new{background:#EEF2FF;color:var(--indigo)}
.badge-done{background:#ECFDF5;color:var(--green)}
.badge-form{background:#F3F4F6;color:var(--muted)}
.badge-whatsapp{background:#ECFDF5;color:var(--green)}
.badge-email{background:#FEF3C7;color:var(--amber)}
.badge-apk{background:#EEF2FF;color:var(--indigo)}
.actions{display:flex;gap:6px}
.actions a{width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--muted);border:1.5px solid var(--border);transition:all .2s}
.actions a:hover{border-color:var(--indigo);color:var(--indigo);background:#EEF2FF}
.actions a.delete:hover{border-color:var(--red);color:var(--red);background:#FEF2F2}
.empty{text-align:center;padding:60px 20px;color:var(--muted);font-size:14px}
.empty i{font-size:36px;color:var(--light);margin-bottom:12px;display:block}

/* Settings panel */
.settings-panel{margin-bottom:32px}
.settings-toggle{width:100%;display:flex;align-items:center;gap:10px;padding:14px 20px;background:#fff;border:1.5px solid var(--border);border-radius:12px;font-size:14px;font-weight:700;color:var(--text);font-family:'Inter',sans-serif;cursor:pointer;transition:all .2s}
.settings-toggle:hover{border-color:var(--indigo);color:var(--indigo)}
.settings-box{display:none;background:#fff;border:1.5px solid var(--border);border-top:none;border-radius:0 0 12px 12px;padding:24px;max-width:420px}
.settings-box.open{display:block}
.settings-box h3{font-size:16px;font-weight:700;margin-bottom:16px;color:var(--text)}
.settings-box input{width:100%;padding:12px 14px;background:var(--bg);border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:var(--text);outline:none;margin-bottom:10px;transition:all .2s}
.settings-box input:focus{border-color:var(--indigo);background:#fff;box-shadow:0 0 0 4px rgba(79,70,229,.06)}
.settings-box button{width:100%;padding:12px;background:var(--indigo);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:all .25s}
.settings-box button:hover{background:var(--indigo-d)}
.settings-err{color:var(--red);font-size:13px;font-weight:600;margin-bottom:12px;padding:10px 14px;background:#FEF2F2;border-radius:8px}
.settings-ok{color:var(--green);font-size:13px;font-weight:600;margin-bottom:12px;padding:10px 14px;background:#ECFDF5;border-radius:8px}

/* Responsive */
@media(max-width:768px){
  .dash{padding:20px}
  .dash-header h1{font-size:18px}
  th,td{padding:12px 14px;font-size:12.5px}
}
</style>
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- LOGIN -->
<div class="login-wrap">
  <div class="login-box">
    <img src="images/icon.png" alt="Binest">
    <h1>Binest Admin</h1>
    <p>Sign in to access your leads dashboard.</p>
    <?php if($error): ?><div class="login-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" autocomplete="off">
      <input type="text" name="username" placeholder="Username" value="admin" required autofocus autocomplete="username">
      <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
      <button type="submit"><i class="fas fa-lock"></i> Sign In</button>
    </form>
    <p style="font-size:12px;color:var(--muted);margin-top:16px">Default: <b>admin</b> / <b>admin123</b></p>
  </div>
</div>

<?php else: ?>
<!-- DASHBOARD -->
<div class="dash">
  <div class="dash-header">
    <h1><i class="fas fa-chart-line" style="color:var(--indigo)"></i> Binest Leads Dashboard</h1>
    <a href="?action=logout" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--indigo)"><i class="fas fa-eye"></i></div>
      <div class="stat-info"><h3><?= number_format($stats['page_views'] ?? 0) ?></h3><p>Page Views</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--green)"><i class="fas fa-download"></i></div>
      <div class="stat-info"><h3><?= number_format($stats['apk_downloads'] ?? 0) ?></h3><p>APK Downloads</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--amber)"><i class="fas fa-envelope"></i></div>
      <div class="stat-info"><h3><?= number_format($stats['total_requests'] ?? 0) ?></h3><p>Total Leads</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#7C3AED"><i class="fas fa-clock"></i></div>
      <div class="stat-info"><h3><?= number_format(lpCountNew()) ?></h3><p>New Leads</p></div>
    </div>
  </div>

  <!-- Settings Panel -->
  <div class="settings-panel">
    <button class="settings-toggle" onclick="document.getElementById('settingsBox').classList.toggle('open')">
      <i class="fas fa-cog"></i> Settings <i class="fas fa-chevron-down" style="margin-left:auto"></i>
    </button>
    <div class="settings-box" id="settingsBox">
      <?php if($error && !empty($_POST['change_password'])): ?><div class="settings-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if($success): ?><div class="settings-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <h3><i class="fas fa-key"></i> Change Password</h3>
      <form method="POST">
        <input type="hidden" name="change_password" value="1">
        <input type="password" name="old_password" placeholder="Current Password" required>
        <input type="password" name="new_password" placeholder="New Password (min 6 chars)" required minlength="6">
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
        <button type="submit"><i class="fas fa-save"></i> Update Password</button>
      </form>
    </div>
  </div>

  <div class="table-wrap">
    <div class="table-header">
      <h2><i class="fas fa-inbox" style="color:var(--indigo);margin-right:6px"></i> All Requests</h2>
      <span class="count"><?= count($requests) ?> total</span>
    </div>
    <div class="table-scroll">
      <?php if(empty($requests)): ?>
        <div class="empty"><i class="fas fa-inbox"></i>No requests yet. Leads will appear here.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Contact</th>
              <th>Type</th>
              <th>Message</th>
              <th>Status</th>
              <th>Date</th>
              <th>IP</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($requests as $r): ?>
            <tr>
              <td style="font-weight:700;color:var(--muted)">#<?= $r['id'] ?></td>
              <td style="font-weight:600"><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['phone'] ?: $r['email']) ?></td>
              <td><span class="badge badge-<?= $r['type'] ?>"><?= ucfirst($r['type']) ?></span></td>
              <td style="max-width:240px;word-break:break-word;color:var(--muted)"><?= htmlspecialchars($r['message'] ?: '—') ?></td>
              <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
              <td style="white-space:nowrap;color:var(--muted)"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
              <td style="font-size:11px;color:var(--light);white-space:nowrap"><?= $r['ip'] ?></td>
              <td>
                <div class="actions">
                  <?php if($r['status']==='new'): ?><a href="?action=done&id=<?= $r['id'] ?>" title="Mark done"><i class="fas fa-check"></i></a><?php endif; ?>
                  <a href="?action=delete&id=<?= $r['id'] ?>" class="delete" title="Delete" onclick="return confirm('Delete this request?')"><i class="fas fa-trash"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>
