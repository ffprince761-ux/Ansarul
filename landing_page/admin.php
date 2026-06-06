<?php
require_once __DIR__ . '/db.php';
$cfg   = lpGetSettings();
$secret = $cfg['lp_api_secret'] ?? '';
$loggedIn = false;
$error = '';

/* ── Login ──────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['secret'])) {
    if ($_POST['secret'] === $secret) {
        setcookie('lp_admin', hash('sha256', $secret), time() + 86400, '/');
        $loggedIn = true;
    } else {
        $error = 'Invalid secret key.';
    }
} elseif (!empty($_COOKIE['lp_admin']) && $_COOKIE['lp_admin'] === hash('sha256', $secret)) {
    $loggedIn = true;
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
    <p>Enter your secret key to access leads dashboard.</p>
    <?php if($error): ?><div class="login-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="password" name="secret" placeholder="Secret Key" required autofocus>
      <button type="submit"><i class="fas fa-lock"></i> Access Dashboard</button>
    </form>
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
