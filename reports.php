<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();

$pageTitle = 'Sales Reports';
$currentAdminName = currentUserName();
$currentAdminRole = currentRole();
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

/* Screen: Hide print-only elements */
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

    /* Hide website UI chrome and on-screen chart */
    .ss-sidebar,
    .ss-topbar,
    .report-tabs,
    .btn,
    .breadcrumb-custom,
    .page-header,
    #chartCard,
    .no-print,
    .modal-backdrop,
    .modal-header .btn-close,
    .modal-footer {
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

    .table-report tfoot tr td {
        background-color: #f1f5f9 !important;
        font-weight: 800 !important;
        border-top: 2px solid #0f172a !important;
        border-bottom: 2px solid #0f172a !important;
        color: #0f172a !important;
        font-size: 9.5pt !important;
    }

    /* Period links become clean black text without link styling on paper */
    .clickable-period {
        color: #111827 !important;
        text-decoration: none !important;
        pointer-events: none !important;
    }
    .clickable-period i {
        display: none !important;
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

    /* Print Breakdown Mode: hides main report, shows only modal breakdown report */
    body.printing-breakdown #printableArea {
        display: none !important;
    }

    body.printing-breakdown #breakdownModal {
        display: block !important;
        position: static !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body.printing-breakdown #breakdownModal .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body.printing-breakdown #breakdownModal .modal-content {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    body.printing-breakdown #breakdownModal .modal-body {
        padding: 0 !important;
    }

    body:not(.printing-breakdown) #breakdownPrintHeader {
        display: none !important;
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
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Sales Reports</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-chart-line me-2 text-emerald"></i>Sales Reports</h1>
                <p class="page-header-subtitle">Analyze your daily, weekly, and monthly revenue trends.</p>
            </div>
            <button class="btn btn-primary" onclick="printMainReport()">
                <i class="fa-solid fa-print me-2"></i>Print Report
            </button>
        </div>

        <div class="report-tabs">
            <div class="report-tab active" onclick="loadReport('daily', this)">Daily Sales</div>
            <div class="report-tab" onclick="loadReport('weekly', this)">Weekly Sales</div>
            <div class="report-tab" onclick="loadReport('monthly', this)">Monthly Sales</div>
        </div>

        <div id="printableArea">

            <!-- ========================================================
                 PRINT-ONLY OFFICIAL DOCUMENT HEADER (Sales Table Report)
                 ======================================================== -->
            <div class="print-only-block print-header">
                <div class="print-store-title">Sharon Store</div>
                <div class="print-store-sub">Pampang Public Market, Angeles City, Pampanga</div>
                <div class="print-store-sub">Point of Sale &amp; Inventory Management System</div>
                <div class="print-report-title" id="printReportHeading">Official Daily Sales Report</div>
                
                <table class="print-meta-grid mt-3">
                    <tr>
                        <td style="width:50%; text-align:left;">
                            <strong>Period Covered:</strong> <span id="printMetaPeriod">Daily Sales (Last 30 Days)</span><br>
                            <strong>Date &amp; Time Generated:</strong> <?= date('F d, Y h:i A') ?>
                        </td>
                        <td style="width:50%; text-align:right;">
                            <strong>Generated By:</strong> <?= htmlspecialchars($currentAdminName) ?> (<?= ucfirst(htmlspecialchars($currentAdminRole)) ?>)<br>
                            <strong>Report Classification:</strong> Official Business Report
                        </td>
                    </tr>
                </table>

                <!-- Mini KPI Summary Strip -->
                <div class="print-kpi-strip">
                    <div class="print-kpi-item">
                        <span class="print-kpi-label">Periods Recorded</span>
                        <span class="print-kpi-val" id="printKpiPeriods">0</span>
                    </div>
                    <div class="print-kpi-item">
                        <span class="print-kpi-label">Total Transactions</span>
                        <span class="print-kpi-val" id="printKpiTxns">0</span>
                    </div>
                    <div class="print-kpi-item">
                        <span class="print-kpi-label">Total Revenue</span>
                        <span class="print-kpi-val" id="printKpiRevenue">₱0.00</span>
                    </div>
                </div>
            </div>

            <!-- On-Screen Chart Card (Hidden during printing) -->
            <div class="card mb-4 no-print" id="chartCard">
                <div class="card-header bg-white">
                    <h5 class="mb-0" id="chartTitle">Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Table Card / Official Print Table -->
            <div class="card">
                <div class="card-header bg-white no-print">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Data Breakdown</h5>
                        <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>Click any date to view items sold</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-report mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">#</th>
                                    <th>Period / Date</th>
                                    <th class="text-center" style="width: 160px;">Transactions</th>
                                    <th class="text-end" style="width: 200px;">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <tr><td colspan="4" class="text-center py-4">Loading data...</td></tr>
                            </tbody>
                            <tfoot id="tableFoot">
                                <tr style="background:#f8fafc; font-weight:800;">
                                    <td colspan="2" class="text-uppercase text-dark">Grand Total</td>
                                    <td class="text-center" id="footTotalTxns">0</td>
                                    <td class="text-end" id="footTotalRevenue" style="color:var(--accent);">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================
                 PRINT-ONLY OFFICIAL SIGNATURES & AUDIT FOOTER
                 ======================================================== -->
            <div class="print-only-block print-signatures">
                <div style="display:flex; justify-content:space-between;">
                    <div class="sig-col" style="text-align:left;">
                        <div class="sig-line"></div>
                        <div class="sig-name"><?= htmlspecialchars($currentAdminName) ?></div>
                        <div class="sig-role">Prepared By (Administrator)</div>
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

        </div><!-- /printableArea -->

    </div>
</div>

<!-- ========================================================
     DETAILED ITEM BREAKDOWN MODAL (With Dedicated Print Button)
     ======================================================== -->
<div class="modal fade" id="breakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            
            <!-- Modal Header (Screen Only) -->
            <div class="modal-header border-0 bg-light no-print">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Items Sold Breakdown</h5>
                    <small class="text-muted">Period: <span id="bdModalTitle" class="fw-bold text-emerald"></span></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Print Header for Breakdown (Shows only when printing breakdown) -->
            <div class="print-only-block print-header" id="breakdownPrintHeader">
                <div class="print-store-title">Sharon Store</div>
                <div class="print-store-sub">Pampang Public Market, Angeles City, Pampanga</div>
                <div class="print-store-sub">Point of Sale &amp; Inventory Management System</div>
                <div class="print-report-title">Detailed Items Sold Breakdown</div>
                
                <table class="print-meta-grid mt-3">
                    <tr>
                        <td style="width:50%; text-align:left;">
                            <strong>Period Covered:</strong> <span id="bdPrintPeriodLabel">-</span><br>
                            <strong>Date &amp; Time Generated:</strong> <?= date('F d, Y h:i A') ?>
                        </td>
                        <td style="width:50%; text-align:right;">
                            <strong>Generated By:</strong> <?= htmlspecialchars($currentAdminName) ?> (<?= ucfirst(htmlspecialchars($currentAdminRole)) ?>)<br>
                            <strong>Report Type:</strong> Itemized Sales Breakdown
                        </td>
                    </tr>
                </table>
            </div>

            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-report mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px;" class="text-center">#</th>
                                <th class="sortable-th" onclick="sortBreakdown('item_name')">
                                    Item Name <i class="fa-solid fa-sort sort-icon no-print" id="sort-icon-item_name"></i>
                                </th>
                                <th class="sortable-th text-center" style="width:160px;" onclick="sortBreakdown('qty_sold')">
                                    Quantity Sold <i class="fa-solid fa-sort sort-icon no-print" id="sort-icon-qty_sold"></i>
                                </th>
                                <th class="sortable-th text-end" style="width:190px;" onclick="sortBreakdown('total_revenue')">
                                    Total Revenue <i class="fa-solid fa-sort sort-icon no-print" id="sort-icon-total_revenue"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="bdTableBody">
                            <tr><td colspan="4" class="text-center py-4">Loading...</td></tr>
                        </tbody>
                        <tfoot id="bdTableFoot">
                            <tr style="background:#f8fafc; font-weight:800;">
                                <td colspan="2" class="text-uppercase text-dark">Breakdown Total</td>
                                <td class="text-center" id="bdFootTotalQty">0</td>
                                <td class="text-end" id="bdFootTotalRev" style="color:var(--accent);">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Print Signatures for Breakdown Modal -->
            <div class="print-only-block print-signatures" id="breakdownPrintFooter">
                <div style="display:flex; justify-content:space-between;">
                    <div class="sig-col" style="text-align:left;">
                        <div class="sig-line"></div>
                        <div class="sig-name"><?= htmlspecialchars($currentAdminName) ?></div>
                        <div class="sig-role">Prepared By (Administrator)</div>
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

            <!-- Modal Footer with Print and Close -->
            <div class="modal-footer border-0 no-print">
                <button type="button" class="btn btn-outline-primary" onclick="printBreakdownReport()">
                    <i class="fa-solid fa-print me-2"></i>Print Breakdown Report
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js?v=25"></script>
<script>
let salesChart = null;
let currentReportType = 'daily';
let breakdownData = [];
let currentBreakdownPeriodLabel = '';
let sortCol = 'total_revenue';
let sortDesc = true;
const bdModal = new bootstrap.Modal(document.getElementById('breakdownModal'));

// Print Main Sales Table Report
function printMainReport() {
    document.body.classList.remove('printing-breakdown');
    window.print();
}

// Print Modal Item Breakdown Report
function printBreakdownReport() {
    document.body.classList.add('printing-breakdown');
    window.print();
}

// Cleanup breakdown print class after printing
window.addEventListener('afterprint', () => {
    document.body.classList.remove('printing-breakdown');
});

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
    const printHeadingMap = {
        'daily': 'Official Daily Sales Report',
        'weekly': 'Official Weekly Sales Report',
        'monthly': 'Official Monthly Sales Report'
    };
    const printMetaMap = {
        'daily': 'Daily Sales (Last 30 Days)',
        'weekly': 'Weekly Sales (Last 12 Weeks)',
        'monthly': 'Monthly Sales (Last 12 Months)'
    };

    document.getElementById('chartTitle').textContent = titleMap[type] || 'Revenue Trend';
    document.getElementById('printReportHeading').textContent = printHeadingMap[type] || 'Official Sales Report';
    document.getElementById('printMetaPeriod').textContent = printMetaMap[type] || 'Sales Report';
    
    document.getElementById('tableBody').innerHTML = '<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm text-emerald"></span></td></tr>';

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

        // Compute Totals
        let grandTxCount = 0;
        let grandRevenue = 0;
        const totalPeriods = d.tableData ? d.tableData.length : 0;

        // Update Table
        let tbody = '';
        if (!d.tableData || d.tableData.length === 0) {
            tbody = '<tr><td colspan="4" class="text-center py-4 text-muted">No sales data found for this period.</td></tr>';
        } else {
            d.tableData.forEach((row, index) => {
                const safePeriod = esc(row.period);
                const safeRawDate = esc(row.raw_date);
                const txCount = parseInt(row.tx_count, 10) || 0;
                const revenue = parseFloat(row.revenue) || 0;

                grandTxCount += txCount;
                grandRevenue += revenue;

                tbody += `<tr>
                    <td class="text-center text-muted fw-bold">${index + 1}</td>
                    <td class="fw-600">
                        <a class="clickable-period" onclick="openBreakdown('${safeRawDate}', '${safePeriod}')" title="Click to view detailed items sold">
                            <i class="fa-solid fa-magnifying-glass-chart me-1"></i> ${safePeriod}
                        </a>
                    </td>
                    <td class="text-center">${txCount.toLocaleString()}</td>
                    <td class="text-end fw-700" style="color:var(--accent);">₱${revenue.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                </tr>`;
            });
        }
        document.getElementById('tableBody').innerHTML = tbody;

        // Update Table Footers & Print KPI Strip
        document.getElementById('footTotalTxns').textContent = grandTxCount.toLocaleString();
        document.getElementById('footTotalRevenue').textContent = '₱' + grandRevenue.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        
        document.getElementById('printKpiPeriods').textContent = totalPeriods;
        document.getElementById('printKpiTxns').textContent = grandTxCount.toLocaleString();
        document.getElementById('printKpiRevenue').textContent = '₱' + grandRevenue.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    } catch (e) {
        showToast('Failed to load report data.', 'error');
        document.getElementById('tableBody').innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Error loading data.</td></tr>';
    }
}

async function openBreakdown(rawDate, periodLabel) {
    currentBreakdownPeriodLabel = periodLabel;
    document.getElementById('bdModalTitle').textContent = periodLabel;
    document.getElementById('bdPrintPeriodLabel').textContent = periodLabel;
    document.getElementById('bdTableBody').innerHTML = '<tr><td colspan="4" class="text-center py-4"><span class="spinner-border text-emerald"></span></td></tr>';
    bdModal.show();
    
    // Reset sort
    sortCol = 'total_revenue';
    sortDesc = true;
    updateSortIcons();

    try {
        const res = await fetch(`/sharonstore3.0/api/reports.php?action=item_breakdown&type=${currentReportType}&raw_date=${encodeURIComponent(rawDate)}`);
        const json = await res.json();
        
        if (json.success) {
            breakdownData = json.data || [];
            renderBreakdownTable();
        } else {
            document.getElementById('bdTableBody').innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger">${esc(json.message)}</td></tr>`;
        }
    } catch (err) {
        document.getElementById('bdTableBody').innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Failed to fetch data.</td></tr>';
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
        if (el) {
            if (c === sortCol) {
                el.className = 'fa-solid sort-icon no-print ' + (sortDesc ? 'fa-sort-down' : 'fa-sort-up');
                el.style.color = 'var(--accent)';
            } else {
                el.className = 'fa-solid fa-sort sort-icon no-print';
                el.style.color = 'var(--txt-muted)';
            }
        }
    });
}

function renderBreakdownTable() {
    if (!breakdownData || breakdownData.length === 0) {
        document.getElementById('bdTableBody').innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No items sold in this period.</td></tr>';
        document.getElementById('bdFootTotalQty').textContent = '0';
        document.getElementById('bdFootTotalRev').textContent = '₱0.00';
        return;
    }
    
    let html = '';
    let totalQty = 0;
    let totalRev = 0;

    breakdownData.forEach((row, idx) => {
        const qty = parseFloat(row.qty_sold) || 0;
        const rev = parseFloat(row.total_revenue) || 0;
        totalQty += qty;
        totalRev += rev;

        html += `<tr>
            <td class="text-center text-muted fw-bold">${idx + 1}</td>
            <td class="fw-600">${esc(row.item_name)}</td>
            <td class="text-center fw-700">${qty}</td>
            <td class="text-end fw-700" style="color:var(--accent);">₱${rev.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
        </tr>`;
    });
    document.getElementById('bdTableBody').innerHTML = html;

    // Footers
    document.getElementById('bdFootTotalQty').textContent = totalQty.toLocaleString();
    document.getElementById('bdFootTotalRev').textContent = '₱' + totalRev.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Initial load
loadReport('daily');
</script>
</body>
</html>
