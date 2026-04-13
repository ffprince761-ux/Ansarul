<?php
/**
 * Owner Panel - Notifications & Announcements
 * Send notifications/announcements to app users
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();
$pageTitle = 'Notifications';

$success = '';
$error = '';

// Create notifications table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS owner_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info','warning','success','urgent') DEFAULT 'info',
        target ENUM('all','specific') DEFAULT 'all',
        target_user_id INT NULL,
        created_by INT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_active (is_active),
        INDEX idx_target (target_user_id)
    )");
} catch(Exception $e) {}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_notification') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = $_POST['type'] ?? 'info';
        $target = $_POST['target'] ?? 'all';
        $targetUserId = $target === 'specific' ? intval($_POST['target_user_id'] ?? 0) : null;
        
        if (empty($title) || empty($message)) {
            $error = 'Title and message are required';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO owner_notifications (title, message, type, target, target_user_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $message, $type, $target, $targetUserId, $_SESSION['owner_id']]);
                logOwnerActivity($pdo, $_SESSION['owner_id'], 'send_notification', "Sent notification: $title");
                $success = 'Notification sent successfully!';
            } catch(PDOException $e) {
                $error = 'Failed to send notification: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_notification') {
        $id = intval($_POST['notif_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM owner_notifications WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Notification deleted';
        } catch(Exception $e) { $error = 'Failed to delete'; }
    } elseif ($action === 'toggle_notification') {
        $id = intval($_POST['notif_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE owner_notifications SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Status updated';
        } catch(Exception $e) { $error = 'Failed to update'; }
    }
}

// Get all notifications
try {
    $stmt = $pdo->query("
        SELECT n.*, ou.full_name as sender_name, u.name as target_name, u.business_name as target_business
        FROM owner_notifications n
        LEFT JOIN owner_users ou ON n.created_by = ou.id
        LEFT JOIN users u ON n.target_user_id = u.id
        ORDER BY n.created_at DESC LIMIT 50
    ");
    $notifications = $stmt->fetchAll();
} catch(Exception $e) { $notifications = []; }

// Get users for target dropdown
try {
    $stmt = $pdo->query("SELECT id, name, business_name FROM users ORDER BY name ASC");
    $users = $stmt->fetchAll();
} catch(Exception $e) { $users = []; }

// Stats
$totalNotifs = count($notifications);
$activeNotifs = count(array_filter($notifications, fn($n) => $n['is_active']));
$todayNotifs = count(array_filter($notifications, fn($n) => date('Y-m-d', strtotime($n['created_at'])) === date('Y-m-d')));

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.sb-info{background:var(--blue-light);color:var(--blue)}.sb-warning{background:var(--amber-light);color:var(--amber)}
.sb-success{background:var(--green-light);color:var(--green)}.sb-urgent{background:var(--red-light);color:var(--red)}
.sb-active{background:var(--green-light);color:var(--green)}.sb-inactive{background:#F3F4F6;color:var(--text-muted)}
.notif-item{background:var(--bg);border-radius:8px;padding:14px;margin-bottom:10px;border:1px solid var(--border)}
.notif-item.type-info{border-left:3px solid var(--blue)}.notif-item.type-warning{border-left:3px solid var(--amber)}
.notif-item.type-success{border-left:3px solid var(--green)}.notif-item.type-urgent{border-left:3px solid var(--red)}
.type-select{display:flex;gap:6px;flex-wrap:wrap}
.type-opt{padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:#fff}
.type-opt.selected{border-width:2px}
.type-opt[data-type="info"].selected{background:var(--blue-light);border-color:var(--blue);color:var(--blue)}
.type-opt[data-type="warning"].selected{background:var(--amber-light);border-color:var(--amber);color:var(--amber)}
.type-opt[data-type="success"].selected{background:var(--green-light);border-color:var(--green);color:var(--green)}
.type-opt[data-type="urgent"].selected{background:var(--red-light);border-color:var(--red);color:var(--red)}
</style>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size:13px"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size:13px"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-6"><div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= $totalNotifs ?></div></div></div>
    <div class="col-md-4 col-6"><div class="stat-card"><div class="stat-label">Active</div><div class="stat-value" style="color:var(--green)"><?= $activeNotifs ?></div></div></div>
    <div class="col-md-4 col-6"><div class="stat-card"><div class="stat-label">Today</div><div class="stat-value"><?= $todayNotifs ?></div></div></div>
</div>

<div class="row g-3">
    <!-- Send Notification Form -->
    <div class="col-md-5">
        <div class="card" style="border:1px solid var(--border);border-radius:12px">
            <div class="card-body" style="padding:20px">
                <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Send Notification</h6>
                <form method="POST">
                    <input type="hidden" name="action" value="send_notification">
                    <input type="hidden" name="type" id="notifType" value="info">
                    
                    <div class="mb-3">
                        <label style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:6px;display:block">Type</label>
                        <div class="type-select">
                            <div class="type-opt selected" data-type="info" onclick="selectType('info',this)">Info</div>
                            <div class="type-opt" data-type="success" onclick="selectType('success',this)">Success</div>
                            <div class="type-opt" data-type="warning" onclick="selectType('warning',this)">Warning</div>
                            <div class="type-opt" data-type="urgent" onclick="selectType('urgent',this)">Urgent</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:6px;display:block">Target</label>
                        <select name="target" class="form-control" style="font-size:13px;border-radius:8px;border:1px solid var(--border)" onchange="document.getElementById('userSelect').style.display=this.value==='specific'?'block':'none'">
                            <option value="all">All Users</option>
                            <option value="specific">Specific User</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="userSelect" style="display:none">
                        <label style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:6px;display:block">Select User</label>
                        <select name="target_user_id" class="form-control" style="font-size:13px;border-radius:8px;border:1px solid var(--border)">
                            <option value="">-- Select User --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['business_name'] ?: '-') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:6px;display:block">Title</label>
                        <input type="text" name="title" class="form-control" style="font-size:13px;border-radius:8px;border:1px solid var(--border)" placeholder="Notification title..." required>
                    </div>
                    
                    <div class="mb-3">
                        <label style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:6px;display:block">Message</label>
                        <textarea name="message" class="form-control" rows="4" style="font-size:13px;border-radius:8px;border:1px solid var(--border)" placeholder="Write your message..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" style="border-radius:8px;font-weight:600;padding:10px;font-size:13px">
                        Send Notification
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Notification History -->
    <div class="col-md-7">
        <div class="table-container">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:16px">Notification History</h6>
            
            <?php if (empty($notifications)): ?>
                <div class="text-center py-4" style="color:var(--text-muted)">
                    <i class="fas fa-bell-slash" style="font-size:24px"></i>
                    <p class="mt-2 mb-0" style="font-size:13px">No notifications sent yet</p>
                </div>
            <?php else: foreach ($notifications as $n): ?>
                <div class="notif-item type-<?= e($n['type']) ?>" style="<?= !$n['is_active'] ? 'opacity:.5' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex:1">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <span class="sb sb-<?= e($n['type']) ?>"><?= ucfirst(e($n['type'])) ?></span>
                                <span class="sb <?= $n['is_active'] ? 'sb-active' : 'sb-inactive' ?>"><?= $n['is_active'] ? 'Active' : 'Off' ?></span>
                                <?php if ($n['target'] === 'specific'): ?>
                                    <span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= e($n['target_name'] ?: 'User #'.$n['target_user_id']) ?></span>
                                <?php else: ?>
                                    <span class="sb" style="background:#F3F4F6;color:var(--text-secondary)">All Users</span>
                                <?php endif; ?>
                            </div>
                            <strong style="font-size:13px"><?= e($n['title']) ?></strong>
                            <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0"><?= e($n['message']) ?></p>
                            <small style="color:var(--text-muted);font-size:11px"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?> &middot; <?= e($n['sender_name'] ?? 'Owner') ?></small>
                        </div>
                        <div class="d-flex gap-1">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle_notification">
                                <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?= $n['is_active'] ? 'warning' : 'success' ?>" style="border-radius:6px" title="<?= $n['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="fas fa-<?= $n['is_active'] ? 'pause' : 'play' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this notification?')">
                                <input type="hidden" name="action" value="delete_notification">
                                <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:6px"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
function selectType(type, el) {
    document.getElementById('notifType').value = type;
    document.querySelectorAll('.type-opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
}
</script>

<?php include 'includes/footer.php'; ?>
