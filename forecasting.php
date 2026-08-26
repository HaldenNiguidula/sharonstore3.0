<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Forecasting — Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=14">
    <?php
    require_once __DIR__ . '/includes/auth_check.php';
    requireAdmin();
    $pageTitle = 'Sales Forecasting';
    ?>
<style>
@media(max-width:991px){
  .row > .col-md-4,.row > .col-md-8,.row > .col-lg-4,.row > .col-lg-8{width:100%!important;}
}
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;}
  .d-flex.gap-2,.d-flex.gap-3{flex-wrap:wrap!important;}
  .modal-dialog{margin:8px!important;max-width:calc(100vw - 16px)!important;}
  .chart-container canvas{max-height:200px!important;}
  .row.g-3 > [class*='col-md-']{width:100%!important;}
}
@media(max-width:575px){
  .stat-value{font-size:1.15rem!important;}
}
</style>
</head>
<body>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-header">
            <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Forecasting</li></ul>
            <h1 class="page-header-title"><i class="fa-solid fa-chart-line me-2 text-emerald"></i>Sales Forecasting</h1>
            <p class="page-header-subtitle">Predict future demand using Simple &amp; Weighted Moving Averages to optimize wholesale purchasing.</p>
        </div>

        <!-- Forecast Controls -->
        <div class="card mb-4">
            <div class="card-header"><h5><i class="fa-solid fa-sliders me-2 text-emerald"></i>Generate Forecast</h5></div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Select Product</label>
                        <select id="itemSelect" class="form-select">
                            <option value="">— Select a product —</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Lookback Period</label>
                        <select id="periodsSelect" class="form-select">
                            <option value="3">3 Months</option>
                            <option value="6" selected>6 Months</option>
                            <option value="12">12 Months</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <button class="btn btn-primary w-100" id="btnForecast">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Forecast
                        </button>
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn btn-secondary w-100" onclick="window.print()">
                            <i class="fa-solid fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results (hidden until forecast generated) -->
        <div id="forecastResults" style="display:none;">

            <!-- Method Cards -->
            <div class="row g-3 mb-4" id="methodCards">
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center gap-2">
                            <span class="badge" style="background:#3b82f6; font-size:0.75rem;">SMA</span>
                            <h5 class="mb-0">Simple Moving Average</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Calculates the arithmetic mean of all selected past months. Equal weight given to each period.</p>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="stat-card p-3" style="animation:none;">
                                        <div class="stat-label">Predicted Qty (Next Month)</div>
                                        <div class="stat-value text-primary" id="smaForecastVal">—</div>
                                        <div class="stat-label" id="smaUnit"></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card p-3" style="animation:none;">
                                        <div class="stat-label">Recommended Order</div>
                                        <div class="stat-value" id="smaRestockVal" style="color:#f59e0b;">—</div>
                                        <div class="stat-label">units to order</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center gap-2">
                            <span class="badge badge-emerald" style="font-size:0.75rem;">WMA</span>
                            <h5 class="mb-0">Weighted Moving Average</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Assigns higher weights to recent months. More responsive to recent sales trends and demand shifts.</p>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="stat-card p-3" style="animation:none;">
                                        <div class="stat-label">Predicted Qty (Next Month)</div>
                                        <div class="stat-value text-emerald" id="wmaForecastVal">—</div>
                                        <div class="stat-label" id="wmaUnit"></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card p-3" style="animation:none;">
                                        <div class="stat-label">Recommended Order</div>
                                        <div class="stat-value" id="wmaRestockVal" style="color:#f59e0b;">—</div>
                                        <div class="stat-label">units to order</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fa-solid fa-chart-area me-2 text-emerald"></i>Historical Sales vs. Forecast — <span id="chartItemName"></span></h5>
                </div>
                <div class="card-body">
                    <canvas id="forecastChart" height="80"></canvas>
                </div>
            </div>

            <!-- Historical Table -->
            <div class="card mb-4">
                <div class="card-header"><h5><i class="fa-solid fa-table me-2 text-emerald"></i>Monthly Data Table</h5></div>
                <div class="card-body p-0">
                    <div style="overflow-x:auto;">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-center">Actual Qty Sold</th>
                                    <th class="text-center" style="color:#3b82f6;">SMA Prediction</th>
                                    <th class="text-center" style="color:#10b981;">WMA Prediction</th>
                                </tr>
                            </thead>
                            <tbody id="forecastTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fa-solid fa-list-check me-2 text-emerald"></i>Forecast Overview — All Products</h5>
                <button class="btn btn-sm btn-secondary" onclick="loadOverview()"><i class="fa-solid fa-rotate-right me-1"></i>Refresh</button>
            </div>
            <div class="card-body p-0" id="overviewContainer">
                <div class="chart-loading p-4"><div class="loading-spinner"></div></div>
            </div>
        </div>

    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
let forecastChart;

// ── Load item dropdown ────────────────────────────────────────
async function loadItems() {
    const r = await fetch('/sharonstore3.0/api/forecasting.php?action=items_list');
    const j = await r.json();
    if (!j.success) return;
    const sel = document.getElementById('itemSelect');
    sel.innerHTML += `<option value="all" style="font-weight:700;color:#0abf8a;">★★ FORECAST ALL PRODUCTS ★★</option>`;
    j.data.forEach(i => {
        sel.innerHTML += `<option value="${i.item_id}" data-stock="${i.stock_qty}" data-unit="${esc(i.unit)}">${esc(i.item_name)} (Stock: ${i.stock_qty} ${i.unit})</option>`;
    });
}

// ── Generate Forecast ─────────────────────────────────────────
document.getElementById('btnForecast').addEventListener('click', async () => {
    const itemId  = document.getElementById('itemSelect').value;
    const periods = document.getElementById('periodsSelect').value;
    if (!itemId) { showToast('Please select a product.', 'warning'); return; }

    const btn = document.getElementById('btnForecast');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Calculating…';

    if (itemId === 'all') {
        const r = await fetch(`/sharonstore3.0/api/forecasting.php?action=forecast_all&periods=${periods}`);
        const j = await r.json();
        
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Forecast';
        
        if (!j.success) { showToast(j.message, 'error'); return; }
        
        document.getElementById('forecastResults').style.display = 'none';
        showToast(j.message, 'success');
        loadOverview();
        return;
    }

    const r = await fetch(`/sharonstore3.0/api/forecasting.php?action=forecast&item_id=${itemId}&periods=${periods}`);
    const j = await r.json();

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Forecast';

    if (!j.success) { showToast(j.message, 'error'); return; }

    const d = j.data;
    document.getElementById('forecastResults').style.display = '';

    // SMA Card
    document.getElementById('smaForecastVal').textContent = d.sma_forecast + ' ' + d.unit;
    document.getElementById('smaUnit').textContent        = 'Next: ' + d.next_period;
    document.getElementById('smaRestockVal').textContent  = d.sma_restock > 0 ? d.sma_restock + ' ' + d.unit : '✓ Adequate';
    document.getElementById('smaRestockVal').style.color  = d.sma_restock > 0 ? '#f59e0b' : '#10b981';

    // WMA Card
    document.getElementById('wmaForecastVal').textContent = d.wma_forecast + ' ' + d.unit;
    document.getElementById('wmaUnit').textContent        = 'Next: ' + d.next_period;
    document.getElementById('wmaRestockVal').textContent  = d.wma_restock > 0 ? d.wma_restock + ' ' + d.unit : '✓ Adequate';
    document.getElementById('wmaRestockVal').style.color  = d.wma_restock > 0 ? '#f59e0b' : '#10b981';

    document.getElementById('chartItemName').textContent  = d.item_name;

    // Chart
    const labels    = [...d.history.map(h => h.month_label), 'Next Month (' + d.next_period + ')'];
    const actuals   = [...d.qtys, null];
    const smaLine   = [...d.qtys.map(() => d.sma_forecast), d.sma_forecast];
    const wmaLine   = [...d.qtys.map(() => d.wma_forecast), d.wma_forecast];

    if (forecastChart) forecastChart.destroy();
    const ctx = document.getElementById('forecastChart').getContext('2d');
    forecastChart = new Chart(ctx, {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar', label: 'Actual Qty Sold',
                    data: actuals, backgroundColor: 'rgba(16,185,129,0.25)',
                    borderColor: '#10b981', borderWidth: 1, borderRadius: 4,
                },
                {
                    type: 'line', label: 'SMA Forecast',
                    data: smaLine, borderColor: '#3b82f6', borderWidth: 2,
                    borderDash: [6,3], fill: false, tension: 0,
                    pointBackgroundColor: '#3b82f6', pointRadius: 5,
                },
                {
                    type: 'line', label: 'WMA Forecast',
                    data: wmaLine, borderColor: '#10b981', borderWidth: 2,
                    borderDash: [0], fill: false, tension: 0,
                    pointBackgroundColor: '#10b981', pointRadius: 5,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: '#94a3b8', font: {size:12} } } },
            scales: {
                x: { grid: {color:'rgba(255,255,255,0.04)'} },
                y: { grid: {color:'rgba(255,255,255,0.04)'}, ticks: {precision:0} },
            },
        },
    });

    // Table
    let tbody = '';
    d.history.forEach(h => {
        tbody += `<tr>
            <td class="fw-600">${esc(h.month_label)}</td>
            <td class="text-center fw-700">${parseFloat(h.qty_sold).toFixed(2)}</td>
            <td class="text-center" style="color:#3b82f6;">${d.sma_forecast}</td>
            <td class="text-center" style="color:#10b981;">${d.wma_forecast}</td>
        </tr>`;
    });
    tbody += `<tr style="background:rgba(16,185,129,0.08); font-weight:800;">
        <td>Next Month (${esc(d.next_period)})</td>
        <td class="text-center text-muted">—</td>
        <td class="text-center" style="color:#3b82f6;">${d.sma_forecast}</td>
        <td class="text-center" style="color:#10b981;">${d.wma_forecast}</td>
    </tr>`;
    document.getElementById('forecastTableBody').innerHTML = tbody;

    showToast('Forecast generated successfully!', 'success');
    loadOverview();
});

// ── Overview Table ────────────────────────────────────────────
async function loadOverview() {
    const container = document.getElementById('overviewContainer');
    container.innerHTML = '<div class="chart-loading p-4"><div class="loading-spinner"></div></div>';
    try {
        const r = await fetch('/sharonstore3.0/api/forecasting.php?action=forecast_report');
        const j = await r.json();
        if (!j.success || !j.data.length) {
            container.innerHTML = '<div class="empty-state p-5"><i class="fa-solid fa-chart-simple fa-2x text-muted mb-3"></i><p class="text-muted">No forecasts generated yet. Select a product above and click Forecast.</p></div>';
            return;
        }
        let totalItems = j.data.length;
        let needsRestockCount = 0;
        let totalRestockQty = 0;

        j.data.forEach(row => {
            if (row.needs_restock) {
                needsRestockCount++;
                totalRestockQty += (row.sma_restock > 0 ? row.sma_restock : row.wma_restock);
            }
        });

        let html = `
            <div class="row g-3 mb-3 p-3 pb-0" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; margin:0;">
                <div class="col-md-4">
                    <div class="card h-100" style="border:none;box-shadow:0 2px 8px rgba(0,0,0,0.04);border-left:4px solid #10b981;">
                        <div class="card-body p-3">
                            <h6 class="text-muted mb-1" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Total Forecasted</h6>
                            <h3 class="mb-0" style="font-weight:800;color:#2C2D2D;">${totalItems} <small class="text-muted" style="font-size:0.8rem;">Items</small></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100" style="border:none;box-shadow:0 2px 8px rgba(0,0,0,0.04);border-left:4px solid ${needsRestockCount > 0 ? '#f59e0b' : '#10b981'};">
                        <div class="card-body p-3">
                            <h6 class="text-muted mb-1" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Needs Restock</h6>
                            <h3 class="mb-0" style="font-weight:800;color:${needsRestockCount > 0 ? '#f59e0b' : '#2C2D2D'};">${needsRestockCount} <small class="text-muted" style="font-size:0.8rem;">Items</small></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100" style="border:none;box-shadow:0 2px 8px rgba(0,0,0,0.04);border-left:4px solid #3b82f6;">
                        <div class="card-body p-3">
                            <h6 class="text-muted mb-1" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Est. Units to Order</h6>
                            <h3 class="mb-0" style="font-weight:800;color:#3b82f6;">${totalRestockQty} <small class="text-muted" style="font-size:0.8rem;">Units</small></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive"><table class="table table-custom mb-0">
            <thead><tr>
                <th>Product</th><th>Category</th><th class="text-center">Current Stock</th>
                <th class="text-center" style="color:#3b82f6;">SMA Forecast</th>
                <th class="text-center" style="color:#10b981;">WMA Forecast</th>
                <th class="text-center" style="color:#f59e0b;">SMA Order</th>
                <th class="text-center" style="color:#f59e0b;">WMA Order</th>
                <th class="text-center">Status</th>
            </tr></thead><tbody>`;
            
        j.data.forEach(row => {
            const needsRestock = row.needs_restock;
            const badge = needsRestock
                ? '<span class="badge badge-warning">Restock Needed</span>'
                : (row.sma_forecast ? '<span class="badge badge-success">Adequate</span>' : '<span class="badge badge-secondary">No Data</span>');
            html += `<tr class="${needsRestock?'table-row-warning':''}">
                <td class="fw-600">${esc(row.item_name)}</td>
                <td class="text-muted">${esc(row.category_name)}</td>
                <td class="text-center">${parseFloat(row.stock_qty).toFixed(2)} ${esc(row.unit)}</td>
                <td class="text-center" style="color:#3b82f6;">${row.sma_forecast ? parseFloat(row.sma_forecast).toFixed(2) : '—'}</td>
                <td class="text-center" style="color:#10b981;">${row.wma_forecast ? parseFloat(row.wma_forecast).toFixed(2) : '—'}</td>
                <td class="text-center fw-700" style="color:#f59e0b;">${row.sma_restock > 0 ? row.sma_restock : '—'}</td>
                <td class="text-center fw-700" style="color:#f59e0b;">${row.wma_restock > 0 ? row.wma_restock : '—'}</td>
                <td class="text-center">${badge}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (err) {
        console.error('loadOverview error:', err);
        showToast('Failed to load forecast overview.', 'error');
        container.innerHTML = '<div class="empty-state p-5"><i class="fa-solid fa-triangle-exclamation fa-2x text-danger mb-3"></i><p class="text-muted">Failed to load overview. Please try again.</p></div>';
    }
}


loadItems();
loadOverview();

// ── Enter key shortcut on filter-area controls ────────────────
(function attachFilterEnterKey() {
    const filterControls = ['itemSelect', 'periodsSelect'];
    filterControls.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('btnForecast').click();
            }
        });
    });
    // Also cover any text inputs added to the filter card in future
    document.querySelectorAll('.card .card-body input[type="text"], .card .card-body input[type="search"]')
        .forEach(inp => {
            inp.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('btnForecast').click();
                }
            });
        });
}());
</script>
</body>
</html>
