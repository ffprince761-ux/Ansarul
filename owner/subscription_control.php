<?php
/**
 * Owner Panel - Subscription Control
 * Manage billing limits, free/paid plans per user
 * Master switch to enable/disable the system
 */
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();

$pageTitle = 'Subscription Control';

try {
    // Ensure tables exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_plans (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        plan_type ENUM('free','paid') DEFAULT 'free',
        bill_limit INT DEFAULT 500,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES 
        ('billing_limit_enabled', '0'),
        ('default_bill_limit', '500')
    ");

    // Get settings
    $masterEnabled = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'billing_limit_enabled'")->fetch()['setting_value'] ?? '0';
    $defaultLimit = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'default_bill_limit'")->fetch()['setting_value'] ?? '500';

    // Get all users with plan info and bill counts
    $users = $pdo->query("
        SELECT u.id, u.name, u.email, u.business_name, u.created_at, u.is_blocked,
            COUNT(DISTINCT b.id) as bill_count,
            COALESCE(up.plan_type, 'free') as plan_type,
            COALESCE(up.bill_limit, $defaultLimit) as bill_limit
        FROM users u
        LEFT JOIN bills b ON u.id = b.user_id
        LEFT JOIN user_plans up ON u.id = up.user_id
        GROUP BY u.id
        ORDER BY bill_count DESC
    ")->fetchAll();

    $totalUsers = count($users);
    $freeUsers = count(array_filter($users, fn($u) => $u['plan_type'] === 'free'));
    $paidUsers = $totalUsers - $freeUsers;
    $limitReached = count(array_filter($users, fn($u) => $u['plan_type'] === 'free' && $u['bill_count'] >= $u['bill_limit']));

} catch (PDOException $e) {
    error_log("Subscription control error: " . $e->getMessage());
    $users = [];
    $masterEnabled = '0';
    $defaultLimit = '500';
    $totalUsers = $freeUsers = $paidUsers = $limitReached = 0;
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.sb-free{background:var(--blue-light);color:var(--blue)}.sb-paid{background:var(--green-light);color:var(--green)}
.sb-limit{background:var(--red-light);color:var(--red)}.sb-ok{background:var(--green-light);color:var(--green)}
.master-switch{display:flex;align-items:center;gap:12px;padding:16px 20px;border-radius:10px;border:2px solid var(--border)}
.master-switch.on{border-color:var(--green);background:var(--green-light)}
.master-switch.off{border-color:var(--border);background:#F9FAFB}
.toggle{position:relative;width:48px;height:26px;background:#D1D5DB;border-radius:13px;cursor:pointer;transition:all .3s}
.toggle.active{background:var(--green)}
.toggle::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;transition:all .3s;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.toggle.active::after{left:25px}
.plan-select{padding:3px 8px;border-radius:6px;border:1px solid var(--border);font-size:12px;font-weight:600;cursor:pointer;background:#fff}
.limit-input{width:70px;padding:3px 6px;border-radius:6px;border:1px solid var(--border);font-size:12px;text-align:center}
.progress-bar-mini{height:4px;border-radius:2px;background:#F3F4F6;overflow:hidden;width:80px;display:inline-block;vertical-align:middle;margin-left:6px}
.progress-fill{height:100%;border-radius:2px;transition:width .3s}
</style>

<!-- Master Switch -->
<div class="master-switch <?= $masterEnabled === '1' ? 'on' : 'off' ?> mb-4" id="masterSwitch">
    <div class="toggle <?= $masterEnabled === '1' ? 'active' : '' ?>" id="masterToggle" onclick="toggleMaster()"></div>
    <div>
        <div style="font-weight:700;font-size:15px">
            Billing Limits 
            <span class="sb <?= $masterEnabled === '1' ? 'sb-paid' : '' ?>" style="<?= $masterEnabled === '1' ? '' : 'background:#F3F4F6;color:var(--text-muted)' ?>" id="masterLabel">
                <?= $masterEnabled === '1' ? 'ON' : 'OFF' ?>
            </span>
        </div>
        <small style="color:var(--text-muted)" id="masterDesc">
            <?= $masterEnabled === '1' ? 'System is enforcing bill limits for free users' : 'System is OFF — all users can create unlimited bills' ?>
        </small>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Total Users</div><div class="stat-value"><?= $totalUsers ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Free Plan</div><div class="stat-value" style="color:var(--blue)"><?= $freeUsers ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Paid Plan</div><div class="stat-value" style="color:var(--green)"><?= $paidUsers ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Limit Reached</div><div class="stat-value" style="color:var(--red)"><?= $limitReached ?></div></div></div>
</div>

<!-- Default Limit Setting -->
<div class="table-container mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 style="font-weight:700;font-size:14px;margin:0">Default Bill Limit</h6>
            <small style="color:var(--text-muted)">Applied to new free users who don't have a custom limit</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="number" id="defaultLimit" value="<?= e($defaultLimit) ?>" min="1" class="limit-input" style="width:90px">
            <button class="btn btn-sm btn-primary" style="border-radius:8px" onclick="updateDefaultLimit()">Save</button>
        </div>
    </div>
</div>

<!-- User Plans Table -->
<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 style="font-weight:700;font-size:14px;margin:0">User Plans</h6>
        <input type="text" class="search-input" id="searchInput" placeholder="Search users...">
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="plansTable">
            <thead><tr><th>User</th><th>Business</th><th>Plan</th><th>Bills Used</th><th>Limit</th><th>Usage</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($users as $u):
                    $pct = $u['bill_limit'] > 0 ? min(100, round(($u['bill_count'] / $u['bill_limit']) * 100)) : 0;
                    $barColor = $u['plan_type'] === 'paid' ? 'var(--green)' : ($pct >= 100 ? 'var(--red)' : ($pct >= 80 ? 'var(--amber)' : 'var(--blue)'));
                ?>
                    <tr class="user-row" data-id="<?= $u['id'] ?>">
                        <td>
                            <strong><?= e($u['name']) ?></strong>
                            <br><small style="color:var(--text-muted)"><?= e($u['email']) ?></small>
                        </td>
                        <td style="font-size:12px;color:var(--text-secondary)"><?= e($u['business_name'] ?: '-') ?></td>
                        <td>
                            <select class="plan-select" onchange="updatePlan(<?= $u['id'] ?>, this.value, document.getElementById('limit-<?= $u['id'] ?>').value)" 
                                style="color:<?= $u['plan_type'] === 'paid' ? 'var(--green)' : 'var(--blue)' ?>">
                                <option value="free" <?= $u['plan_type'] === 'free' ? 'selected' : '' ?>>Free</option>
                                <option value="paid" <?= $u['plan_type'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                            </select>
                        </td>
                        <td>
                            <strong><?= number_format($u['bill_count']) ?></strong>
                        </td>
                        <td>
                            <input type="number" id="limit-<?= $u['id'] ?>" value="<?= $u['bill_limit'] ?>" min="1" class="limit-input"
                                onchange="updatePlan(<?= $u['id'] ?>, this.closest('tr').querySelector('.plan-select').value, this.value)"
                                <?= $u['plan_type'] === 'paid' ? 'disabled style="opacity:.4"' : '' ?>>
                        </td>
                        <td>
                            <?php if ($u['plan_type'] === 'paid'): ?>
                                <span class="sb sb-paid">Unlimited</span>
                            <?php else: ?>
                                <span style="font-size:12px;font-weight:600;color:<?= $barColor ?>"><?= $pct ?>%</span>
                                <div class="progress-bar-mini">
                                    <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div>
                                </div>
                                <?php if ($pct >= 100): ?>
                                    <span class="sb sb-limit" style="margin-left:4px">LIMIT</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="user_detail.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;font-size:11px"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleMaster() {
    const toggle = document.getElementById('masterToggle');
    const sw = document.getElementById('masterSwitch');
    const label = document.getElementById('masterLabel');
    const desc = document.getElementById('masterDesc');
    const isOn = toggle.classList.contains('active');
    const newState = isOn ? 0 : 1;

    fetch('api/update_plan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=toggle_master&enabled=' + newState
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toggle.classList.toggle('active');
            sw.classList.toggle('on');
            sw.classList.toggle('off');
            if (newState) {
                label.textContent = 'ON';
                label.className = 'sb sb-paid';
                desc.textContent = 'System is enforcing bill limits for free users';
            } else {
                label.textContent = 'OFF';
                label.className = 'sb';
                label.style.background = '#F3F4F6';
                label.style.color = 'var(--text-muted)';
                desc.textContent = 'System is OFF — all users can create unlimited bills';
            }
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    });
}

function updateDefaultLimit() {
    const limit = document.getElementById('defaultLimit').value;
    fetch('api/update_plan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_default_limit&limit=' + limit
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Default limit updated to ' + data.limit);
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    });
}

function updatePlan(userId, planType, billLimit) {
    const limitInput = document.getElementById('limit-' + userId);
    if (planType === 'paid') {
        limitInput.disabled = true;
        limitInput.style.opacity = '.4';
    } else {
        limitInput.disabled = false;
        limitInput.style.opacity = '1';
    }

    fetch('api/update_plan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update_user_plan&user_id=' + userId + '&plan_type=' + planType + '&bill_limit=' + billLimit
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update UI - find the row and update usage column
            const row = document.querySelector('tr[data-id="' + userId + '"]');
            const usageCell = row.cells[5];
            const selectEl = row.querySelector('.plan-select');
            selectEl.style.color = planType === 'paid' ? 'var(--green)' : 'var(--blue)';
            
            if (planType === 'paid') {
                usageCell.innerHTML = '<span class="sb sb-paid">Unlimited</span>';
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    });
}

document.getElementById('searchInput').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
