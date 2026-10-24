<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Shift History';
$db = getDB();

$stmt = $db->query("
    SELECT s.*, u.full_name as cashier_name, u.username as cashier_username
    FROM tbl_shifts s
    JOIN tbl_users u ON u.user_id = s.cashier_id
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
    #shiftsTable th:last-child,
    #shiftsTable td:last-child {
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
        <div class="card mb-4" id="filterCard">
            <div class="card-body py-3">
                <div class="row g-3 align-items-center mb-0">
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" id="shiftSearch" class="form-control" placeholder="Search cashier name or shift #...">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="statusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="open">Active / Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="varianceFilter" class="form-select">
                            <option value="">All Cash Variances</option>
                            <option value="exact">Balanced (₱0.00)</option>
                            <option value="over">Cash Over (+)</option>
                            <option value="short">Cash Short (-)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn btn-secondary w-100" id="btnResetFilter">
                            <i class="fa-solid fa-rotate-left me-1"></i>Reset
                        </button>
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
            <div class="card-header d-flex justify-content-between align-items-center no-print">
                <h5 class="mb-0">
                    <i class="fa-solid fa-clock-rotate-left me-2 text-emerald"></i>Shift Log Records
                </h5>
                <span class="badge badge-emerald" id="recordCountBadge">
                    <?= number_format($totalShifts) ?> Recorded <?= $totalShifts === 1 ? 'Shift' : 'Shifts' ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-report align-middle mb-0" id="shiftsTable">
                        <thead>
                            <tr>
                                <th style="width: 80px;" class="sortable-th">Shift ID <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="sortable-th">Cashier <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="sortable-th">Start Time <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="sortable-th">End Time <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-end sortable-th">Starting Cash <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-end sortable-th">Expected Cash <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-end sortable-th">Actual Cash <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-end sortable-th">Short / Over <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-center sortable-th" style="width: 100px;">Status <i class="fa-solid fa-sort sort-icon no-print"></i></th>
                                <th class="text-center no-sort" style="width: 80px;">Action</th>
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
                                    data-variance="<?= $vType ?>">
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

    // Filter functionality
    const searchInput = document.getElementById('shiftSearch');
    const statusFilter = document.getElementById('statusFilter');
    const varianceFilter = document.getElementById('varianceFilter');
    const btnReset = document.getElementById('btnResetFilter');
    const rows = document.querySelectorAll('.shift-row');
    const recordCountBadge = document.getElementById('recordCountBadge');

    function applyFilters() {
        const query = (searchInput.value || '').trim().toLowerCase();
        const selectedStatus = statusFilter.value;
        const selectedVariance = varianceFilter.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const id = row.getAttribute('data-id') || '';
            const paddedId = '#' + id.padStart(4, '0');
            const cashier = row.getAttribute('data-cashier') || '';
            const status = row.getAttribute('data-status') || '';
            const variance = row.getAttribute('data-variance') || '';

            const matchQuery = !query || id.includes(query) || paddedId.toLowerCase().includes(query) || cashier.includes(query);
            const matchStatus = !selectedStatus || status === selectedStatus;
            const matchVariance = !selectedVariance || variance === selectedVariance;

            if (matchQuery && matchStatus && matchVariance) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle no-results row
        let emptyRow = document.getElementById('filterEmptyRow');
        if (visibleCount === 0 && rows.length > 0) {
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

        if (recordCountBadge) {
            recordCountBadge.textContent = `${visibleCount} Recorded ${visibleCount === 1 ? 'Shift' : 'Shifts'}`;
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    varianceFilter?.addEventListener('change', applyFilters);

    btnReset?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (varianceFilter) varianceFilter.value = '';
        applyFilters();
    });

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
