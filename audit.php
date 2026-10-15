<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();
$pageTitle = 'Audit Trail';
$currentUser = currentUserName();
$currentRole = currentRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail — Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=17">
<style>
.print-only-block {
    display: none !important;
}

/* ============================================================
   OFFICIAL PRINT TABLE REPORT STYLING
   ============================================================ */
@media print {
    @page {
        size: auto;
        margin: 12mm 15mm 15mm 15mm;
    }

    body {
        background: #ffffff !important;
        color: #111827 !important;
        font-family: 'Nunito', Arial, sans-serif !important;
        font-size: 9.5pt !important;
        line-height: 1.4 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Hide website UI chrome and on-screen cards */
    .ss-sidebar,
    .ss-topbar,
    .btn,
    .breadcrumb-custom,
    .page-header,
    #filterCard,
    .card-footer,
    #pagination,
    .modal,
    .toast-container,
    .no-print,
    .ss-avatar {
        display: none !important;
    }

    .app-wrapper,
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        display: block !important;
    }

    .card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }

    .card-header {
        display: none !important;
    }

    .card-body {
        padding: 0 !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    /* Show Print Sections */
    .print-only-block {
        display: block !important;
    }

    /* Document Letterhead */
    .print-header {
        text-align: center;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }

    .print-store-title {
        font-size: 18pt;
        font-weight: 900;
        letter-spacing: 1px;
        color: #0f172a;
        margin: 0 0 2px 0;
        text-transform: uppercase;
    }

    .print-store-sub {
        font-size: 9pt;
        color: #475569;
        margin: 0;
    }

    .print-report-title {
        margin-top: 10px;
        font-size: 13pt;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #047857;
    }

    /* Report Metadata */
    .print-meta-grid {
        width: 100%;
        margin-bottom: 14px;
        font-size: 9pt;
    }

    .print-meta-grid td {
        padding: 3px 0;
        border: none !important;
    }

    /* Summary KPI Strip */
    .print-kpi-strip {
        display: flex !important;
        justify-content: space-between;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px;
        padding: 8px 14px;
        margin-bottom: 16px;
    }

    .print-kpi-item {
        text-align: center;
        flex: 1;
    }

    .print-kpi-item:not(:last-child) {
        border-right: 1px solid #cbd5e1;
    }

    .print-kpi-label {
        font-size: 7.5pt;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        display: block;
        margin-bottom: 2px;
    }

    .print-kpi-val {
        font-size: 11pt;
        font-weight: 800;
        color: #0f172a;
    }

    /* Official Table */
    .table-report {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 20px !important;
    }

    .table-report th {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
        border: 1.5px solid #0f172a !important;
        padding: 7px 10px !important;
        font-size: 8.5pt !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
    }

    .table-report td {
        border: 1px solid #cbd5e1 !important;
        padding: 6px 10px !important;
        font-size: 8.5pt !important;
        color: #1e293b !important;
    }

    .table-report tbody tr:nth-child(even) {
        background-color: #f8fafc !important;
    }

    /* Signatures Section */
    .print-signatures {
        margin-top: 36px !important;
        padding-top: 10px !important;
        page-break-inside: avoid !important;
        width: 100%;
    }

    .sig-col {
        display: inline-block;
        width: 48%;
        vertical-align: top;
    }

    .sig-line {
        border-bottom: 1.5px solid #0f172a;
        width: 220px;
        margin-bottom: 6px;
    }

    .sig-name {
        font-size: 9.5pt;
        font-weight: 800;
        color: #0f172a;
    }

    .sig-role {
        font-size: 8.5pt;
        color: #64748b;
    }

    .print-footer-note {
        margin-top: 24px;
        padding-top: 8px;
        border-top: 1px dashed #cbd5e1;
        font-size: 8pt;
        color: #94a3b8;
        text-align: center;
    }
}
</style>
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
                    <i class="fa-solid fa-print me-1"></i>Print Report
                </button>
            </div>
        </div>

        <!-- PRINT-ONLY OFFICIAL DOCUMENT HEADER -->
        <div class="print-only-block print-header">
            <div class="print-store-title">Sharon Store</div>
            <div class="print-store-sub">Pampang Public Market, Angeles City, Pampanga</div>
            <div class="print-store-sub">Point of Sale &amp; Inventory Management System</div>
            <div class="print-report-title">Official System Audit Trail &amp; Activity Log</div>
            
            <table class="print-meta-grid mt-3">
                <tr>
                    <td style="width:50%; text-align:left;">
                        <strong>Date &amp; Time Generated:</strong> <?= date('F d, Y h:i A') ?><br>
                        <strong>Scope:</strong> Security, Transactions &amp; Administrative Actions
                    </td>
                    <td style="width:50%; text-align:right;">
                        <strong>Generated By:</strong> <?= htmlspecialchars($currentUser) ?> (<?= ucfirst(htmlspecialchars($currentRole)) ?>)<br>
                        <strong>Classification:</strong> Official Security &amp; Audit Document
                    </td>
                </tr>
            </table>

            <!-- Mini KPI Summary Strip -->
            <div class="print-kpi-strip">
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Total Logged Entries</span>
                    <span class="print-kpi-val" id="pTotalEntries">0 entries</span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Active Session</span>
                    <span class="print-kpi-val"><?= htmlspecialchars($currentUser) ?></span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">System Integrity</span>
                    <span class="print-kpi-val text-success">Verified &amp; Active</span>
                </div>
            </div>
        </div>

        <!-- Filters (Screen Only) -->
        <div class="card mb-3 no-print" id="filterCard">
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
            <div class="card-header d-flex justify-content-between align-items-center no-print">
                <h5 class="mb-0"><i class="fa-solid fa-list me-2 text-emerald"></i>Activity Log</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" id="lastUpdated"></span>
                    <span class="badge badge-emerald" id="logCount">0 entries</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-report mb-0" id="auditTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 140px;">Date / Time</th>
                                <th style="width: 160px;">User</th>
                                <th style="width: 100px;">Module</th>
                                <th style="width: 120px;">Action</th>
                                <th>Description</th>
                                <th style="width: 110px;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="auditBody">
                            <tr><td colspan="7" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center no-print">
                <div class="text-muted small" id="pageInfo"></div>
                <div class="d-flex gap-2" id="pagination"></div>
            </div>
        </div>

        <!-- PRINT-ONLY OFFICIAL SIGNATURES & AUDIT FOOTER -->
        <div class="print-only-block print-signatures">
            <div style="display:flex; justify-content:space-between;">
                <div class="sig-col" style="text-align:left;">
                    <div class="sig-line"></div>
                    <div class="sig-name"><?= htmlspecialchars($currentUser) ?></div>
                    <div class="sig-role">Prepared By (<?= ucfirst(htmlspecialchars($currentRole)) ?>)</div>
                </div>
                <div class="sig-col" style="text-align:right;">
                    <div class="sig-line" style="margin-left:auto;"></div>
                    <div class="sig-name">Sharon Dela Cruz</div>
                    <div class="sig-role">Approved By (Store Owner)</div>
                </div>
            </div>
            <div class="print-footer-note">
                Confidential &bull; Generated from Sharon Store POS &amp; Inventory Management System &bull; <?= date('Y-m-d H:i:s') ?>
            </div>
        </div>

    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js?v=25"></script>
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
    document.getElementById('auditBody').innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading</td></tr>`;

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
        document.getElementById('pTotalEntries').textContent = `${j.total} entries`;
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
                <td class="text-muted fw-bold">${log.log_id}</td>
                <td style="white-space:nowrap;">
                    <div style="font-size:0.82rem; font-weight:600;">${log.log_time.substring(0,10)}</div>
                    <div class="text-muted" style="font-size:0.72rem;">${log.log_time.substring(11,19)}</div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="ss-avatar" style="width:28px;height:28px;font-size:0.7rem;">${initials}</div>
                        <div>
                            <div style="font-weight:600; font-size:0.85rem;">${esc(log.full_name || 'System')}</div>
                            <div class="text-muted" style="font-size:0.72rem;">${esc(log.role || '')}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge" style="background:${color}22; color:${color}; font-size:0.75rem;">${esc(log.module)}</span></td>
                <td><code style="font-size:0.78rem; font-weight:700;">${esc(log.action)}</code></td>
                <td style="font-size:0.83rem; max-width:280px; word-break:break-word;">${esc(log.description || '—')}</td>
                <td><span class="text-muted" style="font-size:0.75rem; font-family:monospace;">${esc(log.ip_address || '—')}</span></td>
            </tr>`;
        });
        document.getElementById('auditBody').innerHTML = html;
        renderPagination(j.page, j.total_pages);
    } catch(err) {
        console.error('loadLogs error:', err);
        document.getElementById('auditBody').innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to load audit logs. Please check your connection.</td></tr>`;
    }
}

function renderPagination(cur, total) {
    if (total <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let btns = '';
    btns += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(${cur-1})" ${cur===1?'disabled':''}><i class="fa-solid fa-chevron-left"></i></button>`;
    for (let p = Math.max(1, cur-2); p <= Math.min(total, cur+2); p++) {
        btns += `<button class="btn btn-sm ${p===cur?'btn-primary':'btn-secondary'}" onclick="loadLogs(${p})">${p}</button>`;
    }
    btns += `<button class="btn btn-sm btn-secondary" onclick="loadLogs(${cur+1})" ${cur===total?'disabled':''}><i class="fa-solid fa-chevron-right"></i></button>`;
    document.getElementById('pagination').innerHTML = btns;
}

// ── Search & Filter ───────────────────────────────────────────
document.getElementById('btnSearch').addEventListener('click', () => loadLogs(1));
document.getElementById('btnReset').addEventListener('click', () => {
    document.getElementById('filterUser').value      = '';
    document.getElementById('filterModule').value    = '';
    document.getElementById('filterAction').value    = '';
    document.getElementById('filterDateFrom').value  = '';
    document.getElementById('filterDateTo').value    = '';
    loadLogs(1);
});

// Bind Enter key on all filter inputs
document.querySelectorAll('#filterUser, #filterModule, #filterAction, #filterDateFrom, #filterDateTo').forEach(el => {
    el.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            document.getElementById('btnSearch').click();
        }
    });
});

// ── Auto-Refresh Toggle ───────────────────────────────────────
const btnAuto = document.getElementById('btnAutoRefresh');
btnAuto.addEventListener('click', () => {
    if (autoRefreshId) {
        clearInterval(autoRefreshId);
        autoRefreshId = null;
        btnAuto.innerHTML = '<i class="fa-solid fa-rotate me-1"></i>Auto-Refresh: OFF';
        btnAuto.classList.replace('btn-primary', 'btn-secondary');
    } else {
        autoRefreshId = setInterval(() => loadLogs(currentPage), 30000);
        btnAuto.innerHTML = '<i class="fa-solid fa-rotate fa-spin me-1"></i>Auto-Refresh: ON (30s)';
        btnAuto.classList.replace('btn-secondary', 'btn-primary');
    }
});

loadLogs(1);
</script>
</body>
</html>
