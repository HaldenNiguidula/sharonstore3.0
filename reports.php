<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();

$pageTitle = 'Sales Reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=17">
<style>
.report-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.report-tab {
    flex: 1;
    text-align: center;
    padding: 12px;
    background: var(--surface);
    border: 1px solid var(--bd);
    border-radius: var(--r);
    font-weight: 700;
    color: var(--txt-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.report-tab.active {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
    box-shadow: var(--sh-sm);
}
.report-tab:hover:not(.active) {
    background: var(--surface-2);
}
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
.clickable-period {
    color: var(--accent);
    text-decoration: none;
    cursor: pointer;
}
.clickable-period:hover {
    text-decoration: underline;
}
.sortable-th {
    cursor: pointer;
    user-select: none;
}
.sortable-th:hover {
    background-color: var(--surface-2);
}
.sort-icon {
    font-size: 0.8em;
    margin-left: 5px;
    color: var(--txt-muted);
}
@media print {
    body { background: white !important; }
    .ss-sidebar, .ss-topbar, .report-tabs, .btn, .breadcrumb-custom { display: none !important; }
    .app-wrapper { display: block !important; }
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .page-header { padding-top: 0 !important; margin-bottom: 10px !important; }
    #printableArea { display: block !important; width: 100%; }
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
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Sales Reports</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-chart-line me-2 text-emerald"></i>Sales Reports</h1>
                <p class="page-header-subtitle">Analyze your daily, weekly, and monthly revenue trends.</p>
            </div>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print me-2"></i>Print Report
            </button>
        </div>

        <div class="report-tabs">
            <div class="report-tab active" onclick="loadReport('daily', this)">Daily Sales</div>
            <div class="report-tab" onclick="loadReport('weekly', this)">Weekly Sales</div>
            <div class="report-tab" onclick="loadReport('monthly', this)">Monthly Sales</div>
        </div>

        <div id="printableArea">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0" id="chartTitle">Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Data Breakdown</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-end">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr><td colspan="3" class="text-center py-4">Loading data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Detailed Item Breakdown Modal -->
<div class="modal fade" id="breakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold">Items Sold: <span id="bdModalTitle" class="text-emerald"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="sortable-th" onclick="sortBreakdown('item_name')">Item Name <i class="fa-solid fa-sort sort-icon" id="sort-icon-item_name"></i></th>
                                <th class="sortable-th text-center" onclick="sortBreakdown('qty_sold')">Quantity Sold <i class="fa-solid fa-sort sort-icon" id="sort-icon-qty_sold"></i></th>
                                <th class="sortable-th text-end" onclick="sortBreakdown('total_revenue')">Total Revenue <i class="fa-solid fa-sort sort-icon" id="sort-icon-total_revenue"></i></th>
                            </tr>
                        </thead>
                        <tbody id="bdTableBody">
                            <tr><td colspan="3" class="text-center py-4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
<script>
let salesChart = null;
let currentReportType = 'daily';
let breakdownData = [];
let sortCol = 'total_revenue';
let sortDesc = true;
const bdModal = new bootstrap.Modal(document.getElementById('breakdownModal'));

async function loadReport(type, tabEl = null) {
    currentReportType = type;
    if (tabEl) {
        document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
        tabEl.classList.add('active');
    }

    const titleMap = {
        'daily': 'Daily Revenue (Last 30 Days)',
        'weekly': 'Weekly Revenue (Last 12 Weeks)',
        'monthly': 'Monthly Revenue (Last 12 Months)'
    };
    document.getElementById('chartTitle').textContent = titleMap[type] || 'Revenue Trend';
    
    document.getElementById('tableBody').innerHTML = '<tr><td colspan="3" class="text-center py-4"><span class="spinner-border spinner-border-sm text-emerald"></span></td></tr>';

    try {
        const res = await fetch('/sharonstore3.0/api/reports.php?action=sales_report&type=' + type);
        const json = await res.json();
        
        if (!json.success) {
            showToast(json.message, 'error');
            return;
        }
        
        const d = json.data;
        
        // Update Chart
        if (salesChart) salesChart.destroy();
        const ctx = document.getElementById('salesChart').getContext('2d');
        salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: d.labels,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: d.totals,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Update Table
        let tbody = '';
        if (d.tableData.length === 0) {
            tbody = '<tr><td colspan="3" class="text-center py-4 text-muted">No sales data found for this period.</td></tr>';
        } else {
            d.tableData.forEach(row => {
                const safePeriod = esc(row.period);
                const safeRawDate = esc(row.raw_date);
                tbody += `<tr>
                    <td class="fw-600">
                        <a class="clickable-period" onclick="openBreakdown('${safeRawDate}', '${safePeriod}')">
                            <i class="fa-solid fa-magnifying-glass-chart me-1"></i> ${safePeriod}
                        </a>
                    </td>
                    <td class="text-center">${row.tx_count}</td>
                    <td class="text-end fw-700" style="color:var(--accent);">₱${parseFloat(row.revenue).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                </tr>`;
            });
        }
        document.getElementById('tableBody').innerHTML = tbody;

    } catch (e) {
        showToast('Failed to load report data.', 'error');
        document.getElementById('tableBody').innerHTML = '<tr><td colspan="3" class="text-center py-4 text-danger">Error loading data.</td></tr>';
    }
}

async function openBreakdown(rawDate, periodLabel) {
    document.getElementById('bdModalTitle').textContent = periodLabel;
    document.getElementById('bdTableBody').innerHTML = '<tr><td colspan="3" class="text-center py-4"><span class="spinner-border text-emerald"></span></td></tr>';
    bdModal.show();
    
    // Reset sort
    sortCol = 'total_revenue';
    sortDesc = true;
    updateSortIcons();

    try {
        const res = await fetch(`/sharonstore3.0/api/reports.php?action=item_breakdown&type=${currentReportType}&raw_date=${encodeURIComponent(rawDate)}`);
        const json = await res.json();
        
        if (json.success) {
            breakdownData = json.data;
            renderBreakdownTable();
        } else {
            document.getElementById('bdTableBody').innerHTML = `<tr><td colspan="3" class="text-center py-4 text-danger">${esc(json.message)}</td></tr>`;
        }
    } catch (err) {
        document.getElementById('bdTableBody').innerHTML = '<tr><td colspan="3" class="text-center py-4 text-danger">Failed to fetch data.</td></tr>';
    }
}

function sortBreakdown(col) {
    if (sortCol === col) {
        sortDesc = !sortDesc;
    } else {
        sortCol = col;
        sortDesc = true;
    }
    updateSortIcons();
    
    breakdownData.sort((a, b) => {
        let valA = a[sortCol];
        let valB = b[sortCol];
        
        if (typeof valA === 'string') valA = valA.toLowerCase();
        if (typeof valB === 'string') valB = valB.toLowerCase();
        
        if (valA < valB) return sortDesc ? 1 : -1;
        if (valA > valB) return sortDesc ? -1 : 1;
        return 0;
    });
    
    renderBreakdownTable();
}

function updateSortIcons() {
    ['item_name', 'qty_sold', 'total_revenue'].forEach(c => {
        const el = document.getElementById('sort-icon-' + c);
        if (c === sortCol) {
            el.className = 'fa-solid sort-icon ' + (sortDesc ? 'fa-sort-down' : 'fa-sort-up');
            el.style.color = 'var(--accent)';
        } else {
            el.className = 'fa-solid fa-sort sort-icon';
            el.style.color = 'var(--txt-muted)';
        }
    });
}

function renderBreakdownTable() {
    if (breakdownData.length === 0) {
        document.getElementById('bdTableBody').innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No items sold in this period.</td></tr>';
        return;
    }
    
    let html = '';
    breakdownData.forEach(row => {
        html += `<tr>
            <td class="fw-600">${esc(row.item_name)}</td>
            <td class="text-center fw-700">${parseFloat(row.qty_sold)}</td>
            <td class="text-end fw-700" style="color:var(--accent);">₱${parseFloat(row.total_revenue).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
        </tr>`;
    });
    document.getElementById('bdTableBody').innerHTML = html;
}

// Initial load
loadReport('daily');
</script>
</body>
</html>
