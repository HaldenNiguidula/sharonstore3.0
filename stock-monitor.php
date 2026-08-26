<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Monitoring — Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=16">
    <?php
    require_once __DIR__ . '/includes/auth_check.php';
    requireLogin();
    $pageTitle = 'Stock Monitoring';
    $isAdmin   = isAdmin();
    ?>
<style>
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;}
  .page-header .btn{width:100%;}
  .d-flex.gap-2,.d-flex.gap-3{flex-wrap:wrap!important;}
  .row > [class*='col-md-'],[class*='col-lg-']{width:100%!important;}
  .stat-value{font-size:1.15rem!important;}
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
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Stock Monitoring</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-magnifying-glass-chart me-2 text-emerald"></i>Stock Monitoring</h1>
                <p class="page-header-subtitle">View-only stock overview — current levels, low stock alerts, and expiring items.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary" onclick="loadAll()"><i class="fa-solid fa-rotate-right me-1"></i>Refresh</button>
                <button class="btn btn-secondary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon emerald"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="stat-value" id="sTotal">—</div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-value text-success" id="sOk">—</div>
                    <div class="stat-label">In Stock</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-value text-warning" id="sLow">—</div>
                    <div class="stat-label">Low Stock</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fa-solid fa-ban"></i></div>
                    <div class="stat-value text-danger" id="sOut">—</div>
                    <div class="stat-label">Out of Stock</div>
                </div>
            </div>
        </div>

        <!-- Critical Alerts -->
        <div class="row g-3 mb-4">
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

        <!-- Full Inventory (view-only) -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fa-solid fa-table me-2 text-emerald"></i>Full Stock List</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search…" style="width:180px;">
                    <select id="catFilter" class="form-select form-select-sm" style="width:140px;"></select>
                    <select id="statusFilterStock" class="form-select form-select-sm" style="max-width:160px;">
                        <option value="">All Status</option>
                        <option value="low">Low Stock</option>
                        <option value="out">Out of Stock</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div style="overflow-x:auto;">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Threshold</th>
                                <th>Expiry</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="stockBody">
                            <tr><td colspan="7" class="text-center py-5"><div class="loading-spinner mx-auto mb-2"></div>Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
async function loadAll() { await Promise.all([loadStats(), loadLowStock(), loadExpiring(), loadFullList()]); }

async function loadStats() {
    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=stats');
        const j = await r.json();
        if (!j.success) return;
        document.getElementById('sTotal').textContent = j.data.total_items;
        document.getElementById('sOk').textContent    = parseInt(j.data.total_items) - parseInt(j.data.low_stock) - parseInt(j.data.out_of_stock);
        document.getElementById('sLow').textContent   = j.data.low_stock;
        document.getElementById('sOut').textContent   = j.data.out_of_stock;
    } catch (err) {
        console.error('loadStats error:', err);
        showToast('Failed to load stats. Please refresh.', 'danger');
    }
}

async function loadLowStock() {
    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=list&status=low&limit=50');
        const j = await r.json();
        const el = document.getElementById('lowStockList');
        const items = (j.data?.items || []).filter(i => i.stock_status === 'low' || i.stock_status === 'out');
        document.getElementById('lowCount').textContent = items.length;
        if (!items.length) {
            el.innerHTML = '<div class="empty-state p-4 text-center"><i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i><p class="text-muted mb-0">All items are sufficiently stocked.</p></div>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th>Item</th><th>Stock</th><th>Status</th></tr></thead><tbody>';
        items.forEach(row => {
            const rowCls = row.stock_status==='out'?'table-row-danger':'table-row-warning';
            const badge  = row.stock_status==='out'?'<span class="badge badge-danger">Out</span>':'<span class="badge badge-warning">Low</span>';
            html += `<tr class="${rowCls}"><td style="font-weight:600;">${esc(row.item_name)}<br><small style="color:#555657;">${esc(row.category_name)}</small></td><td style="font-weight:700;">${row.stock_qty} ${row.unit}</td><td>${badge}</td></tr>`;
        });
        html += '</tbody></table></div>';
        el.innerHTML = html;
    } catch(err) {
        console.error('loadLowStock:', err);
    }
}

async function loadExpiring() {
    try {
        const r = await fetch('/sharonstore3.0/api/inventory.php?action=list&limit=500');
        const j = await r.json();
        const el = document.getElementById('expiringList');
        const today = new Date(); today.setHours(0,0,0,0);
        const in30  = new Date(); in30.setDate(in30.getDate()+30);

        const expiring = (j.data?.items || []).filter(i => {
            if (!i.expiry_date) return false;
            const [ey,em,ed] = i.expiry_date.split('-').map(Number);
            const d = new Date(ey, em-1, ed);
            return d <= in30;
        }).sort((a,b) => a.expiry_date.localeCompare(b.expiry_date));

        document.getElementById('expCount').textContent = expiring.length;
        if (!expiring.length) {
            el.innerHTML = '<div class="empty-state p-4 text-center"><i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i><p class="text-muted mb-0">No items expiring within 30 days.</p></div>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th>Item</th><th>Expiry</th><th>Days Left</th></tr></thead><tbody>';
        expiring.forEach(row => {
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
        document.getElementById('stockBody').innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load data.</td></tr>`;
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
    filtered.forEach(item => {
        const rowCls  = item.stock_status==='out'?'table-row-danger':item.stock_status==='low'?'table-row-warning':'';
        const sBadge  = item.stock_status==='out'?'<span class="badge badge-danger">Out</span>':item.stock_status==='low'?'<span class="badge badge-warning">Low</span>':'<span class="badge badge-success">OK</span>';
        const expHtml = item.expiry_date ? (() => {
            // Safe date parse: treat YYYY-MM-DD as local date, not UTC
            const [ey, em, ed] = item.expiry_date.split('-').map(Number);
            const expDate = new Date(ey, em - 1, ed);
            const days = Math.ceil((expDate - new Date()) / 86400000);
            return `<span class="${days<=0?'text-danger fw-700':days<=30?'text-warning':'text-muted'}">${item.expiry_date}${days<=30?' ('+days+'d)':''}</span>`;
        })() : '<span class="text-muted">—</span>';
        html += `<tr class="${rowCls}">
            <td class="fw-600">${esc(item.item_name)}</td>
            <td><span class="badge badge-secondary">${esc(item.category_name)}</span></td>
            <td>${item.unit}</td>
            <td class="text-center fw-700">${parseFloat(item.stock_qty)}</td>
            <td class="text-center text-muted">${item.low_stock_threshold}</td>
            <td>${expHtml}</td>
            <td class="text-center">${sBadge}</td>
        </tr>`;
    });
    document.getElementById('stockBody').innerHTML = html || `<tr><td colspan="7" class="text-center py-4 text-muted">No items match.</td></tr>`;
}

let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => { clearTimeout(searchTimer); searchTimer=setTimeout(()=>renderList(allItems),300); });
document.getElementById('searchInput').addEventListener('keydown', (e) => { if (e.key === 'Enter') { clearTimeout(searchTimer); renderList(allItems); } });
document.getElementById('catFilter').addEventListener('change', () => renderList(allItems));
document.getElementById('statusFilterStock').addEventListener('change', () => renderList(allItems));



loadAll();
</script>
</body>
</html>
