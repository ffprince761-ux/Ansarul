<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Owner Panel' ?> - Binest</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            --green: #059669;
            --green-light: #ECFDF5;
            --red: #DC2626;
            --red-light: #FEF2F2;
            --amber: #D97706;
            --amber-light: #FFFBEB;
            --blue: #2563EB;
            --blue-light: #EFF6FF;
            --sidebar-w: 240px;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); color: var(--text-primary); font-size: 14px; line-height: 1.5; }

        /* ---- Sidebar ---- */
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-w); background: var(--card-bg); border-right: 1px solid var(--border); padding: 0; overflow-y: auto; z-index: 1000; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 20px 20px 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); }
        .sidebar-brand img { height: 28px; }
        .sidebar-brand span { font-size: 17px; font-weight: 700; color: var(--text-primary); }
        .sidebar-menu { list-style: none; padding: 8px 0; margin: 0; flex: 1; }
        .sidebar-section { padding: 16px 20px 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        .sidebar-menu li a { display: flex; align-items: center; padding: 8px 20px; color: var(--text-secondary); text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 0; transition: color .15s, background .15s; }
        .sidebar-menu li a i { width: 20px; margin-right: 10px; font-size: 14px; color: var(--text-muted); text-align: center; }
        .sidebar-menu li a:hover { background: var(--bg); color: var(--text-primary); }
        .sidebar-menu li a:hover i { color: var(--text-secondary); }
        .sidebar-menu li a.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
        .sidebar-menu li a.active i { color: var(--primary); }

        /* ---- Main ---- */
        .main-content { margin-left: var(--sidebar-w); padding: 24px 32px; min-height: 100vh; }
        .top-navbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        .top-navbar h4 { font-size: 18px; font-weight: 700; margin: 0; color: var(--text-primary); }
        .top-navbar small { font-size: 12px; color: var(--text-muted); }
        .user-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 13px; cursor: pointer; }

        /* ---- Cards ---- */
        .stat-card, .card-clean { background: var(--card-bg); border: 1px solid var(--border); border-radius: 10px; padding: 20px; }
        .stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff; margin-bottom: 12px; }
        .stat-value { font-size: 24px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
        .stat-label { color: var(--text-muted); font-size: 12px; font-weight: 500; margin-bottom: 6px; }
        .stat-trend { font-size: 12px; color: var(--text-secondary); }

        .chart-container { background: var(--card-bg); border: 1px solid var(--border); border-radius: 10px; padding: 20px; margin-bottom: 16px; }
        .table-container { background: var(--card-bg); border: 1px solid var(--border); border-radius: 10px; padding: 20px; }

        /* ---- Common ---- */
        .badge-clean { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .bg-green-soft { background: var(--green-light); color: var(--green); }
        .bg-red-soft { background: var(--red-light); color: var(--red); }
        .bg-amber-soft { background: var(--amber-light); color: var(--amber); }
        .bg-blue-soft { background: var(--blue-light); color: var(--blue); }
        .bg-gray-soft { background: #F3F4F6; color: var(--text-secondary); }

        .table th { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); border-bottom: 1px solid var(--border); padding: 10px 12px; white-space: nowrap; }
        .table td { font-size: 13px; color: var(--text-primary); padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #F3F4F6; }
        .table-hover tbody tr:hover { background: var(--bg); }

        .btn-primary { background: var(--primary); border-color: var(--primary); font-weight: 600; font-size: 13px; border-radius: 8px; }
        .btn-primary:hover { background: #4338CA; border-color: #4338CA; }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); font-weight: 600; font-size: 13px; border-radius: 8px; }
        .form-control, .form-select { border: 1px solid var(--border); border-radius: 8px; font-size: 13px; padding: 8px 12px; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
        .modal-content { border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .modal-header { border-bottom: 1px solid var(--border); }

        .search-input { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 13px; outline: none; width: 260px; transition: border .15s; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,.08); }

        /* Tabs */
        .tabs-clean { display: flex; gap: 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .tabs-clean .tab-item { padding: 10px 20px; font-size: 13px; font-weight: 600; color: var(--text-secondary); cursor: pointer; border: none; background: none; border-bottom: 2px solid transparent; transition: all .15s; }
        .tabs-clean .tab-item.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tabs-clean .tab-item:hover:not(.active) { color: var(--text-primary); }
        .tab-count { background: #F3F4F6; color: var(--text-secondary); padding: 1px 8px; border-radius: 10px; font-size: 11px; margin-left: 6px; }
        .tab-item.active .tab-count { background: var(--primary-light); color: var(--primary); }

        .tp { display: none; } .tp.active { display: block; }

        /* Progress bar */
        .progress-sm { height: 6px; background: #F3F4F6; border-radius: 3px; overflow: hidden; }
        .progress-sm .fill { height: 100%; border-radius: 3px; }

        /* Mobile */
        .hamburger { display: none; position: fixed; top: 14px; left: 14px; z-index: 1100; width: 36px; height: 36px; border-radius: 8px; background: var(--card-bg); border: 1px solid var(--border); color: var(--text-primary); font-size: 16px; align-items: center; justify-content: center; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.3); z-index: 999; }
        @media(max-width: 991px) {
            .sidebar { transform: translateX(-100%); transition: transform .25s ease; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main-content { margin-left: 0 !important; padding: 16px !important; }
            .hamburger { display: flex; }
            .top-navbar { margin-left: 44px; }
            .search-input { width: 180px; }
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
    </style>
</head>
<body>
    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="../assets/icon.png" alt="Binest" style="width: 32px; height: 32px;">
            <span>Binest</span>
        </div>
        
        <ul class="sidebar-menu">
            <div class="sidebar-section">Overview</div>
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-grid-2"></i><span>Dashboard</span></a></li>
            <li><a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i><span>Users</span></a></li>
            <li><a href="revenue.php" class="<?= basename($_SERVER['PHP_SELF']) == 'revenue.php' ? 'active' : '' ?>"><i class="fas fa-indian-rupee-sign"></i><span>Revenue</span></a></li>
            <li><a href="analytics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>"><i class="fas fa-chart-simple"></i><span>Analytics</span></a></li>

            <div class="sidebar-section">Data</div>
            <li><a href="all_bills.php" class="<?= basename($_SERVER['PHP_SELF']) == 'all_bills.php' ? 'active' : '' ?>"><i class="fas fa-file-invoice"></i><span>Bills</span></a></li>
            <li><a href="all_products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'all_products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="all_customers.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['all_customers.php','customer_detail.php']) ? 'active' : '' ?>"><i class="fas fa-user-group"></i><span>Customers</span></a></li>
            <li><a href="all_expenses.php" class="<?= basename($_SERVER['PHP_SELF']) == 'all_expenses.php' ? 'active' : '' ?>"><i class="fas fa-receipt"></i><span>Expenses</span></a></li>

            <div class="sidebar-section">Monitoring</div>
            <li><a href="connection_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'connection_dashboard.php' ? 'active' : '' ?>"><i class="fas fa-signal"></i><span>Live Monitor</span></a></li>
            <li><a href="error_logs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'error_logs.php' ? 'active' : '' ?>"><i class="fas fa-triangle-exclamation"></i><span>Error Logs</span></a></li>
            <li><a href="system.php" class="<?= basename($_SERVER['PHP_SELF']) == 'system.php' ? 'active' : '' ?>"><i class="fas fa-server"></i><span>System</span></a></li>

            <div class="sidebar-section">Admin</div>
            <li><a href="subscription_control.php" class="<?= basename($_SERVER['PHP_SELF']) == 'subscription_control.php' ? 'active' : '' ?>"><i class="fas fa-crown"></i><span>Subscriptions</span></a></li>
            <li><a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i><span>Notifications</span></a></li>
            <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-gear"></i><span>Settings</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h4><?= $pageTitle ?? 'Dashboard' ?></h4>
                <small><?= e($_SESSION['owner_name'] ?? 'Owner') ?></small>
            </div>
            <div class="user-avatar" title="<?= e($_SESSION['owner_username'] ?? '') ?>">
                <?= strtoupper(substr($_SESSION['owner_name'] ?? 'O', 0, 1)) ?>
            </div>
        </div>
