<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail — Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=14">
    <?php
    require_once __DIR__ . '/includes/auth_check.php';
    requireAdmin();
    $pageTitle = 'Audit Trail';
    ?>
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Audit Trail</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-shield-halved me-2 text-emerald"></i>Audit Trail</h1>
                <p class="page-header-subtitle">Track all system activities, user actions, and changes across all modules.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" id="btnAutoRefresh" title="Auto-refresh every 30s">
                    <i class="fa-solid fa-rotate me-1"></i>Auto-Refresh: OFF
                </button>
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i>Print
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">User</label>
                        <select id="filterUser" class="form-select"><option value="">All Users</option></select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Module</label>
                        <select id="filterModule" class="form-select"><option value="">All Modules</option></select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Action (search)</label>
                        <input type="text" id="filterAction" class="form-control" placeholder="e.g. LOGIN">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" id="filterDateFrom" class="form-control">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" id="filterDateTo" class="form-control">
                    </div>
                    <div class="col-12 col-md-1 d-flex gap-2">
                        <button class="btn btn-primary flex-fill" id="btnSearch"><i class="fa-solid fa-search"></i></button>
                        <button class="btn btn-secondary flex-fill" id="btnReset"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-list me-2 text-emerald"></i>Activity Log</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" id="lastUpdated"></span>
                    <span class="badge badge-emerald" id="logCount">0 entries</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date / Time</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody id="auditBody">
                            <tr><td colspan="7" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small" id="pageInfo"></div>
                <div class="d-flex gap-2" id="pagination"></div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
let currentPage   = 1;
let autoRefreshId = null;

const MODULE_COLORS = {
    'Auth':      '#10b981',
    'POS':       '#3b82f6',
    'Inventory': '#f59e0b',
    'Accounts':  '#8b5cf6',
    'Backup':    '#06b6d4',
    'System':    '#94a3b8',
};

async function loadLogs(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({
        page,
        limit: 20,
        user_id:   document.getElementById('filterUser').value,
        module:    document.getElementById('filterModule').value,
        action:    document.getElementById('filterAction').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to:   document.getElementById('filterDateTo').value,
    });
    document.getElementById('auditBody').innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading…</td></tr>`;

    try {
        const r = await fetch(`/sharonstore3.0/api/audit_logs.php?${params}`);
        const j = await r.json();

        // Populate filter dropdowns on first load
        if (page === 1) {
            const userSel = document.getElementById('filterUser');
            if (userSel.options.length === 1 && j.users) {
                j.users.forEach(u => { userSel.innerHTML += `<option value="${u.user_id}">${esc(u.full_name)}</option>`; });
            }
            const modSel = document.getElementById('filterModule');
            if (modSel.options.length === 1 && j.modules) {
                j.modules.forEach(m => { modSel.innerHTML += `<option value="${m}">${esc(m)}</option>`; });
            }
        }

        document.getElementById('logCount').textContent = `${j.total} entries`;
        document.getElementById('pageInfo').textContent = `Page ${j.page} of ${j.total_pages}`;
        document.getElementById('lastUpdated').textContent = 'Updated: ' + new Date().toLocaleTimeString();

        if (!j.data.length) {
            document.getElementById('auditBody').innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-shield-halved fa-2x mb-2"></i><br>No log entries found.</td></tr>`;
            document.getElementById('pagination').innerHTML = '';
            return;
        }

        let html = '';
        j.data.forEach((log, idx) => {
            const color  = MODULE_COLORS[log.module] || '#94a3b8';
            const initials = log.full_name ? log.full_name.split(' ').map(w=>w[0]?.toUpperCase()||'').join('').substring(0,2) : '?';
            html += `<tr style="border-left: 3px solid ${color};">
                <td class="text-muted">${log.log_id}</td>
                <td style="white-space:nowrap;">
                    <div style="font-size:0.82rem; font-weight:600;">${log.log_time.substring(0,10)}</div>
                    <div class="text-muted" style="font-size:0.72rem;">${log.log_time.substring(11,19)}</div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="ss-avatar" style="width:28px;height:28px;font-size:0.7rem;">${initials}</div>
                        <div>
                            <div style="font-size:0.82rem; font-weight:600;">${esc(log.full_name||'System')}</div>
                            <div class="text-muted" style="font-size:0.7rem;">${esc(log.role||'—')}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge" style="background:rgba(${hexToRgb(color)},0.15); color:${color}; border:1px solid rgba(${hexToRgb(color)},0.3);">${esc(log.module)}</span></td>
                <td><code style="font-size:0.75rem; color:#10b981;">${esc(log.action)}</code></td>
                <td style="font-size:0.8rem; max-width:280px; word-break:break-word;">${esc(log.description||'—')}</td>
                <td class="text-muted" style="font-size:0.75rem; white-space:nowrap;">${esc(log.ip_address||'—')}</td>
            </tr>`;
        });
        document.getElementById('auditBody').innerHTML = html;

        // Pagination
        let pg = '';
        if (j.total_pages > 1) {
            if (page > 1) pg += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(${page-1})"><i class="fa-solid fa-chevron-left"></i></button>`;
            for (let p = Math.max(1,page-2); p <= Math.min(j.total_pages, page+2); p++) {
                pg += `<button class="btn btn-sm ${p===page?'btn-primary':'btn-secondary'}" onclick="loadLogs(${p})">${p}</button>`;
            }
            if (page < j.total_pages) pg += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(${page+1})"><i class="fa-solid fa-chevron-right"></i></button>`;
        }
        document.getElementById('pagination').innerHTML = pg;
    } catch (err) {
        document.getElementById('auditBody').innerHTML = `<tr><td colspan="7" class="text-center py-5 text-danger"><i class="fa-solid fa-circle-exclamation fa-2x mb-2"></i><br>Failed to load audit logs. Please try again.<br><small class="text-muted">${err.message}</small></td></tr>`;
        document.getElementById('pagination').innerHTML = '';
    }
}

function hexToRgb(hex) {
    const r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return r ? `${parseInt(r[1],16)},${parseInt(r[2],16)},${parseInt(r[3],16)}` : '148,163,184';
}

document.getElementById('btnSearch').addEventListener('click', () => loadLogs(1));
document.getElementById('btnReset').addEventListener('click', () => {
    ['filterUser','filterModule','filterAction','filterDateFrom','filterDateTo'].forEach(id => {
        const el = document.getElementById(id);
        if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
    });
    loadLogs(1);
});

// Enter key triggers search from any filter input
document.querySelectorAll('#filterUser,#filterModule,#filterAction,#filterDateFrom,#filterDateTo').forEach(el => {
    el.addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('btnSearch').click(); });
});

// Auto-refresh toggle
document.getElementById('btnAutoRefresh').addEventListener('click', function() {
    if (autoRefreshId) {
        clearInterval(autoRefreshId);
        autoRefreshId = null;
        this.innerHTML = '<i class="fa-solid fa-rotate me-1"></i>Auto-Refresh: OFF';
        this.classList.remove('btn-primary');
        this.classList.add('btn-secondary');
    } else {
        autoRefreshId = setInterval(() => loadLogs(currentPage), 30000);
        this.innerHTML = '<i class="fa-solid fa-rotate me-1 fa-spin"></i>Auto-Refresh: ON';
        this.classList.remove('btn-secondary');
        this.classList.add('btn-primary');
        showToast('Auto-refresh enabled (every 30s)', 'info');
    }
});


loadLogs(1);
</script>
</body>
</html>
