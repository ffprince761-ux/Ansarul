<?php
/**
 * Owner Panel - Users Management
 */
require_once 'config/db.php';
require_once 'config/functions.php';

requireOwnerLogin();

$pageTitle = 'Users Management';

// Get all users with their statistics
try {
    // Add is_blocked column if it doesn't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN DEFAULT FALSE");
    
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.business_name,
            u.mobile,
            u.created_at,
            u.updated_at,
            u.is_blocked,
            COUNT(DISTINCT p.id) as product_count,
            COUNT(DISTINCT c.id) as customer_count,
            COUNT(DISTINCT b.id) as bill_count,
            COALESCE(SUM(b.grand_total), 0) as total_revenue,
            GREATEST(
                COALESCE(MAX(b.created_at), u.updated_at),
                COALESCE(MAX(p.created_at), u.updated_at),
                COALESCE(MAX(c.created_at), u.updated_at),
                u.updated_at
            ) as last_active
        FROM users u
        LEFT JOIN products p ON u.id = p.user_id
        LEFT JOIN customers c ON u.id = c.user_id
        LEFT JOIN bills b ON u.id = b.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Users page error: " . $e->getMessage());
    $users = [];
}

include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 style="font-weight:700;font-size:14px;margin:0">All Users (<?= count($users) ?>)</h6>
    <input type="text" class="search-input" id="searchInput" placeholder="Search users...">
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="usersTable">
            <thead><tr><th>Business</th><th>Owner</th><th>Contact</th><th>Products</th><th>Customers</th><th>Bills</th><th>Revenue</th><th>Registered</th><th>Last Active</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="11" class="text-center" style="color:var(--text-muted);padding:24px">No users found</td></tr>
                <?php else: foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= e($user['business_name'] ?: 'N/A') ?></strong></td>
                        <td><?= e($user['name']) ?></td>
                        <td><small style="color:var(--text-muted)"><?= e($user['email']) ?><br><?= e($user['mobile'] ?: 'N/A') ?></small></td>
                        <td><span class="sb" style="background:var(--blue-light);color:var(--blue)"><?= number_format($user['product_count']) ?></span></td>
                        <td><span class="sb" style="background:#E0F2FE;color:#0369A1"><?= number_format($user['customer_count']) ?></span></td>
                        <td><span class="sb" style="background:var(--green-light);color:var(--green)"><?= number_format($user['bill_count']) ?></span></td>
                        <td><strong style="color:var(--green)"><?= formatCurrency($user['total_revenue']) ?></strong></td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= timeAgo($user['last_active']) ?></td>
                        <td>
                            <?php if ($user['is_blocked']): ?>
                                <span class="sb" style="background:var(--red-light);color:var(--red)">Blocked</span>
                            <?php else: ?>
                                <span class="sb" style="background:var(--green-light);color:var(--green)">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="user_detail.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" style="border-radius:6px" title="View"><i class="fas fa-eye"></i></a>
                                <?php if ($user['is_blocked']): ?>
                                    <button class="btn btn-sm btn-outline-success" style="border-radius:6px" onclick="unblockUser(<?= $user['id'] ?>)" title="Unblock"><i class="fas fa-unlock"></i></button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-warning" style="border-radius:6px" onclick="blockUser(<?= $user['id'] ?>)" title="Block"><i class="fas fa-ban"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger" style="border-radius:6px" onclick="deleteUser(<?= $user['id'] ?>, '<?= e($user['business_name'] ?: $user['name']) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchValue) ? '' : 'none';
    }
});

// Block user
function blockUser(userId) {
    if (confirm('Are you sure you want to block this user? They will not be able to login.')) {
        manageUser(userId, 'block');
    }
}

// Unblock user
function unblockUser(userId) {
    if (confirm('Are you sure you want to unblock this user?')) {
        manageUser(userId, 'unblock');
    }
}

// Delete user
function deleteUser(userId, businessName) {
    if (confirm(`⚠️ WARNING: Are you sure you want to DELETE "${businessName}"?\n\nThis will permanently delete:\n- All products\n- All customers\n- All bills\n- All expenses\n\nThis action CANNOT be undone!`)) {
        if (confirm('Final confirmation: Type YES to delete this user permanently')) {
            manageUser(userId, 'delete');
        }
    }
}

// Manage user API call
function manageUser(userId, action) {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    
    fetch('api/manage_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
