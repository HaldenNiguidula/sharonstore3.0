<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();
$pageTitle = 'Sales Forecasting';
$currentUser = currentUserName();
$currentRole = currentRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Forecasting — Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=17">
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

.print-only-block {
    display: none !important;
}

/* Integrated Search Autocomplete Suggestions */
.suggestion-item {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
    transition: all 0.12s ease-in-out;
    text-align: left;
    width: 100%;
}
.suggestion-item:last-child {
    border-bottom: none;
}
.suggestion-item:hover, .suggestion-item.active-suggestion {
    background-color: #f0fdf4 !important;
}
.suggestion-item.active-suggestion .fw-semibold {
    color: #059669 !important;
}
.suggestion-item.selected-item {
    background-color: #ecfdf5 !important;
    border-left: 3px solid #10b981;
}
.forecast-item-link:hover .product-title-link {
    color: #059669 !important;
    text-decoration: underline;
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

    /* Hide website UI chrome and on-screen control cards */
    .ss-sidebar,
    .ss-topbar,
    .btn,
    .breadcrumb-custom,
    .page-header,
    #forecastControlCard,
    #methodCards,
    #chartCard,
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

        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Forecasting</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-chart-line me-2 text-emerald"></i>Sales Forecasting</h1>
                <p class="page-header-subtitle">Predict future demand using Simple &amp; Weighted Moving Averages to optimize wholesale purchasing.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print me-2"></i>Print Report
                </button>
            </div>
        </div>

        <!-- PRINT-ONLY OFFICIAL DOCUMENT HEADER -->
        <div class="print-only-block print-header">
            <div class="print-store-title">Sharon Store</div>
            <div class="print-store-sub">Pampang Public Market, Angeles City, Pampanga</div>
            <div class="print-store-sub">Point of Sale &amp; Inventory Management System</div>
            <div class="print-report-title">Official Demand Forecasting &amp; Restock Report</div>
            
            <table class="print-meta-grid mt-3">
                <tr>
                    <td style="width:50%; text-align:left;">
                        <strong>Date &amp; Time Generated:</strong> <?= date('F d, Y h:i A') ?><br>
                        <strong>Methodology:</strong> Simple &amp; Weighted Moving Averages (SMA &amp; WMA)
                    </td>
                    <td style="width:50%; text-align:right;">
                        <strong>Generated By:</strong> <?= htmlspecialchars($currentUser) ?> (<?= ucfirst(htmlspecialchars($currentRole)) ?>)<br>
                        <strong>Classification:</strong> Procurement &amp; Purchasing Recommendation
                    </td>
                </tr>
            </table>

            <!-- Mini KPI Summary Strip -->
            <div class="print-kpi-strip">
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Forecasted Products</span>
                    <span class="print-kpi-val" id="pTotalForecasted">0 Items</span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Needs Restock</span>
                    <span class="print-kpi-val" id="pNeedsRestock">0 Items</span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Recommended Restock</span>
                    <span class="print-kpi-val" id="pEstUnits">0 Units</span>
                </div>
            </div>
        </div>

        <!-- Forecast Controls (Screen Only) -->
        <div class="card mb-4 no-print" id="forecastControlCard">
            <div class="card-header"><h5><i class="fa-solid fa-sliders me-2 text-emerald"></i>Generate Forecast</h5></div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold">Select Product</span>
                            <span class="text-muted small" id="productSearchBadge"></span>
                        </label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="productSearchInput" class="form-control border-start-0 ps-1" placeholder="Search product name, category, barcode..." autocomplete="off">
                                <button class="btn btn-outline-secondary d-none" type="button" id="clearProductSearchBtn" onclick="clearProductSearch()" title="Clear product selection">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <input type="hidden" id="selectedItemId" value="">
                            <div id="productSuggestionsList" class="dropdown-menu shadow-lg w-100 p-0 mt-1" style="max-height: 280px; overflow-y: auto; display: none; z-index: 1060; border-radius: 8px;">
                            </div>
                        </div>
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

            <!-- Method Cards (Screen Only) -->
            <div class="row g-3 mb-4 no-print" id="methodCards">
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

            <!-- Chart (Screen Only) -->
            <div class="card mb-4 no-print" id="chartCard">
                <div class="card-header">
                    <h5><i class="fa-solid fa-chart-area me-2 text-emerald"></i>Historical Sales vs. Forecast — <span id="chartItemName"></span></h5>
                </div>
                <div class="card-body">
                    <canvas id="forecastChart" height="80"></canvas>
                </div>
            </div>

            <!-- Historical Table -->
            <div class="card mb-4">
                <div class="card-header no-print"><h5><i class="fa-solid fa-table me-2 text-emerald"></i>Monthly Data Table</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-report mb-0">
                            <thead>
                                <tr>
                                    <th class="sortable-th">Month <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                    <th class="text-center sortable-th">Actual Qty Sold <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                    <th class="text-center sortable-th" style="color:#3b82f6;">SMA Prediction <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                    <th class="text-center sortable-th" style="color:#10b981;">WMA Prediction <i class="fa-solid fa-sort sort-icon no-print"></i></th>
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
            <div class="card-header d-flex justify-content-between align-items-center no-print">
                <h5><i class="fa-solid fa-list-check me-2 text-emerald"></i>Forecast Overview — All Products</h5>
                <button class="btn btn-sm btn-secondary" onclick="loadOverview()"><i class="fa-solid fa-rotate-right me-1"></i>Refresh</button>
            </div>
            <div class="card-body p-0" id="overviewContainer">
                <div class="chart-loading p-4"><div class="loading-spinner"></div></div>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js?v=26"></script>
<script>
let forecastChart;

let allForecastItems = [];

let activeSuggestionIndex = -1;

// ── Load item list ──────────────────────────────────────────
async function loadItems() {
    const r = await fetch('/sharonstore3.0/api/forecasting.php?action=items_list');
    const j = await r.json();
    if (!j.success) return;
    allForecastItems = j.data || [];
    const badge = document.getElementById('productSearchBadge');
    if (badge) {
        badge.textContent = `${allForecastItems.length} products`;
    }
}

function renderProductSuggestions(query = '') {
    const list = document.getElementById('productSuggestionsList');
    const badge = document.getElementById('productSearchBadge');
    const clearBtn = document.getElementById('clearProductSearchBtn');
    const selectedId = document.getElementById('selectedItemId') ? document.getElementById('selectedItemId').value : '';
    activeSuggestionIndex = -1;

    const q = (query || '').toLowerCase().trim();
    if (clearBtn) {
        clearBtn.classList.toggle('d-none', !query && !selectedId);
    }

    let filtered = allForecastItems;
    if (q) {
        filtered = allForecastItems.filter(i => {
            const name = (i.item_name || '').toLowerCase();
            const cat = (i.category_name || '').toLowerCase();
            const barcode = (i.barcode || '').toLowerCase();
            return name.includes(q) || cat.includes(q) || barcode.includes(q);
        });
    }

    if (badge) {
        if (selectedId) {
            const selItem = allForecastItems.find(i => String(i.item_id) === String(selectedId));
            badge.innerHTML = `<span class="badge bg-success text-white"><i class="fa-solid fa-check me-1"></i>${esc(selItem ? selItem.category_name || 'Selected' : 'Selected')}</span>`;
        } else if (q) {
            badge.textContent = `${filtered.length} found`;
            badge.className = filtered.length > 0 ? 'badge bg-emerald text-white' : 'badge bg-danger text-white';
        } else {
            badge.textContent = `${allForecastItems.length} products`;
            badge.className = 'text-muted small';
        }
    }

    if (!list) return;

    if (!filtered.length) {
        list.innerHTML = `
            <div class="p-3 text-center text-muted small">
                <i class="fa-solid fa-box-open me-1 text-secondary"></i> No matching products found
            </div>`;
        list.style.display = 'block';
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    filtered.forEach((i, idx) => {
        const isSelected = String(i.item_id) === String(selectedId);
        const catBadge = i.category_name ? `<span class="badge bg-light text-secondary border me-2">${esc(i.category_name)}</span>` : '';
        const barcodeText = i.barcode ? `<span class="text-muted small me-2"><i class="fa-solid fa-barcode me-1"></i>${esc(i.barcode)}</span>` : '';
        html += `
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 suggestion-item ${isSelected ? 'selected-item' : ''}" 
                    data-index="${idx}" data-id="${i.item_id}" onclick="selectProductById(${i.item_id})">
                <div class="text-start me-2">
                    <div class="fw-semibold text-dark">${esc(i.item_name)}</div>
                    <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                        ${catBadge}
                        ${barcodeText}
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <span class="badge bg-light text-dark border">Stock: ${i.stock_qty} ${esc(i.unit)}</span>
                </div>
            </button>`;
    });
    html += '</div>';
    list.innerHTML = html;
    list.style.display = 'block';
}

function selectProductById(itemId) {
    const item = allForecastItems.find(i => String(i.item_id) === String(itemId));
    if (!item) return;

    const hiddenId = document.getElementById('selectedItemId');
    const input = document.getElementById('productSearchInput');
    const clearBtn = document.getElementById('clearProductSearchBtn');
    const badge = document.getElementById('productSearchBadge');

    if (hiddenId) hiddenId.value = item.item_id;
    if (input) input.value = item.item_name;
    if (clearBtn) clearBtn.classList.remove('d-none');
    if (badge) {
        badge.innerHTML = `<span class="badge bg-success text-white"><i class="fa-solid fa-check me-1"></i>${esc(item.category_name || 'Selected')}</span>`;
    }

    hideProductSuggestions();
}

function hideProductSuggestions() {
    const list = document.getElementById('productSuggestionsList');
    if (list) list.style.display = 'none';
    activeSuggestionIndex = -1;
}

function showProductSuggestions() {
    const input = document.getElementById('productSearchInput');
    renderProductSuggestions(input ? input.value : '');
}

function clearProductSearch() {
    const inp = document.getElementById('productSearchInput');
    const selected = document.getElementById('selectedItemId');
    if (selected) selected.value = '';
    if (inp) {
        inp.value = '';
        inp.focus();
    }
    const clearBtn = document.getElementById('clearProductSearchBtn');
    if (clearBtn) clearBtn.classList.add('d-none');

    const badge = document.getElementById('productSearchBadge');
    if (badge) {
        badge.textContent = `${allForecastItems.length} products`;
        badge.className = 'text-muted small';
    }

    renderProductSuggestions('');
}

function updateActiveSuggestion(items) {
    items.forEach((item, idx) => {
        if (idx === activeSuggestionIndex) {
            item.classList.add('active-suggestion');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('active-suggestion');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInp = document.getElementById('productSearchInput');

    if (searchInp) {
        searchInp.addEventListener('focus', () => {
            showProductSuggestions();
        });

        searchInp.addEventListener('input', (e) => {
            const selectedId = document.getElementById('selectedItemId').value;
            if (selectedId) {
                const selItem = allForecastItems.find(i => String(i.item_id) === String(selectedId));
                if (!selItem || selItem.item_name.toLowerCase() !== e.target.value.toLowerCase()) {
                    document.getElementById('selectedItemId').value = '';
                }
            }
            renderProductSuggestions(e.target.value);
        });

        searchInp.addEventListener('keydown', (e) => {
            const list = document.getElementById('productSuggestionsList');
            const items = list ? list.querySelectorAll('.suggestion-item') : [];

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!items.length) {
                    showProductSuggestions();
                    return;
                }
                activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
                updateActiveSuggestion(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!items.length) return;
                activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
                updateActiveSuggestion(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (list && list.style.display !== 'none' && activeSuggestionIndex >= 0 && items[activeSuggestionIndex]) {
                    items[activeSuggestionIndex].click();
                    document.getElementById('btnForecast').click();
                } else if (document.getElementById('selectedItemId').value) {
                    hideProductSuggestions();
                    document.getElementById('btnForecast').click();
                } else {
                    const q = (searchInp.value || '').trim().toLowerCase();
                    if (q) {
                        const exact = allForecastItems.find(i => (i.item_name || '').toLowerCase() === q || (i.barcode || '').toLowerCase() === q);
                        if (exact) {
                            selectProductById(exact.item_id);
                            document.getElementById('btnForecast').click();
                            return;
                        }
                        const filtered = allForecastItems.filter(i => {
                            return (i.item_name || '').toLowerCase().includes(q) ||
                                   (i.category_name || '').toLowerCase().includes(q) ||
                                   (i.barcode || '').toLowerCase().includes(q);
                        });
                        if (filtered.length === 1) {
                            selectProductById(filtered[0].item_id);
                            document.getElementById('btnForecast').click();
                        } else if (filtered.length > 1) {
                            showProductSuggestions();
                        }
                    } else {
                        showProductSuggestions();
                    }
                }
            } else if (e.key === 'Escape') {
                hideProductSuggestions();
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#productSearchInput') && !e.target.closest('#productSuggestionsList') && !e.target.closest('#clearProductSearchBtn')) {
            hideProductSuggestions();
        }
    });
});

// ── Generate Forecast ─────────────────────────────────────────
document.getElementById('btnForecast').addEventListener('click', async () => {
    let itemId  = document.getElementById('selectedItemId').value;
    const periods = document.getElementById('periodsSelect').value;

    if (!itemId) {
        const query = (document.getElementById('productSearchInput').value || '').trim().toLowerCase();
        if (query) {
            const match = allForecastItems.find(i => (i.item_name || '').toLowerCase() === query || (i.barcode || '').toLowerCase() === query);
            if (match) {
                selectProductById(match.item_id);
                itemId = match.item_id;
            } else {
                const filtered = allForecastItems.filter(i => {
                    return (i.item_name || '').toLowerCase().includes(query) ||
                           (i.category_name || '').toLowerCase().includes(query) ||
                           (i.barcode || '').toLowerCase().includes(query);
                });
                if (filtered.length === 1) {
                    selectProductById(filtered[0].item_id);
                    itemId = filtered[0].item_id;
                }
            }
        }
    }

    if (!itemId) {
        showToast('Please select a product to generate forecast.', 'warning');
        showProductSuggestions();
        document.getElementById('productSearchInput').focus();
        return;
    }

    const btn = document.getElementById('btnForecast');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Calculating…';

    const r = await fetch(`/sharonstore3.0/api/forecasting.php?action=forecast&item_id=${itemId}&periods=${periods}`);
    const j = await r.json();

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Forecast';

    if (!j.success) { showToast(j.message, 'error'); return; }

    const d = j.data;
    document.getElementById('forecastResults').style.display = 'block';

    // Populate method cards
    document.getElementById('smaForecastVal').textContent = d.sma_forecast ? Math.round(d.sma_forecast) : '—';
    document.getElementById('wmaForecastVal').textContent = d.wma_forecast ? Math.round(d.wma_forecast) : '—';
    document.getElementById('smaRestockVal').textContent  = d.sma_restock  ? Math.round(d.sma_restock)  : '0';
    document.getElementById('wmaRestockVal').textContent  = d.wma_restock  ? Math.round(d.wma_restock)  : '0';
    document.getElementById('smaUnit').textContent        = d.unit;
    document.getElementById('wmaUnit').textContent        = d.unit;
    document.getElementById('chartItemName').textContent  = d.item_name;

    // Build Chart
    const labels   = d.history.map(h => h.month_label);
    const actuals  = d.history.map(h => parseFloat(h.qty_sold));
    const smaLine  = d.history.map(h => null);
    const wmaLine  = d.history.map(h => null);

    labels.push('Next Month (' + d.next_period + ')');
    actuals.push(null);
    smaLine.push(d.sma_forecast);
    wmaLine.push(d.wma_forecast);

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
            <td class="fw-600" data-sort-value="${esc(h.month_key || h.month_label)}">${esc(h.month_label)}</td>
            <td class="text-center fw-700" data-sort-value="${parseFloat(h.qty_sold)}">${parseFloat(h.qty_sold)}</td>
            <td class="text-center" style="color:#3b82f6;" data-sort-value="${d.sma_forecast || 0}">${d.sma_forecast ? Math.round(d.sma_forecast) : '-'}</td>
            <td class="text-center" style="color:#10b981;" data-sort-value="${d.wma_forecast || 0}">${d.wma_forecast ? Math.round(d.wma_forecast) : '-'}</td>
        </tr>`;
    });
    tbody += `<tr class="no-sort-row" style="background:rgba(16,185,129,0.08); font-weight:800;">
        <td>Next Month (${esc(d.next_period)})</td>
        <td class="text-center text-muted">—</td>
        <td class="text-center" style="color:#3b82f6;">${d.sma_forecast ? Math.round(d.sma_forecast) : '-'}</td>
        <td class="text-center" style="color:#10b981;">${d.wma_forecast ? Math.round(d.wma_forecast) : '-'}</td>
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

        // Update Print KPI Strip
        document.getElementById('pTotalForecasted').textContent = totalItems + ' Items';
        document.getElementById('pNeedsRestock').textContent     = needsRestockCount + ' Items';
        document.getElementById('pEstUnits').textContent         = totalRestockQty + ' Units';

        let html = `
            <div class="row g-3 mb-3 p-3 pb-0 no-print" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; margin:0;">
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
            <div class="table-responsive"><table class="table table-custom table-report mb-0">
            <thead><tr>
                <th style="width:50px;" class="text-center sortable-th"># <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="sortable-th">Product <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="sortable-th">Category <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="text-center sortable-th">Current Stock <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="text-center sortable-th" style="color:#3b82f6;">SMA Forecast <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="text-center sortable-th" style="color:#10b981;">WMA Forecast <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="text-center sortable-th" style="color:#f59e0b;">SMA Order <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="text-center sortable-th" style="color:#f59e0b;">WMA Order <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                <th class="text-center sortable-th">Status <i class="fa-solid fa-sort sort-icon no-print"></i></th>
            </tr></thead><tbody>`;
            
        j.data.forEach((row, idx) => {
            const needsRestock = row.needs_restock;
            const badge = needsRestock
                ? '<span class="badge badge-warning">Restock Needed</span>'
                : (row.sma_forecast ? '<span class="badge badge-success">Adequate</span>' : '<span class="badge badge-secondary">No Data</span>');
            html += `<tr class="${needsRestock?'table-row-warning':''}">
                <td class="text-center text-muted fw-bold" data-sort-value="${idx + 1}">${idx + 1}</td>
                <td class="fw-600" data-sort-value="${esc(row.item_name)}">
                    <a href="javascript:void(0)" class="text-dark text-decoration-none forecast-item-link" onclick="selectProductById(${row.item_id}); document.getElementById('btnForecast').click(); window.scrollTo({top: 0, behavior: 'smooth'});" title="Click to view forecast for ${esc(row.item_name)}">
                        <span class="product-title-link">${esc(row.item_name)}</span>
                        <i class="fa-solid fa-arrow-trend-up ms-1 text-emerald opacity-75 small no-print"></i>
                    </a>
                </td>
                <td class="text-muted" data-sort-value="${esc(row.category_name)}">${esc(row.category_name)}</td>
                <td class="text-center" data-sort-value="${parseFloat(row.stock_qty)}">${parseFloat(row.stock_qty)} ${esc(row.unit)}</td>
                <td class="text-center" style="color:#3b82f6;" data-sort-value="${row.sma_forecast || 0}">${row.sma_forecast ? Math.round(row.sma_forecast) : '—'}</td>
                <td class="text-center" style="color:#10b981;" data-sort-value="${row.wma_forecast || 0}">${row.wma_forecast ? Math.round(row.wma_forecast) : '—'}</td>
                <td class="text-center fw-700" style="color:#f59e0b;" data-sort-value="${row.sma_restock || 0}">${row.sma_restock > 0 ? row.sma_restock : '—'}</td>
                <td class="text-center fw-700" style="color:#f59e0b;" data-sort-value="${row.wma_restock || 0}">${row.wma_restock > 0 ? row.wma_restock : '—'}</td>
                <td class="text-center" data-sort-value="${needsRestock ? 2 : (row.sma_forecast ? 1 : 0)}">${badge}</td>
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

(function attachFilterEnterKey() {
    const filterControls = ['periodsSelect'];
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
}());
</script>
</body>
</html>
