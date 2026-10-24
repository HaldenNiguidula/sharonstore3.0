<?php
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();
$pageTitle = 'Stock Monitoring';
$isAdmin   = isAdmin();
$currentUser = currentUserName();
$currentRole = currentRole();

$hasOpenShift = true;
if ($currentRole === 'cashier') {
    require_once __DIR__ . '/includes/db.php';
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT shift_id FROM tbl_shifts WHERE cashier_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([currentUserId()]);
        $hasOpenShift = (bool)$stmt->fetch();
    } catch (\Throwable $e) {
        $hasOpenShift = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Monitoring - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=41">
<style>
/* Screen adjustments */
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;}
  .page-header .btn{width:100%;}
  .d-flex.gap-2,.d-flex.gap-3{flex-wrap:wrap!important;}
  .row > [class*='col-md-'],[class*='col-lg-']{width:100%!important;}
  .stat-value{font-size:1.15rem!important;}
}

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
        font-size: 10pt !important;
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
    #statCardsRow,
    #alertsRow,
    .no-print,
    .toast-container {
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
        font-size: 9pt !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
    }

    .table-report td {
        border: 1px solid #cbd5e1 !important;
        padding: 6px 10px !important;
        font-size: 9pt !important;
        color: #1e293b !important;
    }

    .table-report tbody tr:nth-child(even) {
        background-color: #f8fafc !important;
    }

    .badge {
        border: 1px solid #94a3b8 !important;
        background: transparent !important;
        color: #0f172a !important;
        font-weight: 700 !important;
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

        <!-- ══ SHIFT LOCKED CONTAINER (For Cashiers without open shift) ══ -->
        <div id="shiftLockContainer" style="<?= ($currentRole === 'cashier' && !$hasOpenShift) ? 'display:flex;' : 'display:none;' ?> min-height: calc(100vh - 140px); align-items:center; justify-content:center; padding: 24px;">
            <div class="card border-0 shadow text-center p-4 p-md-5" style="max-width: 440px; width: 100%; border-radius: 18px; background: #ffffff;">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width: 76px; height: 76px; border-radius: 50%; background: #fee2e2; color: #ef4444; font-size: 32px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.18);">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2" style="font-size: 1.4rem;">Stock Monitoring Locked</h3>
                <p class="text-muted mb-4" style="font-size: 0.92rem; line-height: 1.55;">
                    Your shift has not been started or has ended.<br>
                    Manager authorization is required to access inventory and stock levels.
                </p>
                <div class="d-flex flex-column gap-2 w-100">
                    <button type="button" class="btn btn-primary btn-lg fw-bold w-100 py-2 d-flex align-items-center justify-content-center gap-2"
                            onclick="openStartShiftModal()" style="border-radius: 10px;">
                        <i class="fa-solid fa-key"></i>
                        <span>Start Shift (Manager)</span>
                    </button>
                    <a href="/sharonstore3.0/pos.php" class="btn btn-outline-secondary fw-bold w-100 py-2 d-flex align-items-center justify-content-center gap-2" style="border-radius: 10px;">
                        <i class="fa-solid fa-cash-register"></i>
                        <span>Go to Point of Sale</span>
                    </a>
                    <button type="button" class="btn btn-outline-danger fw-bold w-100 py-2 mt-1" data-bs-toggle="modal" data-bs-target="#logoutModal" style="border-radius: 10px;">
                        <i class="fa-solid fa-right-from-bracket me-2"></i>Sign Out
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ UNLOCKED STOCK MONITORING CONTENT ══ -->
        <div id="stockMonitorContent" style="<?= ($currentRole === 'cashier' && !$hasOpenShift) ? 'display:none;' : '' ?>">

        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 pt-4">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Stock Monitoring</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-magnifying-glass-chart me-2 text-emerald"></i>Stock Monitoring</h1>
                <p class="page-header-subtitle">View-only stock overview — current levels, low stock alerts, and expiring items.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" onclick="loadAll()"><i class="fa-solid fa-rotate-right me-1"></i>Refresh</button>
                <button class="btn btn-secondary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print Report</button>
            </div>
        </div>

        <!-- PRINT-ONLY OFFICIAL DOCUMENT HEADER -->
        <div class="print-only-block print-header">
            <div class="print-store-title">Sharon Store</div>
            <div class="print-store-sub">Pampang Public Market, Angeles City, Pampanga</div>
            <div class="print-store-sub">Point of Sale &amp; Inventory Management System</div>
            <div class="print-report-title">Official Stock Inventory Report</div>
            
            <table class="print-meta-grid mt-3">
                <tr>
                    <td style="width:50%; text-align:left;">
                        <strong>Date &amp; Time Generated:</strong> <?= date('F d, Y h:i A') ?><br>
                        <strong>Report Scope:</strong> Full Stock &amp; Inventory Level Overview
                    </td>
                    <td style="width:50%; text-align:right;">
                        <strong>Generated By:</strong> <?= htmlspecialchars($currentUser) ?> (<?= ucfirst(htmlspecialchars($currentRole)) ?>)<br>
                        <strong>Classification:</strong> Inventory Audit Document
                    </td>
                </tr>
            </table>

            <!-- Mini KPI Summary Strip -->
            <div class="print-kpi-strip">
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Total Products</span>
                    <span class="print-kpi-val" id="pTotal">0</span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">In Stock</span>
                    <span class="print-kpi-val" id="pOk">0</span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Low Stock</span>
                    <span class="print-kpi-val" id="pLow">0</span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Out of Stock</span>
                    <span class="print-kpi-val" id="pOut">0</span>
                </div>
            </div>
        </div>

        <!-- Stat Cards Row (Screen Only) -->
        <div class="row g-3 mb-4" id="statCardsRow">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon emerald"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="stat-value" id="sTotal"></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-value text-success" id="sOk"></div>
                    <div class="stat-label">In Stock</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-value text-warning" id="sLow"></div>
                    <div class="stat-label">Low Stock</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-ban"></i></div>
                    <div class="stat-value text-danger" id="sOut"></div>
                    <div class="stat-label">Out of Stock</div>
                </div>
            </div>
        </div>

        <!-- Critical Alerts (Screen Only) -->
        <div class="row g-3 mb-4" id="alertsRow">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Low / Out of Stock</h5>
                        <span class="badge badge-warning" id="lowCount">0</span>
                    </div>
                    <div class="card-body p-0" id="lowStockList">
                        <div class="chart-loading p-4"><div class="loading-spinner"></div></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fa-solid fa-calendar-xmark me-2 text-danger"></i>Expiring Soon (30 days)</h5>
                        <span class="badge badge-danger" id="expCount">0</span>
                    </div>
                    <div class="card-body p-0" id="expiringList">
                        <div class="chart-loading p-4"><div class="loading-spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Inventory Table -->
        <div class="card" id="stockCard">
            <div class="card-header d-flex justify-content-between align-items-center no-print">
                <h5><i class="fa-solid fa-table me-2 text-emerald"></i>Full Stock List</h5>
                <div class="d-flex gap-2 flex-nowrap">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search..." style="width:180px;">
                    <select id="catFilter" class="form-select form-select-sm" style="width:140px;"></select>
                    <select id="statusFilterStock" class="form-select form-select-sm" style="max-width:160px;">
                        <option value="">All Status</option>
                        <option value="low">Low Stock</option>
                        <option value="out">Out of Stock</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-report mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center sortable-th"># <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="sortable-th">Item Name <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="sortable-th">Category <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-center sortable-th" style="width: 80px;">Unit <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-center sortable-th" style="width: 100px;">Stock <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-center sortable-th" style="width: 90px;">Threshold <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="sortable-th" style="width: 120px;">Expiry <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-center sortable-th" style="width: 110px;">Status <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                            </tr>
                        </thead>
                        <tbody id="stockBody">
                            <tr><td colspan="8" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading</td></tr>
                        </tbody>
                    </table>
                </div>
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

        </div><!-- /#stockMonitorContent -->

    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js?v=35"></script>
<?php require_once __DIR__ . '/includes/shift_modals.php'; ?>
<script>

async function loadAll() { await Promise.all([loadStats(), loadLowStock(), loadExpiring(), loadFullList()]); }

async function loadStats() {
    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=stats');
        const j = await r.json();
        if (!j.success) {
            if (j.message && j.message.includes('Shift not started')) {
                hasOpenShift = false;
                document.getElementById('shiftLockContainer').style.display = 'flex';
                document.getElementById('stockMonitorContent').style.display = 'none';
            }
            return;
        }
        const total = parseInt(j.data.total_items) || 0;
        const low = parseInt(j.data.low_stock) || 0;
        const out = parseInt(j.data.out_of_stock) || 0;
        const ok = total - low - out;

        document.getElementById('sTotal').textContent = total;
        document.getElementById('sOk').textContent    = ok;
        document.getElementById('sLow').textContent   = low;
        document.getElementById('sOut').textContent   = out;

        // Also update print KPI strip
        document.getElementById('pTotal').textContent = total;
        document.getElementById('pOk').textContent    = ok;
        document.getElementById('pLow').textContent   = low;
        document.getElementById('pOut').textContent   = out;
    } catch (err) {
        console.error('loadStats error:', err);
        showToast('Failed to load stats. Please refresh.', 'danger');
    }
}

async function loadLowStock() {
    const el = document.getElementById('lowStockList');
    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=low_stock');
        const j = await r.json();
        if (!j.success) {
            el.innerHTML = '<div class="p-3 text-muted text-center">No data available.</div>';
            return;
        }
        const items = (j.data && j.data.items) ? j.data.items : (Array.isArray(j.data) ? j.data : []);
        document.getElementById('lowCount').textContent = items.length;
        if (!items.length) {
            el.innerHTML = '<div class="empty-state p-4 text-center"><i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i><p class="text-muted mb-0">All stock levels are healthy.</p></div>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th class="sortable-th">Item <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Stock <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Status <i class="fa-solid fa-sort sort-icon no-print"></i></th></tr></thead><tbody>';
        items.forEach(row => {
            const isOut = parseFloat(row.stock_qty) <= 0;
            html += `<tr class="${isOut ? 'table-row-danger' : 'table-row-warning'}">
                <td><div style="font-weight:600;">${esc(row.item_name)}</div><div class="text-muted" style="font-size:0.75rem;">${esc(row.category_name)}</div></td>
                <td><span style="font-weight:700;">${parseFloat(row.stock_qty)}</span> <span class="text-muted" style="font-size:0.75rem;">${row.unit}</span></td>
                <td><span class="badge ${isOut ? 'badge-danger' : 'badge-warning'}">${isOut ? 'Out' : 'Low'}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        el.innerHTML = html;
    } catch(err) {
        console.error('loadLowStock:', err);
        el.innerHTML = '<div class="p-3 text-danger text-center">Failed to load low stock items.</div>';
    }
}

async function loadExpiring() {
    const el = document.getElementById('expiringList');
    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=expiring_soon');
        const j = await r.json();
        if (!j.success) {
            el.innerHTML = '<div class="p-3 text-muted text-center">No data available.</div>';
            return;
        }
        const expiring = (j.data && j.data.items) ? j.data.items : (Array.isArray(j.data) ? j.data : []);
        const today    = new Date();

        document.getElementById('expCount').textContent = expiring.length;
        if (!expiring.length) {
            el.innerHTML = '<div class="empty-state p-4 text-center"><i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i><p class="text-muted mb-0">No items expiring within 30 days.</p></div>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th class="sortable-th">Item <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Expiry <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Days Left <i class="fa-solid fa-sort sort-icon no-print"></i></th></tr></thead><tbody>';
        expiring.forEach(row => {
            if (!row.expiry_date) return;
            const [ey,em,ed] = row.expiry_date.split('-').map(Number);
            const expDate = new Date(ey, em-1, ed);
            const days = Math.ceil((expDate - today) / 86400000);
            const cls  = days<=0?'text-danger':days<=7?'text-warning':'text-info';
            html += `<tr class="${days<=0?'table-row-danger':days<=7?'table-row-warning':''}"><td style="font-weight:600;">${esc(row.item_name)}</td><td style="color:#555657;">${row.expiry_date}</td><td><span class="${cls}" style="font-weight:700;">${days<=0?'EXPIRED':days+' days'}</span></td></tr>`;
        });
        html += '</tbody></table></div>';
        el.innerHTML = html;
    } catch(err) {
        console.error('loadExpiring:', err);
        el.innerHTML = '<div class="p-3 text-danger text-center">Failed to load expiring items.</div>';
    }
}

let allItems = [];
async function loadFullList() {
    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=list&limit=500');
        const j = await r.json();
        if (!j.success) return;

        // Populate category filter
        const cats = [...new Set(j.data.items.map(i=>i.category_name))].sort();
        const catSel = document.getElementById('catFilter');
        catSel.innerHTML = '<option value="">All Categories</option>' + cats.map(c=>`<option value="${c}">${esc(c)}</option>`).join('');

        allItems = j.data.items;
        renderList(allItems);
    } catch (err) {
        console.error('loadFullList error:', err);
        showToast('Failed to load stock list. Please refresh.', 'danger');
        document.getElementById('stockBody').innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Failed to load data.</td></tr>`;
    }
}

function renderList(items) {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const cat    = document.getElementById('catFilter').value;
    const status = document.getElementById('statusFilterStock').value;
    const filtered = items.filter(i =>
        (!q || i.item_name.toLowerCase().includes(q) || (i.barcode||'').includes(q)) &&
        (!cat || i.category_name === cat) &&
        (!status || i.stock_status === status)
    );

    let html = '';
    filtered.forEach((item, index) => {
        const rowCls  = item.stock_status==='expired'?'table-row-danger':item.stock_status==='out'?'table-row-danger':item.stock_status==='low'?'table-row-warning':'';
        const sBadge  = item.stock_status==='expired'?'<span class="badge badge-danger">Expired</span>':item.stock_status==='out'?'<span class="badge badge-danger">Out</span>':item.stock_status==='low'?'<span class="badge badge-warning">Low</span>':'<span class="badge badge-success">OK</span>';
        const expHtml = item.expiry_date ? (() => {
            const [ey, em, ed] = item.expiry_date.split('-').map(Number);
            const expDate = new Date(ey, em - 1, ed);
            const days = Math.ceil((expDate - new Date()) / 86400000);
            return `<span class="${days<=0?'text-danger fw-700':days<=30?'text-warning':'text-muted'}">${item.expiry_date}${days<=30?' ('+days+'d)':''}</span>`;
        })() : '<span class="text-muted">—</span>';
        
        html += `<tr class="${rowCls}">
            <td class="text-center text-muted fw-bold" data-sort-value="${index + 1}">${index + 1}</td>
            <td class="fw-600" data-sort-value="${esc(item.item_name)}">${esc(item.item_name)}</td>
            <td data-sort-value="${esc(item.category_name)}"><span class="badge badge-secondary">${esc(item.category_name)}</span></td>
            <td class="text-center" data-sort-value="${esc(item.unit)}">${item.unit}</td>
            <td class="text-center fw-700" data-sort-value="${parseFloat(item.stock_qty)}">${parseFloat(item.stock_qty)}</td>
            <td class="text-center text-muted" data-sort-value="${parseFloat(item.low_stock_threshold)}">${item.low_stock_threshold}</td>
            <td data-sort-value="${item.expiry_date || ''}">${expHtml}</td>
            <td class="text-center" data-sort-value="${item.stock_status}">${sBadge}</td>
        </tr>`;
    });
    document.getElementById('stockBody').innerHTML = html || `<tr><td colspan="8" class="text-center py-4 text-muted">No items match.</td></tr>`;
}

let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer=setTimeout(()=>renderList(allItems),300); });
document.getElementById('searchInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') { clearTimeout(searchTimer); renderList(allItems); } });
document.getElementById('catFilter').addEventListener('change', () => renderList(allItems));
document.getElementById('statusFilterStock').addEventListener('change', () => renderList(allItems));

if (!isCashierRole || hasOpenShift) {
    loadAll();
}
</script>
</body>
</html>
