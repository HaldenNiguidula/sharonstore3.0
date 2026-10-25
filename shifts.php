<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Shift History';
$db = getDB();

$stmt = $db->query("
    SELECT s.*, 
           COALESCE(u.full_name, 'Former Employee') as cashier_name, 
           COALESCE(u.username, 'deleted') as cashier_username
    FROM tbl_shifts s
    LEFT JOIN tbl_users u ON u.user_id = s.cashier_id
    ORDER BY s.shift_id DESC
");
$shifts = $stmt->fetchAll();

// Statistics calculation
$totalShifts = count($shifts);
$openShifts = 0;
$closedShifts = 0;
$totalStartingCash = 0.0;
$totalExpectedCash = 0.0;
$totalActualCash = 0.0;
$totalVariance = 0.0;

foreach ($shifts as $s) {
    if ($s['status'] === 'open') {
        $openShifts++;
    } else {
        $closedShifts++;
    }
    $totalStartingCash += (float)($s['starting_cash'] ?? 0);
    if ($s['expected_cash'] !== null) {
        $totalExpectedCash += (float)$s['expected_cash'];
    }
    if ($s['actual_cash'] !== null) {
        $totalActualCash += (float)$s['actual_cash'];
    }
    if ($s['short_over'] !== null) {
        $totalVariance += (float)$s['short_over'];
    }
}
$currentUser = currentUserName();
$currentRole = currentRole();
$dailyPin = getDailyManagerPIN($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift History - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=40">
<style>
/* ── Shift History Page Custom Styles ── */
.badge-variance {
    font-weight: 700;
    padding: 0.35rem 0.7rem;
    border-radius: 20px;
    font-size: 0.74rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.badge-variance.over {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.badge-variance.short {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}
.badge-variance.exact {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
}

.shift-id-tag {
    font-family: 'Roboto Mono', monospace;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--txt-2);
    background: var(--surface-2);
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid var(--bd);
    display: inline-block;
}

.cashier-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-dk));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.status-indicator-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.status-indicator-dot.active {
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

.copy-pin-btn {
    cursor: pointer;
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.copy-pin-btn:hover {
    opacity: 0.8;
    transform: scale(1.1);
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed var(--bd);
    font-size: 0.9rem;
}
.detail-row:last-child {
    border-bottom: none;
}
.detail-label {
    color: var(--txt-muted);
    font-weight: 600;
}
.detail-val {
    font-weight: 700;
    color: var(--txt);
}

.print-only-block {
    display: none !important;
}

tr.shift-row.filter-hidden {
    display: none !important;
}
tr.shift-row.page-hidden {
    display: none !important;
}

#shiftsTable {
    table-layout: fixed;
    width: 100%;
}
#shiftsTable th,
#shiftsTable td {
    white-space: nowrap;
}

#shiftsPaginationContainer {
    position: sticky;
    bottom: 0;
    z-index: 25;
    background: #ffffff !important;
    box-shadow: 0 -4px 14px rgba(0, 0, 0, 0.08);
    border-top: 1px solid var(--bd) !important;
}

#shiftsTopPaginationWrapper .pagination .page-link,
#shiftsPaginationContainer .pagination .page-link {
    font-weight: 600;
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
    #statCards,
    #filterCard,
    .modal,
    .toast-container,
    .no-print,
    #shiftsPaginationContainer,
    #shiftsTable th:last-child,
    #shiftsTable td:last-child {
        display: none !important;
    }

    tr.shift-row.page-hidden {
        display: table-row !important;
    }
    tr.shift-row.filter-hidden {
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

    .table-report tfoot tr td {
        background-color: #f1f5f9 !important;
        font-weight: 800 !important;
        border-top: 2px solid #0f172a !important;
        border-bottom: 2px solid #0f172a !important;
        color: #0f172a !important;
        font-size: 9pt !important;
    }

    .shift-id-tag {
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        color: #0f172a !important;
        font-weight: 700 !important;
    }

    .cashier-avatar {
        display: none !important;
    }

    .badge-variance {
        border: 1px solid #94a3b8 !important;
        background: transparent !important;
        color: #0f172a !important;
        padding: 2px 4px !important;
        font-size: 8pt !important;
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

@media (max-width: 767px) {
    .page-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
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
                <ul class="breadcrumb-custom">
                    <li><a href="/sharonstore3.0/dashboard.php">Home</a></li>
                    <li class="active">Shift History</li>
                </ul>
                <h1 class="page-header-title">
                    <i class="fa-solid fa-clock-rotate-left me-2 text-emerald"></i>Shift History
                </h1>
                <p class="page-header-subtitle">
                    Review cashier work shifts, starting cash, drawer reconciliations, and cash discrepancies.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i>Print Report
                </button>
                <a href="shifts.php" class="btn btn-outline-primary" title="Reload shift data">
                    <i class="fa-solid fa-rotate-right me-1"></i>Refresh
                </a>
            </div>
        </div>

        <!-- Stat Cards Row -->
        <div class="row g-3 mb-4" id="statCards">
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon emerald"><i class="fa-solid fa-receipt"></i></div>
                    <div class="stat-value"><?= number_format($totalShifts) ?></div>
                    <div class="stat-label">Total Shifts Logged</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-user-clock"></i></div>
                    <div class="stat-value"><?= number_format($openShifts) ?></div>
                    <div class="stat-label">Active / Open Shifts</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-wallet"></i></div>
                    <div class="stat-value">₱<?= number_format($totalStartingCash, 2) ?></div>
                    <div class="stat-label">Starting Cash Total</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="fa-solid fa-key"></i></div>
                    <div class="stat-value font-monospace d-flex align-items-center gap-2">
                        <span><?= htmlspecialchars($dailyPin) ?></span>
                        <i class="fa-regular fa-copy fs-6 text-muted copy-pin-btn" id="btnCopyPin" title="Copy Today's Manager PIN"></i>
                    </div>
                    <div class="stat-label">Today's Manager PIN</div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4 no-print" id="filterCard">
            <div class="card-body py-3">
                <!-- Top Row: Search, Status, Cash Variance, Per Page, Reset -->
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" id="shiftSearch" class="form-control" placeholder="Search cashier name or shift #...">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <select id="statusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="open">Active / Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select id="varianceFilter" class="form-select">
                            <option value="">All Discrepancies</option>
                            <option value="exact">Balanced (₱0.00)</option>
                            <option value="over">Cash Over (+)</option>
                            <option value="short">Cash Short (-)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select id="shiftPerPage" class="form-select" title="Items to show per page">
                            <option value="10">10 per page</option>
                            <option value="25" selected>25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                            <option value="all">All shifts</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <button class="btn btn-secondary w-100" id="btnResetFilter">
                            <i class="fa-solid fa-rotate-left me-1"></i>Reset
                        </button>
                    </div>
                </div>
                <!-- Bottom Row: Date Range Filter & Quick Presets -->
                <div class="row g-2 align-items-center pt-2 border-top">
                    <div class="col-12 col-md-auto d-flex align-items-center">
                        <span class="small fw-bold text-muted me-2"><i class="fa-regular fa-calendar-days text-emerald me-1"></i>Date Range:</span>
                    </div>
                    <div class="col-6 col-md-auto d-flex align-items-center gap-1">
                        <span class="small text-muted">From</span>
                        <input type="date" id="shiftDateFrom" class="form-control form-control-sm" style="width:140px;" title="Filter start date">
                    </div>
                    <div class="col-6 col-md-auto d-flex align-items-center gap-1">
                        <span class="small text-muted">To</span>
                        <input type="date" id="shiftDateTo" class="form-control form-control-sm" style="width:140px;" title="Filter end date">
                    </div>
                    <div class="col-12 col-md d-flex align-items-center gap-1 flex-wrap justify-content-md-end mt-1 mt-md-0">
                        <span class="small text-muted me-1">Presets:</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setShiftDateRange('today')">Today</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setShiftDateRange('yesterday')">Yesterday</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setShiftDateRange('7days')">Last 7d</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setShiftDateRange('month')">This Month</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="setShiftDateRange('all')">All Time</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINT-ONLY OFFICIAL DOCUMENT HEADER -->
        <div class="print-only-block print-header">
            <div class="print-store-title">Sharon Store</div>
            <div class="print-store-sub">Pampang Public Market, Angeles City, Pampanga</div>
            <div class="print-store-sub">Point of Sale &amp; Inventory Management System</div>
            <div class="print-report-title">Official Cashier Shift &amp; Drawer Reconciliation Report</div>
            
            <table class="print-meta-grid mt-3">
                <tr>
                    <td style="width:50%; text-align:left;">
                        <strong>Date &amp; Time Generated:</strong> <?= date('F d, Y h:i A') ?><br>
                        <strong>Report Classification:</strong> Cash Reconciliation &amp; Audit Log
                    </td>
                    <td style="width:50%; text-align:right;">
                        <strong>Generated By:</strong> <?= htmlspecialchars($currentUser) ?> (<?= ucfirst(htmlspecialchars($currentRole)) ?>)<br>
                        <strong>Reconciliation Status:</strong> System Audited
                    </td>
                </tr>
            </table>

            <!-- Mini KPI Summary Strip -->
            <div class="print-kpi-strip">
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Total Shifts</span>
                    <span class="print-kpi-val"><?= number_format($totalShifts) ?></span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Starting Cash</span>
                    <span class="print-kpi-val">₱<?= number_format($totalStartingCash, 2) ?></span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Expected Cash</span>
                    <span class="print-kpi-val">₱<?= number_format($totalExpectedCash, 2) ?></span>
                </div>
                <div class="print-kpi-item">
                    <span class="print-kpi-label">Net Discrepancy</span>
                    <span class="print-kpi-val <?= $totalVariance < 0 ? 'text-danger' : ($totalVariance > 0 ? 'text-success' : '') ?>">
                        <?= ($totalVariance < 0 ? '-₱' : ($totalVariance > 0 ? '+₱' : '₱')) . number_format(abs($totalVariance), 2) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Shift History Records Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-clock-rotate-left me-2 text-emerald"></i>Shift Log Records
                    </h5>
                    <span class="badge badge-emerald" id="recordCountBadge">
                        <?= number_format($totalShifts) ?> Recorded <?= $totalShifts === 1 ? 'Shift' : 'Shifts' ?>
                    </span>
                </div>
                <!-- Top Pagination Controls -->
                <div class="d-flex align-items-center gap-2" id="shiftsTopPaginationWrapper" style="display:none;">
                    <span class="text-muted small d-none d-md-inline" id="shiftPaginationInfoTop"></span>
                    <nav aria-label="Top Shift history pagination">
                        <ul class="pagination pagination-sm mb-0" id="shiftPaginationUlTop">
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="min-height: 480px;">
                    <table class="table table-custom table-report align-middle mb-0" id="shiftsTable" style="table-layout: fixed; width: 100%; min-width: 1100px;">
                        <thead>
                            <tr>
                                <th style="width: 85px; min-width: 85px;" class="sortable-th">Shift ID <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 170px; min-width: 170px;" class="sortable-th">Cashier <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 155px; min-width: 155px;" class="sortable-th">Start Time <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 155px; min-width: 155px;" class="sortable-th">End Time <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 125px; min-width: 125px;" class="text-end sortable-th">Starting Cash <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 125px; min-width: 125px;" class="text-end sortable-th">Expected Cash <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 125px; min-width: 125px;" class="text-end sortable-th">Actual Cash <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 125px; min-width: 125px;" class="text-end sortable-th">Short / Over <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 105px; min-width: 105px;" class="text-center sortable-th">Status <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th style="width: 80px; min-width: 80px;" class="text-center no-sort">Action</th>
                            </tr>
                        </thead>
                        <tbody id="shiftsTbody">
                            <?php if (empty($shifts)): ?>
                                <tr id="noShiftsRow">
                                    <td colspan="10" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fa-solid fa-clock-rotate-left fa-3x mb-3 d-block text-secondary opacity-50"></i>
                                            <span class="fw-semibold fs-6">No shift records found</span>
                                            <p class="small text-muted mb-0">Shifts will appear here once cashiers start their work sessions.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shifts as $s): 
                                    $variance = $s['short_over'] !== null ? (float)$s['short_over'] : null;
                                    $vType = 'exact';
                                    $vBadgeHtml = '<span class="badge badge-variance exact"><i class="fa-solid fa-circle-check me-1 text-secondary"></i>₱0.00</span>';
                                    
                                    if ($variance !== null) {
                                        if ($variance < 0) {
                                            $vType = 'short';
                                            $vBadgeHtml = '<span class="badge badge-variance short"><i class="fa-solid fa-arrow-trend-down me-1"></i>-₱' . number_format(abs($variance), 2) . '</span>';
                                        } elseif ($variance > 0) {
                                            $vType = 'over';
                                            $vBadgeHtml = '<span class="badge badge-variance over"><i class="fa-solid fa-arrow-trend-up me-1"></i>+₱' . number_format($variance, 2) . '</span>';
                                        }
                                    } else {
                                        $vType = 'none';
                                        $vBadgeHtml = '<span class="text-muted fw-semibold">—</span>';
                                    }

                                    $initial = strtoupper(substr($s['cashier_name'] ?: 'C', 0, 1));
                                    $shiftJson = htmlspecialchars(json_encode([
                                        'shift_id'      => $s['shift_id'],
                                        'cashier_name'  => $s['cashier_name'],
                                        'cashier_user'  => $s['cashier_username'] ?? '',
                                        'start_time'    => $s['start_time'],
                                        'end_time'      => $s['end_time'],
                                        'starting_cash' => $s['starting_cash'],
                                        'expected_cash' => $s['expected_cash'],
                                        'actual_cash'   => $s['actual_cash'],
                                        'short_over'    => $s['short_over'],
                                        'status'        => $s['status']
                                    ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="shift-row"
                                    data-id="<?= $s['shift_id'] ?>"
                                    data-cashier="<?= strtolower(htmlspecialchars($s['cashier_name'] . ' ' . ($s['cashier_username'] ?? ''))) ?>"
                                    data-status="<?= $s['status'] ?>"
                                    data-variance="<?= $vType ?>"
                                    data-date="<?= date('Y-m-d', strtotime($s['start_time'])) ?>">
                                    <td data-sort-value="<?= $s['shift_id'] ?>">
                                        <span class="shift-id-tag">#<?= str_pad((string)$s['shift_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td data-sort-value="<?= htmlspecialchars($s['cashier_name']) ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="cashier-avatar"><?= $initial ?></div>
                                            <div>
                                                <div class="fw-bold text-dark text-nowrap"><?= htmlspecialchars($s['cashier_name']) ?></div>
                                                <div class="text-muted small">@<?= htmlspecialchars($s['cashier_username'] ?? 'cashier') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-sort-value="<?= strtotime($s['start_time']) ?>">
                                        <div class="fw-semibold text-dark text-nowrap"><?= date('M d, Y', strtotime($s['start_time'])) ?></div>
                                        <div class="text-muted small"><i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($s['start_time'])) ?></div>
                                    </td>
                                    <td data-sort-value="<?= $s['end_time'] ? strtotime($s['end_time']) : 0 ?>">
                                        <?php if ($s['end_time']): ?>
                                            <div class="fw-semibold text-dark text-nowrap"><?= date('M d, Y', strtotime($s['end_time'])) ?></div>
                                            <div class="text-muted small"><i class="fa-regular fa-clock me-1"></i><?= date('h:i A', strtotime($s['end_time'])) ?></div>
                                        <?php else: ?>
                                            <span class="badge badge-emerald">
                                                <span class="status-indicator-dot active me-1"></span>Ongoing
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark" data-sort-value="<?= (float)$s['starting_cash'] ?>">
                                        ₱<?= number_format($s['starting_cash'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark" data-sort-value="<?= $s['expected_cash'] !== null ? (float)$s['expected_cash'] : '' ?>">
                                        <?= $s['expected_cash'] !== null ? '₱' . number_format($s['expected_cash'], 2) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td class="text-end font-monospace fw-semibold text-dark" data-sort-value="<?= $s['actual_cash'] !== null ? (float)$s['actual_cash'] : '' ?>">
                                        <?= $s['actual_cash'] !== null ? '₱' . number_format($s['actual_cash'], 2) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td class="text-end" data-sort-value="<?= $variance !== null ? $variance : '' ?>">
                                        <?= $vBadgeHtml ?>
                                    </td>
                                    <td class="text-center" data-sort-value="<?= $s['status'] ?>">
                                        <?php if ($s['status'] === 'open'): ?>
                                            <span class="badge badge-emerald px-2 py-1">
                                                <i class="fa-solid fa-circle-dot me-1 text-success"></i>Open
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary px-2 py-1">
                                                <i class="fa-solid fa-lock me-1"></i>Closed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary py-1 px-2 btn-view-shift" 
                                                data-shift='<?= $shiftJson ?>' 
                                                title="View shift details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot id="shiftsTfoot">
                            <tr style="background:#f8fafc; font-weight:800;">
                                <td colspan="4" class="text-uppercase text-dark">Total Summary</td>
                                <td class="text-end">₱<?= number_format($totalStartingCash, 2) ?></td>
                                <td class="text-end">₱<?= number_format($totalExpectedCash, 2) ?></td>
                                <td class="text-end">₱<?= number_format($totalActualCash, 2) ?></td>
                                <td class="text-end <?= $totalVariance < 0 ? 'text-danger' : ($totalVariance > 0 ? 'text-success' : '') ?>">
                                    <?= ($totalVariance < 0 ? '-₱' : ($totalVariance > 0 ? '+₱' : '₱')) . number_format(abs($totalVariance), 2) ?>
                                </td>
                                <td class="text-center"><?= $totalShifts ?> Shifts</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <!-- Shifts Table Pagination Footer -->
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 no-print" id="shiftsPaginationContainer">
                <div class="text-muted small" id="shiftPaginationInfo">
                    Showing 0 to 0 of 0 shifts
                </div>
                <nav aria-label="Shift history pagination">
                    <ul class="pagination pagination-sm mb-0" id="shiftPaginationUl">
                    </ul>
                </nav>
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

<!-- Shift Details Modal -->
<div class="modal fade" id="shiftDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-receipt me-2 text-emerald"></i>Shift Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="shiftDetailBody">
                <!-- Dynamically populated by JS -->
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i>Print Summary
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js?v=35"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Copy PIN to clipboard
    const btnCopyPin = document.getElementById('btnCopyPin');
    if (btnCopyPin) {
        btnCopyPin.addEventListener('click', () => {
            const pinText = '<?= htmlspecialchars($dailyPin) ?>';
            navigator.clipboard.writeText(pinText).then(() => {
                const originalClass = btnCopyPin.className;
                btnCopyPin.className = 'fa-solid fa-check fs-6 text-success copy-pin-btn';
                setTimeout(() => {
                    btnCopyPin.className = originalClass;
                }, 1800);
            });
        });
    }

    // Filter & Pagination functionality
    const searchInput = document.getElementById('shiftSearch');
    const statusFilter = document.getElementById('statusFilter');
    const varianceFilter = document.getElementById('varianceFilter');
    const perPageSelect = document.getElementById('shiftPerPage');
    const dateFromInput = document.getElementById('shiftDateFrom');
    const dateToInput = document.getElementById('shiftDateTo');
    const btnReset = document.getElementById('btnResetFilter');
    const rows = Array.from(document.querySelectorAll('.shift-row'));
    const recordCountBadge = document.getElementById('recordCountBadge');
    const paginationInfo = document.getElementById('shiftPaginationInfo');
    const paginationUl = document.getElementById('shiftPaginationUl');
    const paginationInfoTop = document.getElementById('shiftPaginationInfoTop');
    const paginationUlTop = document.getElementById('shiftPaginationUlTop');
    const topPaginationWrapper = document.getElementById('shiftsTopPaginationWrapper');

    let currentShiftPage = 1;

    function applyFilters(resetPage = false) {
        if (resetPage) currentShiftPage = 1;

        const query = (searchInput?.value || '').trim().toLowerCase();
        const selectedStatus = statusFilter?.value || '';
        const selectedVariance = varianceFilter?.value || '';
        const fromDate = dateFromInput?.value || '';
        const toDate = dateToInput?.value || '';
        const perPageVal = perPageSelect?.value || '25';

        const matchedRows = [];

        rows.forEach(row => {
            const id = row.getAttribute('data-id') || '';
            const paddedId = '#' + id.padStart(4, '0');
            const cashier = row.getAttribute('data-cashier') || '';
            const status = row.getAttribute('data-status') || '';
            const variance = row.getAttribute('data-variance') || '';
            const rowDate = row.getAttribute('data-date') || '';

            const matchQuery = !query || id.includes(query) || paddedId.toLowerCase().includes(query) || cashier.includes(query);
            const matchStatus = !selectedStatus || status === selectedStatus;
            const matchVariance = !selectedVariance || variance === selectedVariance;
            const matchDateFrom = !fromDate || rowDate >= fromDate;
            const matchDateTo = !toDate || rowDate <= toDate;

            if (matchQuery && matchStatus && matchVariance && matchDateFrom && matchDateTo) {
                row.classList.remove('filter-hidden');
                matchedRows.push(row);
            } else {
                row.classList.add('filter-hidden');
                row.classList.remove('page-hidden');
            }
        });

        // Toggle no-results row
        let emptyRow = document.getElementById('filterEmptyRow');
        if (matchedRows.length === 0 && rows.length > 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'filterEmptyRow';
                emptyRow.innerHTML = '<td colspan="10" class="text-center py-4 text-muted"><i class="fa-solid fa-filter-circle-xmark me-2"></i>No shifts match the current filters.</td>';
                document.getElementById('shiftsTbody').appendChild(emptyRow);
            }
            emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }

        // Calculate Pagination
        const totalMatched = matchedRows.length;
        let totalPages = 1;
        let limit = totalMatched;

        if (perPageVal !== 'all') {
            limit = parseInt(perPageVal, 10) || 25;
            totalPages = Math.max(1, Math.ceil(totalMatched / limit));
        }

        if (currentShiftPage > totalPages) currentShiftPage = totalPages;
        if (currentShiftPage < 1) currentShiftPage = 1;

        // Apply page-hidden to matched rows
        if (perPageVal === 'all') {
            matchedRows.forEach(r => r.classList.remove('page-hidden'));
        } else {
            matchedRows.forEach((r, idx) => {
                const pageNumber = Math.floor(idx / limit) + 1;
                if (pageNumber === currentShiftPage) {
                    r.classList.remove('page-hidden');
                } else {
                    r.classList.add('page-hidden');
                }
            });
        }

        // Badge update
        if (recordCountBadge) {
            recordCountBadge.textContent = `${totalMatched} Recorded ${totalMatched === 1 ? 'Shift' : 'Shifts'}`;
        }

        // Pagination Info (Top & Bottom)
        let infoStr = '';
        if (totalMatched === 0) {
            infoStr = 'Showing 0 of 0 shifts';
        } else if (perPageVal === 'all') {
            infoStr = `Showing all ${totalMatched} shifts` + (totalMatched !== rows.length ? ` (filtered from ${rows.length})` : '');
        } else {
            const start = (currentShiftPage - 1) * limit + 1;
            const end = Math.min(totalMatched, currentShiftPage * limit);
            infoStr = `Showing ${start} to ${end} of ${totalMatched} shifts` + (totalMatched !== rows.length ? ` (filtered from ${rows.length})` : '');
        }

        if (paginationInfo) paginationInfo.textContent = infoStr;
        if (paginationInfoTop) paginationInfoTop.textContent = infoStr;

        // Render Pagination UI
        renderShiftPagination(totalPages);
    }

    function goToShiftPage(targetPage) {
        currentShiftPage = targetPage;
        applyFilters(false);
    }

    function buildShiftPaginationUl(containerUl, totalPages) {
        if (!containerUl) return;
        containerUl.innerHTML = '';

        if (totalPages <= 1) {
            return;
        }

        // Prev button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentShiftPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="javascript:void(0)" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></a>`;
        if (currentShiftPage > 1) {
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                goToShiftPage(currentShiftPage - 1);
            });
        }
        containerUl.appendChild(prevLi);

        // Page buttons (sliding window)
        const maxVisible = 5;
        let startPage = Math.max(1, currentShiftPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        if (startPage > 1) {
            const p1 = document.createElement('li');
            p1.className = 'page-item';
            p1.innerHTML = `<a class="page-link" href="javascript:void(0)">1</a>`;
            p1.addEventListener('click', (e) => {
                e.preventDefault();
                goToShiftPage(1);
            });
            containerUl.appendChild(p1);

            if (startPage > 2) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = `<span class="page-link">&hellip;</span>`;
                containerUl.appendChild(dots);
            }
        }

        for (let p = startPage; p <= endPage; p++) {
            const li = document.createElement('li');
            li.className = `page-item ${p === currentShiftPage ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="javascript:void(0)">${p}</a>`;
            const targetP = p;
            li.addEventListener('click', (e) => {
                e.preventDefault();
                goToShiftPage(targetP);
            });
            containerUl.appendChild(li);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = `<span class="page-link">&hellip;</span>`;
                containerUl.appendChild(dots);
            }
            const pLast = document.createElement('li');
            pLast.className = 'page-item';
            pLast.innerHTML = `<a class="page-link" href="javascript:void(0)">${totalPages}</a>`;
            pLast.addEventListener('click', (e) => {
                e.preventDefault();
                goToShiftPage(totalPages);
            });
            containerUl.appendChild(pLast);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentShiftPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="javascript:void(0)" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></a>`;
        if (currentShiftPage < totalPages) {
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                goToShiftPage(currentShiftPage + 1);
            });
        }
        containerUl.appendChild(nextLi);
    }

    function renderShiftPagination(totalPages) {
        buildShiftPaginationUl(paginationUl, totalPages);
        buildShiftPaginationUl(paginationUlTop, totalPages);

        if (topPaginationWrapper) {
            topPaginationWrapper.style.display = totalPages <= 1 ? 'none' : 'flex';
        }
    }

    // Quick Date Range Preset Helper
    window.setShiftDateRange = function(range) {
        const today = new Date();
        const fromInput = document.getElementById('shiftDateFrom');
        const toInput = document.getElementById('shiftDateTo');
        if (!fromInput || !toInput) return;

        function fmt(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        if (range === 'today') {
            fromInput.value = fmt(today);
            toInput.value = fmt(today);
        } else if (range === 'yesterday') {
            const yest = new Date(today);
            yest.setDate(yest.getDate() - 1);
            fromInput.value = fmt(yest);
            toInput.value = fmt(yest);
        } else if (range === '7days') {
            const d7 = new Date(today);
            d7.setDate(d7.getDate() - 6);
            fromInput.value = fmt(d7);
            toInput.value = fmt(today);
        } else if (range === 'month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            fromInput.value = fmt(firstDay);
            toInput.value = fmt(today);
        } else if (range === 'all') {
            fromInput.value = '';
            toInput.value = '';
        }
        applyFilters(true);
    };

    searchInput?.addEventListener('input', () => applyFilters(true));
    statusFilter?.addEventListener('change', () => applyFilters(true));
    varianceFilter?.addEventListener('change', () => applyFilters(true));
    perPageSelect?.addEventListener('change', () => applyFilters(true));
    dateFromInput?.addEventListener('change', () => applyFilters(true));
    dateToInput?.addEventListener('change', () => applyFilters(true));

    btnReset?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (varianceFilter) varianceFilter.value = '';
        if (dateFromInput) dateFromInput.value = '';
        if (dateToInput) dateToInput.value = '';
        if (perPageSelect) perPageSelect.value = '25';
        applyFilters(true);
    });

    // Run on initial page load
    applyFilters(true);

    // View Shift Details Modal
    const detailModalEl = document.getElementById('shiftDetailModal');
    const detailModal = detailModalEl ? new bootstrap.Modal(detailModalEl) : null;
    const detailBody = document.getElementById('shiftDetailBody');

    document.querySelectorAll('.btn-view-shift').forEach(btn => {
        btn.addEventListener('click', () => {
            const raw = btn.getAttribute('data-shift');
            if (!raw || !detailBody) return;
            const s = JSON.parse(raw);

            const formatMoney = val => val !== null && val !== undefined ? '₱' + parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
            const formatDate = str => {
                if (!str) return '—';
                const d = new Date(str.replace(' ', 'T'));
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            };

            let varianceBadge = '<span class="badge badge-variance exact">₱0.00 (Balanced)</span>';
            if (s.short_over !== null) {
                const diff = parseFloat(s.short_over);
                if (diff < 0) {
                    varianceBadge = `<span class="badge badge-variance short"><i class="fa-solid fa-arrow-trend-down me-1"></i>-₱${Math.abs(diff).toFixed(2)} (Short)</span>`;
                } else if (diff > 0) {
                    varianceBadge = `<span class="badge badge-variance over"><i class="fa-solid fa-arrow-trend-up me-1"></i>+₱${diff.toFixed(2)} (Over)</span>`;
                }
            }

            const statusBadge = s.status === 'open' 
                ? '<span class="badge badge-emerald"><i class="fa-solid fa-circle-dot me-1 text-success"></i>Open</span>'
                : '<span class="badge badge-secondary"><i class="fa-solid fa-lock me-1"></i>Closed</span>';

            detailBody.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div>
                        <span class="shift-id-tag me-2">#${String(s.shift_id).padStart(4, '0')}</span>
                        <strong>${s.cashier_name}</strong>
                    </div>
                    <div>${statusBadge}</div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Clock In:</span>
                    <span class="detail-val">${formatDate(s.start_time)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Clock Out:</span>
                    <span class="detail-val">${s.end_time ? formatDate(s.end_time) : '<span class="text-success fw-bold">Active / Ongoing</span>'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Starting Cash Float:</span>
                    <span class="detail-val font-monospace">${formatMoney(s.starting_cash)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expected Drawer Cash:</span>
                    <span class="detail-val font-monospace">${formatMoney(s.expected_cash)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Actual Counted Cash:</span>
                    <span class="detail-val font-monospace">${formatMoney(s.actual_cash)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Cash Variance:</span>
                    <span class="detail-val">${varianceBadge}</span>
                </div>
            `;

            detailModal?.show();
        });
    });
});
</script>
</body>
</html>
