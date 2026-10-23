<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Business Intelligence Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sharon Store</title>
    <meta name="description" content="Sharon Store Business Intelligence Dashboard  Real-time sales and inventory overview">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=35">
<style>
@media(max-width:767px){
  .page-header{flex-direction:column!important;gap:10px!important;}
  .chart-container canvas{max-height:220px!important;}
  .row.g-4 > .col-md-8, .row.g-4 > .col-md-4{width:100%!important;}
  .row.g-3 > .col-md-6{width:100%!important;}
  .col-lg-8,.col-lg-4{width:100%!important;}
}
@media(max-width:575px){
  .stat-value{font-size:1.15rem!important;}
  .card-body{padding:12px!important;}
}
</style>
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Dashboard</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-chart-pie me-2 text-emerald"></i>Business Intelligence</h1>
                <p class="page-header-subtitle">Real-time overview of Sharon Store's sales performance and inventory health.</p>
            </div>
            <button class="btn btn-outline-primary btn-sm" id="refreshDashboard">
                <i class="fa-solid fa-rotate-right me-1"></i>Refresh
            </button>
        </div>

        <!-- -- ROW 1: Primary Stat Cards -- -->
        <div class="row g-3 mb-4" id="statCards">
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon emerald"><i class="fa-solid fa-peso-sign"></i></div>
                    <div class="stat-value" id="statTodayRevenue"></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-calendar-week"></i></div>
                    <div class="stat-value" id="statMonthlyRevenue"></div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-value" id="statTodayTx"></div>
                    <div class="stat-label">Today's Transactions</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div class="stat-value" id="statTotalItems"></div>
                    <div class="stat-label">Active Products</div>
                </div>
            </div>
        </div>

        <!-- -- ROW 2: Alert Cards -- -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card" style="cursor:pointer;" onclick="window.location='/sharonstore3.0/inventory.php'">
                    <div class="stat-icon amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-value text-warning" id="statLowStock"></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card" style="cursor:pointer;" onclick="window.location='/sharonstore3.0/inventory.php'">
                    <div class="stat-icon red"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="stat-value text-danger" id="statExpiring"></div>
                    <div class="stat-label">Expiring in 30 Days</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon emerald"><i class="fa-solid fa-peso-sign"></i></div>
                    <div class="stat-value" id="statStockValue"></div>
                    <div class="stat-label">Stock Value (Retail)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-key"></i></div>
                    <div class="stat-value font-monospace text-primary" id="statManagerPin"><?= getDailyManagerPIN(getDB()) ?></div>
                    <div class="stat-label">Today's Manager PIN</div>
                </div>
            </div>
        </div>

        <!-- -- ROW 3: Charts -- -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fa-solid fa-chart-line me-2 text-emerald"></i>Sales Trend (Last 30 Days)</h5>
                        <span class="badge badge-emerald">Daily</span>
                    </div>
                    <div class="card-body">
                        <div id="salesTrendLoading" class="chart-loading"><div class="loading-spinner"></div></div>
                        <canvas id="salesTrendChart" height="90" style="display:none; max-height:300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5><i class="fa-solid fa-chart-pie me-2 text-emerald"></i>Sales by Category</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div id="catLoading" class="chart-loading"><div class="loading-spinner"></div></div>
                        <canvas id="categoryChart" style="display:none; max-height:230px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- -- ROW 4: Top Items + Alert Tables -- -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa-solid fa-fire me-2 text-emerald"></i>Top 10 Fast-Moving Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="topItemsLoading" class="chart-loading p-4"><div class="loading-spinner"></div></div>
                        <canvas id="topItemsChart" height="160" style="display:none; padding:16px; max-height:300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Low Stock Alerts</h5>
                        <a href="/sharonstore3.0/inventory.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0" id="lowStockTable">
                        <div class="chart-loading p-4"><div class="loading-spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- -- ROW 5: Expiring Soon -- -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fa-solid fa-calendar-xmark me-2 text-danger"></i>Expiring Soon (Within 30 Days)</h5>
                        <a href="/sharonstore3.0/inventory.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0" id="expiringTable">
                        <div class="chart-loading p-4"><div class="loading-spinner"></div></div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js?v=35"></script>
<script>
// -- Palette --------------------------------------------------
const EMERALD   = '#10b981';
const EMERALD_L = '#34d399';
const NAVY      = '#1e3a5f';
const COLORS    = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#ec4899','#06b6d4','#84cc16','#f97316','#a78bfa'];

Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

let salesChart, catChart, topChart;

// -- Render Stats ----------------------------------------------
function renderStats(d) {
    if (!d) return;
    animateCounter('statTodayRevenue',   parseFloat(d.today_revenue)    || 0, true);
    animateCounter('statMonthlyRevenue', parseFloat(d.monthly_revenue)  || 0, true);
    animateCounter('statTodayTx',        parseInt(d.today_transactions) || 0, false);
    animateCounter('statTotalItems',     parseInt(d.total_items)        || 0, false);
    animateCounter('statLowStock',       parseInt(d.low_stock)          || 0, false);
    animateCounter('statExpiring',       parseInt(d.expiring_soon)      || 0, false);
    animateCounter('statStockValue',     parseFloat(d.total_stock_value)|| 0, true);
}

async function loadStats() {
    try {
        const r = await fetch('/sharonstore3.0/api/dashboard.php?action=stats');
        const j = await r.json();
        if (j.success) renderStats(j.data);
    } catch(e) { console.error('stats error', e); }
}

// -- Counter animation -----------------------------------------
function animateCounter(id, target, isCurrency) {
    const el = document.getElementById(id);
    if (!el) return;
    const dur = 800, step = 16;
    const steps = dur / step;
    let cur = 0, frame = 0;
    const interval = setInterval(() => {
        frame++;
        cur = target * (frame / steps);
        if (frame >= steps) { cur = target; clearInterval(interval); }
        el.textContent = isCurrency ? '\u20b1' + cur.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2}) : Math.round(cur).toLocaleString();
    }, step);
}

// -- Sales Trend Chart -----------------------------------------
function renderSalesTrend(data) {
    if (!data) return;
    const loadingEl = document.getElementById('salesTrendLoading');
    const chartEl = document.getElementById('salesTrendChart');
    if (loadingEl) loadingEl.style.display = 'none';
    if (chartEl) chartEl.style.display = '';
    const ctx = chartEl.getContext('2d');
    if (salesChart) salesChart.destroy();
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Daily Sales (\u20b1)',
                data: data.data,
                borderColor: EMERALD,
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: EMERALD,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { display: false }, tooltip: {
                callbacks: { label: ctx => '\u20b1' + parseFloat(ctx.parsed.y).toLocaleString('en-PH',{minimumFractionDigits:2}) }
            }},
            scales: {
                x: {
                    grid: { color: 'rgba(0,0,0,0.07)' },
                    ticks: { maxTicksLimit: 10, color: '#555657', font: { size: 11 } }
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0.07)' },
                    ticks: {
                        color: '#555657',
                        font: { size: 11 },
                        callback: function(v) { return '\u20b1' + Number(v).toLocaleString('en-PH', {minimumFractionDigits: 0}); }
                    }
                }
            }
        }
    });
}

async function loadSalesTrend() {
    try {
        const r = await fetch('/sharonstore3.0/api/dashboard.php?action=sales_trend');
        const j = await r.json();
        if (j.success) renderSalesTrend(j.data);
    } catch(e) { console.error('trend error', e); }
}

// -- Category Chart --------------------------------------------
function renderCategoryChart(data) {
    if (!data) return;
    const loadingEl = document.getElementById('catLoading');
    const chartEl = document.getElementById('categoryChart');
    if (loadingEl) loadingEl.style.display = 'none';
    if (chartEl) chartEl.style.display = '';
    const ctx = chartEl.getContext('2d');
    if (catChart) catChart.destroy();
    catChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{ data: data.data, backgroundColor: COLORS, borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, color: '#2C2D2D', font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ctx.label + ': \u20b1' + parseFloat(ctx.parsed).toLocaleString('en-PH',{minimumFractionDigits:2}) } }
            }
        }
    });
}

async function loadCategoryChart() {
    try {
        const r = await fetch('/sharonstore3.0/api/dashboard.php?action=category_sales');
        const j = await r.json();
        if (j.success) renderCategoryChart(j.data);
    } catch(e) { console.error('category error', e); }
}

// -- Top Items Chart -------------------------------------------
function renderTopItems(data) {
    if (!data) return;
    const loadingEl = document.getElementById('topItemsLoading');
    const chartEl = document.getElementById('topItemsChart');
    if (loadingEl) loadingEl.style.display = 'none';
    if (chartEl) chartEl.style.display = '';
    const ctx = chartEl.getContext('2d');
    if (topChart) topChart.destroy();
    const labels = (data.labels || []).map(l => l.length > 20 ? l.substring(0,20)+'...' : l);
    topChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Units Sold',
                data: data.data,
                backgroundColor: COLORS.slice(0, (data.data || []).length),
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { color: 'rgba(0,0,0,0.07)' },
                    ticks: { precision: 0, color: '#555657', font: { size: 11 } }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#2C2D2D', font: { size: 11, weight: '600' } }
                }
            }
        }
    });
}

async function loadTopItems() {
    try {
        const r = await fetch('/sharonstore3.0/api/dashboard.php?action=top_items');
        const j = await r.json();
        if (j.success) renderTopItems(j.data);
    } catch(e) { console.error('top items error', e); }
}

// -- Low Stock Table -------------------------------------------
function renderLowStock(data) {
    const el = document.getElementById('lowStockTable');
    if (!data || !data.length) {
        el.innerHTML = '<div class="empty-state p-4"><i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i><p class="text-muted mb-0">All items are sufficiently stocked!</p></div>';
        return;
    }
    let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th class="sortable-th">Item <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Category <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Stock <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Status <i class="fa-solid fa-sort sort-icon no-print"></i></th></tr></thead><tbody>';
    data.forEach(row => {
        const badge = row.status === 'out'
            ? '<span class="badge badge-danger">Out of Stock</span>'
            : '<span class="badge badge-warning">Low Stock</span>';
        const rowCls = row.status === 'out' ? 'table-row-danger' : 'table-row-warning';
        html += `<tr class="${rowCls}">
            <td data-sort-value="${esc(row.item_name)}"><span class="fw-600">${esc(row.item_name)}</span></td>
            <td data-sort-value="${esc(row.category_name)}"><span class="text-muted-sm">${esc(row.category_name)}</span></td>
            <td data-sort-value="${parseFloat(row.stock_qty)}"><span class="fw-700">${parseFloat(row.stock_qty)} ${row.unit}</span></td>
            <td data-sort-value="${row.status}">${badge}</td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    el.innerHTML = html;
}

async function loadLowStock() {
    try {
        const r = await fetch('/sharonstore3.0/api/dashboard.php?action=low_stock');
        const j = await r.json();
        if (j.success) renderLowStock(j.data);
    } catch(e) { console.error('low stock error', e); }
}

// -- Expiring Table --------------------------------------------
function renderExpiring(data) {
    const el = document.getElementById('expiringTable');
    if (!data || !data.length) {
        el.innerHTML = '<div class="empty-state p-4"><i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i><p class="text-muted mb-0">No items expiring within 30 days.</p></div>';
        return;
    }
    let html = '<div class="table-responsive"><table class="table table-custom mb-0"><thead><tr><th class="sortable-th">Item <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Category <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Stock <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Expiry Date <i class="fa-solid fa-sort sort-icon no-print"></i></th><th class="sortable-th">Days Left <i class="fa-solid fa-sort sort-icon no-print"></i></th></tr></thead><tbody>';
    data.forEach(row => {
        const days = parseInt(row.days_left);
        const daysCls = days <= 7 ? 'text-danger fw-700' : days <= 14 ? 'text-warning fw-600' : 'text-info';
        const rowCls  = days <= 0 ? 'table-row-danger' : days <= 7 ? 'table-row-warning' : '';
        html += `<tr class="${rowCls}">
            <td class="fw-600" data-sort-value="${esc(row.item_name)}">${esc(row.item_name)}</td>
            <td class="text-muted-sm" data-sort-value="${esc(row.category_name)}">${esc(row.category_name)}</td>
            <td data-sort-value="${parseFloat(row.stock_qty)}">${parseFloat(row.stock_qty)} ${row.unit}</td>
            <td data-sort-value="${row.expiry_date}">${row.expiry_date}</td>
            <td data-sort-value="${days}"><span class="${daysCls}">${days <= 0 ? 'EXPIRED' : days + ' days'}</span></td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    el.innerHTML = html;
}

async function loadExpiring() {
    try {
        const r = await fetch('/sharonstore3.0/api/dashboard.php?action=expiring_soon');
        const j = await r.json();
        if (j.success) renderExpiring(j.data);
    } catch(e) { console.error('expiring error', e); }
}

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Single-trip fast loader
async function loadAll() {
    try {
        const r = await fetch('/sharonstore3.0/api/dashboard.php?action=overview');
        const j = await r.json();
        if (j.success && j.data) {
            if (j.data.stats) renderStats(j.data.stats);
            if (j.data.sales_trend) renderSalesTrend(j.data.sales_trend);
            if (j.data.category_sales) renderCategoryChart(j.data.category_sales);
            if (j.data.top_items) renderTopItems(j.data.top_items);
            if (j.data.low_stock) renderLowStock(j.data.low_stock);
            if (j.data.expiring_soon) renderExpiring(j.data.expiring_soon);
            return;
        }
    } catch(e) {
        console.warn('Dashboard batch load failed, falling back to individual calls', e);
    }

    // Fallback if needed
    loadStats();
    loadSalesTrend();
    loadCategoryChart();
    loadTopItems();
    loadLowStock();
    loadExpiring();
}

document.getElementById('refreshDashboard').addEventListener('click', () => {
    showToast('Dashboard refreshed.', 'success');
    loadAll();
});

loadAll();
// Auto-refresh every 5 minutes
setInterval(loadAll, 5 * 60 * 1000);
</script>
</body>
</html>
