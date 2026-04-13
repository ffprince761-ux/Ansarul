<?php
require_once 'config/db.php';
require_once 'config/functions.php';
requireOwnerLogin();
$pageTitle = 'System Monitor';
include 'includes/header.php';
?>

<style>
.sb{display:inline-block;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600}
.m-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:18px;margin-bottom:16px}
.pulse-dot{width:8px;height:8px;border-radius:50%;display:inline-block;animation:pulse 2s infinite}
.pulse-green{background:var(--green)}.pulse-red{background:var(--red)}.pulse-amber{background:var(--amber)}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}
.gauge-container{position:relative;width:100px;height:100px;margin:0 auto}
.gauge-bg{fill:none;stroke:#F3F4F6;stroke-width:10}.gauge-fill{fill:none;stroke-width:10;stroke-linecap:round;transition:stroke-dashoffset 1s}
.gauge-text{font-size:20px;font-weight:700;fill:var(--text-primary)}.gauge-label{font-size:10px;fill:var(--text-muted)}
.speed-bar{height:5px;border-radius:3px;background:#F3F4F6;overflow:hidden;margin-top:6px}
.speed-fill{height:100%;border-radius:3px;transition:width .8s}
.speed-fast{background:var(--green)}.speed-medium{background:var(--amber)}.speed-slow{background:var(--red)}
.sb-online{background:var(--green-light);color:var(--green)}.sb-offline{background:var(--red-light);color:var(--red)}.sb-warning{background:var(--amber-light);color:var(--amber)}
.user-status-dot{width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:5px}
.usd-online{background:var(--green)}.usd-recent{background:var(--blue)}.usd-today{background:var(--amber)}.usd-inactive{background:var(--text-muted)}
.mini-chart{height:50px;display:flex;align-items:flex-end;gap:3px}
.mini-bar{flex:1;background:var(--primary);border-radius:2px 2px 0 0;min-height:3px}
.err-item{border-left:3px solid var(--red);padding:8px 12px;margin-bottom:8px;background:var(--red-light);border-radius:0 6px 6px 0;font-size:12px}
.m-val{font-size:24px;font-weight:700;color:var(--text-primary);line-height:1.1}
.m-label{font-size:11px;color:var(--text-muted);margin-top:2px}
.grade-badge{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff}
.grade-a{background:var(--green)}.grade-b{background:var(--blue)}.grade-c{background:var(--amber)}.grade-d{background:var(--red)}
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h5 class="mb-0" style="font-weight:700">System Monitor</h5>
            <span class="sb sb-online" style="font-size:10px">LIVE</span>
        </div>
        <small style="color:var(--text-muted)">Auto-refresh: <span id="countdown">30</span>s &middot; Last: <span id="lastUpdate">-</span></small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" style="border-radius:8px" onclick="loadMonitorData()"><i class="fas fa-sync-alt"></i> Refresh</button>
        <button class="btn btn-sm btn-outline-secondary" style="border-radius:8px" onclick="runSpeedTest()"><i class="fas fa-tachometer-alt"></i> Speed Test</button>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4" id="quickStats">
    <div class="col-md-2 col-6">
        <div class="stat-card text-center">
            <div id="stat-server" class="pulse-dot pulse-green mb-2"></div>
            <div class="m-val" id="stat-serverLabel">-</div>
            <div class="m-label">Server</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card text-center">
            <div class="m-val" id="stat-apiHealth">-</div>
            <div class="m-label">API Health</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card text-center">
            <div class="m-val" id="stat-dbStatus">-</div>
            <div class="m-label">DB Tables</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card text-center">
            <div id="stat-online-dot" class="pulse-dot pulse-green mb-2"></div>
            <div class="m-val" style="color:var(--green)" id="stat-activeUsers">0</div>
            <div class="m-label">Live Online</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card text-center">
            <div class="m-val" id="stat-todayBills">-</div>
            <div class="m-label">Today's Bills</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="stat-card text-center">
            <div class="m-val" id="stat-errors">-</div>
            <div class="m-label">Errors</div>
        </div>
    </div>
</div>

<!-- Row 2: Resources + DB Health + Performance -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="m-card">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Server Resources</h6>
            <div class="row text-center">
                <div class="col-6">
                    <svg class="gauge-container" viewBox="0 0 120 120">
                        <circle class="gauge-bg" cx="60" cy="60" r="50"/>
                        <circle class="gauge-fill" id="gauge-disk" cx="60" cy="60" r="50" stroke="var(--blue)" stroke-dasharray="314" stroke-dashoffset="314" transform="rotate(-90 60 60)"/>
                        <text class="gauge-text" x="60" y="58" text-anchor="middle" id="gauge-disk-text">0%</text>
                        <text class="gauge-label" x="60" y="74" text-anchor="middle">Disk</text>
                    </svg>
                </div>
                <div class="col-6">
                    <svg class="gauge-container" viewBox="0 0 120 120">
                        <circle class="gauge-bg" cx="60" cy="60" r="50"/>
                        <circle class="gauge-fill" id="gauge-db-conn" cx="60" cy="60" r="50" stroke="var(--primary)" stroke-dasharray="314" stroke-dashoffset="314" transform="rotate(-90 60 60)"/>
                        <text class="gauge-text" x="60" y="58" text-anchor="middle" id="gauge-dbconn-text">0%</text>
                        <text class="gauge-label" x="60" y="74" text-anchor="middle">DB Conn</text>
                    </svg>
                </div>
            </div>
            <div class="mt-3" id="serverDetails" style="font-size:12px;color:var(--text-muted)"></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="m-card">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Database Health</h6>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="grade-badge grade-a" id="dbGrade">A+</div>
                <div>
                    <div class="m-val" id="dbAvgTime">-</div>
                    <div class="m-label">Avg Query Time</div>
                </div>
            </div>
            <div id="dbInfo" style="font-size:12px"></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="m-card">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Performance</h6>
            <div id="performanceMetrics"></div>
            <div id="speedTestResults" class="mt-3" style="display:none">
                <hr style="border-color:var(--border)">
                <h6 style="font-weight:600;font-size:12px;margin-bottom:8px">Speed Test Results</h6>
                <div id="speedResults"></div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: API Endpoints + Live Bill Tracking -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="m-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 style="font-weight:700;font-size:14px;margin:0">API Endpoints</h6>
                <span class="sb sb-online" id="apiOverallStatus">All Online</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Endpoint</th><th>Status</th><th>Speed</th><th>Time</th><th>Size</th></tr></thead>
                    <tbody id="apiTable"></tbody>
                </table>
            </div>
            <div class="mt-2">
                <small style="color:var(--text-muted)">Total: <strong id="apiTotalTime">-</strong> &middot; Avg: <strong id="apiAvgTime">-</strong></small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="m-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 style="font-weight:700;font-size:14px;margin:0">Live Bill Tracking</h6>
                <span class="sb sb-online" style="font-size:10px">LIVE</span>
            </div>
            <div class="row text-center mb-3">
                <div class="col-3"><div class="m-val" id="billToday">0</div><div class="m-label">Today</div></div>
                <div class="col-3"><div class="m-val" id="billWeek">0</div><div class="m-label">This Week</div></div>
                <div class="col-3"><div class="m-val" style="color:var(--red)" id="billDue">0</div><div class="m-label">Due</div></div>
                <div class="col-3"><div class="m-val" style="color:var(--green)" id="billRevenue">₹0</div><div class="m-label">Today Rev.</div></div>
            </div>
            <h6 style="font-weight:600;font-size:12px;color:var(--text-muted);margin-bottom:8px">7-Day Trend</h6>
            <div class="mini-chart" id="billTrendChart"></div>
            <div class="mt-2" style="font-size:11px;color:var(--text-muted)" id="billTrendLabels"></div>
        </div>
    </div>
</div>

<!-- Row 4: Database Tables + User Activity -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="m-card">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Database Tables</h6>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Table</th><th>Status</th><th>Records</th><th>Query</th><th>Size</th></tr></thead>
                    <tbody id="dbTableBody"></tbody>
                </table>
            </div>
            <div class="mt-2">
                <small style="color:var(--text-muted)">Total: <strong id="dbTotalRecords">-</strong> &middot; Size: <strong id="dbTotalSize">-</strong></small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="m-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 style="font-weight:700;font-size:14px;margin:0">Live User Activity</h6>
                <span class="sb sb-online" style="font-size:10px">LIVE</span>
            </div>
            <div class="row text-center mb-3">
                <div class="col-3"><div class="m-val" style="color:var(--green)" id="userOnline">0</div><div class="m-label">Online</div></div>
                <div class="col-3"><div class="m-val" id="userTotal">0</div><div class="m-label">Total</div></div>
                <div class="col-3"><div class="m-val" style="color:var(--blue)" id="userToday">0</div><div class="m-label">New Today</div></div>
                <div class="col-3"><div class="m-val" style="color:var(--amber)" id="userWeek">0</div><div class="m-label">This Week</div></div>
            </div>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead><tr><th>User</th><th>Business</th><th>Today Bills</th><th>Device</th><th>Status</th></tr></thead>
                    <tbody id="userTable"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Row 5: Recent Bills + Error Logs -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="m-card">
            <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">Recent Bills</h6>
            <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Bill #</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Time</th></tr></thead>
                    <tbody id="recentBillsTable"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="m-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 style="font-weight:700;font-size:14px;margin:0">Error Monitor</h6>
                <span id="errorBadge" class="sb sb-online">No Errors</span>
            </div>
            <div class="row text-center mb-3">
                <div class="col-6"><div class="m-val" style="color:var(--red)" id="errorTotal">0</div><div class="m-label">Total Errors</div></div>
                <div class="col-6"><div class="m-val" style="color:var(--amber)" id="errorToday">0</div><div class="m-label">Today</div></div>
            </div>
            <div id="errorList" style="max-height:200px;overflow-y:auto"></div>
        </div>
    </div>
</div>

<!-- Row 6: PHP Extensions + Server Config -->
<div class="m-card">
    <h6 style="font-weight:700;font-size:14px;margin-bottom:12px">PHP Extensions & Server Config</h6>
    <div class="row" id="extensionsGrid"></div>
    <hr style="border-color:var(--border);margin:12px 0">
    <div class="row" id="serverConfigGrid" style="font-size:12px"></div>
</div>

<script>
let monitorData = null;
let refreshInterval = null;
let countdown = 30;

// ===== MAIN LOAD FUNCTION =====
async function loadMonitorData() {
    try {
        const res = await fetch('api/monitor.php?action=full&t=' + Date.now());
        monitorData = await res.json();
        if (monitorData.success) {
            renderAll(monitorData);
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        }
    } catch (e) {
        console.error('Monitor fetch error:', e);
        document.getElementById('stat-serverLabel').textContent = 'ERROR';
        document.getElementById('stat-server').className = 'pulse-dot pulse-red mb-2';
    }
}

// ===== RENDER ALL SECTIONS =====
function renderAll(d) {
    renderQuickStats(d);
    renderServerGauges(d.server, d.database);
    renderDatabaseHealth(d.database, d.performance);
    renderPerformance(d.performance);
    renderApiTable(d.apis);
    renderBillTracking(d.bills);
    renderDbTables(d.database);
    renderUserActivity(d.users);
    renderRecentBills(d.bills.recent);
    renderErrors(d.errors);
    renderExtensions(d.server);
    renderServerConfig(d.server);
}

// ===== QUICK STATS =====
function renderQuickStats(d) {
    document.getElementById('stat-serverLabel').textContent = 'Online';
    document.getElementById('stat-server').className = 'pulse-dot pulse-green mb-2';
    document.getElementById('stat-apiHealth').textContent = d.apis.health_percent + '%';
    
    const dbOnline = Object.values(d.database.tables).filter(t => t.status === 'online').length;
    const dbTotal = Object.keys(d.database.tables).length;
    document.getElementById('stat-dbStatus').textContent = dbOnline + '/' + dbTotal;
    
    const liveCount = d.users.live_online || 0;
    document.getElementById('stat-activeUsers').textContent = liveCount;
    document.getElementById('stat-online-dot').className = 'pulse-dot mb-2 ' + (liveCount > 0 ? 'pulse-green' : 'pulse-amber');
    
    document.getElementById('stat-todayBills').textContent = d.bills.today_count;
    document.getElementById('stat-errors').textContent = d.errors.today;
    document.getElementById('stat-errors').style.color = d.errors.today > 0 ? 'var(--red)' : 'var(--green)';
}

// ===== SERVER GAUGES =====
function renderServerGauges(server, db) {
    setGauge('gauge-disk', 'gauge-disk-text', server.disk_used_percent, '#3b82f6');
    setGauge('gauge-db-conn', 'gauge-dbconn-text', db.connection_usage_percent, '#8b5cf6');
    
    document.getElementById('serverDetails').innerHTML = `
        <div class="row">
            <div class="col-6"><strong>PHP:</strong> ${server.php_version}</div>
            <div class="col-6"><strong>Server:</strong> Apache</div>
            <div class="col-6"><strong>Memory:</strong> ${server.memory_used}</div>
            <div class="col-6"><strong>Disk Free:</strong> ${server.disk_free}</div>
            <div class="col-6"><strong>Upload:</strong> ${server.max_upload}</div>
            <div class="col-6"><strong>Timezone:</strong> ${server.timezone}</div>
        </div>`;
}

function setGauge(circleId, textId, percent, color) {
    const circle = document.getElementById(circleId);
    const text = document.getElementById(textId);
    const offset = 314 - (314 * percent / 100);
    circle.style.strokeDashoffset = offset;
    circle.style.stroke = percent > 80 ? '#ef4444' : percent > 60 ? '#f59e0b' : color;
    text.textContent = percent + '%';
}

// ===== DATABASE HEALTH =====
function renderDatabaseHealth(db, perf) {
    const gradeEl = document.getElementById('dbGrade');
    gradeEl.textContent = perf.grade;
    gradeEl.className = 'grade-badge grade-' + perf.grade[0].toLowerCase();
    
    document.getElementById('dbAvgTime').textContent = perf.avg_query_time_ms + 'ms';
    document.getElementById('dbInfo').innerHTML = `
        <div class="row">
            <div class="col-6 mb-1"><strong>MySQL:</strong> ${db.mysql_version}</div>
            <div class="col-6 mb-1"><strong>Uptime:</strong> ${db.uptime_formatted}</div>
            <div class="col-6 mb-1"><strong>Queries:</strong> ${db.total_queries}</div>
            <div class="col-6 mb-1"><strong>Connections:</strong> ${db.active_connections}/${db.max_connections}</div>
            <div class="col-6 mb-1"><strong>DB Size:</strong> ${db.database_size_mb} MB</div>
            <div class="col-6 mb-1"><strong>Records:</strong> ${db.total_records.toLocaleString()}</div>
        </div>`;
}

// ===== PERFORMANCE =====
function renderPerformance(perf) {
    let html = '';
    const labels = { simple_query: 'Simple Query', count_query: 'Count Query', join_query: 'Join Query', aggregate_query: 'Aggregate Query' };
    for (const [key, ms] of Object.entries(perf.benchmarks)) {
        const speed = ms < 5 ? 'fast' : ms < 20 ? 'medium' : 'slow';
        const width = Math.min(100, (ms / 50) * 100);
        html += `<div class="d-flex justify-content-between mb-1" style="font-size:12px;"><span>${labels[key] || key}</span><strong>${ms}ms</strong></div>
            <div class="speed-bar"><div class="speed-fill speed-${speed}" style="width:${width}%"></div></div>`;
    }
    document.getElementById('performanceMetrics').innerHTML = html;
}

// ===== API TABLE =====
function renderApiTable(apis) {
    let html = '';
    apis.endpoints.forEach(ep => {
        const statusClass = ep.status === 'online' ? 'sb-online' : 'sb-offline';
        const speedClass = ep.speed === 'fast' ? 'speed-fast' : ep.speed === 'medium' ? 'speed-medium' : 'speed-slow';
        const speedWidth = Math.min(100, (ep.response_time_ms / 2000) * 100);
        html += `<tr>
            <td><strong>${ep.name}</strong></td>
            <td><span class="sb ${statusClass}">${ep.status}</span></td>
            <td><div class="speed-bar" style="width:80px;"><div class="speed-fill ${speedClass}" style="width:${speedWidth}%"></div></div></td>
            <td>${ep.response_time_ms}ms</td>
            <td>${(ep.data_size / 1024).toFixed(1)}KB</td>
        </tr>`;
    });
    document.getElementById('apiTable').innerHTML = html;
    document.getElementById('apiTotalTime').textContent = apis.total_response_ms + 'ms';
    document.getElementById('apiAvgTime').textContent = apis.avg_response_ms + 'ms';
    
    const overallEl = document.getElementById('apiOverallStatus');
    if (apis.offline > 0) {
        overallEl.className = 'sb sb-offline';
        overallEl.textContent = apis.offline + ' Offline';
    } else {
        overallEl.className = 'sb sb-online';
        overallEl.textContent = 'All Online';
    }
}

// ===== BILL TRACKING =====
function renderBillTracking(bills) {
    document.getElementById('billToday').textContent = bills.today_count;
    document.getElementById('billWeek').textContent = bills.week_count;
    document.getElementById('billDue').textContent = bills.due_count;
    document.getElementById('billRevenue').textContent = '₹' + formatNum(bills.today_revenue);
    
    // Mini chart
    const trend = bills.trend || [];
    const maxCount = Math.max(...trend.map(t => t.count), 1);
    let chartHtml = '';
    let labelsHtml = '<div class="d-flex justify-content-between">';
    trend.forEach(t => {
        const h = Math.max(4, (t.count / maxCount) * 56);
        chartHtml += `<div class="mini-bar" style="height:${h}px;" title="${t.date}: ${t.count} bills, ₹${formatNum(t.revenue)}"></div>`;
        labelsHtml += `<span>${t.date.slice(5)}</span>`;
    });
    labelsHtml += '</div>';
    document.getElementById('billTrendChart').innerHTML = chartHtml || '<span class="text-muted">No data</span>';
    document.getElementById('billTrendLabels').innerHTML = trend.length > 0 ? labelsHtml : '';
}

// ===== DB TABLES =====
function renderDbTables(db) {
    let html = '';
    for (const [name, info] of Object.entries(db.tables)) {
        const statusClass = info.status === 'online' ? 'sb-online' : 'sb-offline';
        html += `<tr>
            <td><strong>${name}</strong></td>
            <td><span class="sb ${statusClass}">${info.status}</span></td>
            <td>${info.records.toLocaleString()}</td>
            <td>${info.query_time_ms}ms</td>
            <td>${info.size_mb}MB</td>
        </tr>`;
    }
    document.getElementById('dbTableBody').innerHTML = html;
    document.getElementById('dbTotalRecords').textContent = db.total_records.toLocaleString();
    document.getElementById('dbTotalSize').textContent = db.database_size_mb + ' MB';
}

// ===== USER ACTIVITY =====
function renderUserActivity(users) {
    document.getElementById('userOnline').textContent = users.live_online || 0;
    document.getElementById('userTotal').textContent = users.total;
    document.getElementById('userToday').textContent = users.today;
    document.getElementById('userWeek').textContent = users.this_week;
    
    let html = '';
    (users.active_users || []).forEach(u => {
        const dotClass = 'usd-' + u.status;
        const statusLabel = { online: 'Online', recent: 'Recent', today: 'Today', offline: 'Offline' }[u.status] || 'Offline';
        const statusBadge = u.status === 'online' ? 'sb-online' : u.status === 'offline' ? 'sb-offline' : 'sb-warning';
        const device = u.device_info || '-';
        const todayBills = u.today_bills || 0;
        html += `<tr>
            <td><span class="user-status-dot ${dotClass}"></span><a href="user_detail.php?id=${u.id}" style="color:inherit;text-decoration:none;font-weight:600" title="View Details">${u.name || 'User #' + u.id}</a></td>
            <td style="font-size:11px;">${u.business_name || '-'}</td>
            <td class="text-center"><strong>${todayBills}</strong></td>
            <td style="font-size:10px;">${device}</td>
            <td><span class="sb ${statusBadge}">${statusLabel}</span></td>
        </tr>`;
    });
    document.getElementById('userTable').innerHTML = html || '<tr><td colspan="5" class="text-center text-muted">No users</td></tr>';
}

// ===== RECENT BILLS =====
function renderRecentBills(bills) {
    let html = '';
    (bills || []).forEach(b => {
        const dueClass = b.due_status === 'paid' ? 'text-success' : b.due_status === 'due' ? 'text-danger' : 'text-warning';
        const time = new Date(b.created_at).toLocaleString('en-IN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short' });
        html += `<tr>
            <td><strong>${b.bill_number || '#' + b.id}</strong><br><small class="text-muted">${b.user_name || ''}</small></td>
            <td>${b.customer_name || 'Walk-in'}</td>
            <td><strong>₹${formatNum(b.total)}</strong></td>
            <td><span class="${dueClass}">${b.payment_mode || '-'}</span></td>
            <td style="font-size:11px;">${time}</td>
        </tr>`;
    });
    document.getElementById('recentBillsTable').innerHTML = html || '<tr><td colspan="5" class="text-center text-muted py-3">No bills yet</td></tr>';
}

// ===== ERRORS =====
function renderErrors(errors) {
    document.getElementById('errorTotal').textContent = errors.total;
    document.getElementById('errorToday').textContent = errors.today;
    
    const badge = document.getElementById('errorBadge');
    if (errors.today > 0) {
        badge.className = 'sb sb-offline';
        badge.textContent = errors.today + ' Today';
    } else {
        badge.className = 'sb sb-online';
        badge.textContent = 'No Errors';
    }
    
    let html = '';
    (errors.recent || []).slice(0, 8).forEach(e => {
        const time = e.created_at ? new Date(e.created_at).toLocaleString() : 'Unknown';
        html += `<div class="err-item">
            <div class="d-flex justify-content-between">
                <strong style="font-size:12px;color:var(--red)">${e.error_message || 'Unknown Error'}</strong>
                <small style="color:var(--text-muted)">${time}</small>
            </div>
            <small style="color:var(--text-muted)">User #${e.user_id || 'Guest'} | ${e.device_info || 'Unknown Device'}</small>
        </div>`;
    });
    document.getElementById('errorList').innerHTML = html || '<div class="text-center py-3" style="color:var(--green)"><i class="fas fa-check-circle" style="font-size:20px"></i><p class="mt-2 mb-0" style="font-size:12px">No errors reported</p></div>';
}

// ===== EXTENSIONS =====
function renderExtensions(server) {
    let html = '';
    for (const [ext, loaded] of Object.entries(server.extensions || {})) {
        const icon = loaded ? 'check-circle' : 'times-circle';
        const color = loaded ? 'var(--green)' : 'var(--red)';
        html += `<div class="col-md-2 col-4 mb-2 text-center">
            <i class="fas fa-${icon}" style="color:${color};font-size:16px"></i>
            <div style="font-size:11px;color:var(--text-muted)">${ext}</div>
        </div>`;
    }
    document.getElementById('extensionsGrid').innerHTML = html;
}

function renderServerConfig(server) {
    document.getElementById('serverConfigGrid').innerHTML = `
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>OS:</strong> ${server.os}</div>
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>Memory:</strong> ${server.max_memory}</div>
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>Upload:</strong> ${server.max_upload}</div>
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>Max Exec:</strong> ${server.max_execution}</div>
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>Disk:</strong> ${server.disk_free} / ${server.disk_total}</div>
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>Timezone:</strong> ${server.timezone}</div>
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>Post Max:</strong> ${server.max_post}</div>
        <div class="col-md-3 col-6 mb-2" style="color:var(--text-secondary)"><strong>Peak Mem:</strong> ${server.memory_peak}</div>
    `;
}

// ===== SPEED TEST =====
async function runSpeedTest() {
    document.getElementById('speedTestResults').style.display = 'block';
    document.getElementById('speedResults').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Running speed test...</div>';
    try {
        const res = await fetch('api/monitor.php?action=speed&t=' + Date.now());
        const data = await res.json();
        if (data.success) {
            let html = '';
            const labels = { db_read_10x: 'DB Read (10x)', fs_write_100kb: 'File Write 100KB', fs_read_100kb: 'File Read 100KB' };
            for (const [key, ms] of Object.entries(data.data)) {
                const speed = ms < 10 ? 'fast' : ms < 50 ? 'medium' : 'slow';
                const w = Math.min(100, (ms / 100) * 100);
                html += `<div class="d-flex justify-content-between mb-1" style="font-size:12px;"><span>${labels[key] || key}</span><strong>${ms}ms</strong></div>
                    <div class="speed-bar"><div class="speed-fill speed-${speed}" style="width:${w}%"></div></div>`;
            }
            document.getElementById('speedResults').innerHTML = html;
        }
    } catch (e) {
        document.getElementById('speedResults').innerHTML = '<div class="text-danger">Speed test failed</div>';
    }
}

// ===== HELPERS =====
function formatNum(n) {
    if (!n) return '0';
    n = parseFloat(n);
    if (n >= 100000) return (n / 100000).toFixed(1) + 'L';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return n.toLocaleString('en-IN');
}

// ===== AUTO REFRESH =====
function startAutoRefresh() {
    countdown = 30;
    clearInterval(refreshInterval);
    refreshInterval = setInterval(() => {
        countdown--;
        document.getElementById('countdown').textContent = countdown;
        if (countdown <= 0) {
            countdown = 30;
            loadMonitorData();
        }
    }, 1000);
}

// ===== INIT =====
loadMonitorData();
startAutoRefresh();
</script>

<?php include 'includes/footer.php'; ?>
