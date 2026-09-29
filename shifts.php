<?php
require_once __DIR__ . '/includes/auth_check.php';
requireAdmin();
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Shift History';
$db = getDB();

$stmt = $db->query("
    SELECT s.*, u.full_name as cashier_name
    FROM tbl_shifts s
    JOIN tbl_users u ON u.user_id = s.cashier_id
    ORDER BY s.shift_id DESC
");
$shifts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift History - Sharon Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/sharonstore3.0/assets/css/style.css?v=17">
<style>
.badge-variance {
    font-weight: 700;
    padding: 0.4em 0.8em;
}
.badge-variance.over { background: #dcfce7; color: #166534; }
.badge-variance.short { background: #fee2e2; color: #991b1b; }
.badge-variance.exact { background: #f1f5f9; color: #475569; }
@media(max-width:767px){
  .page-header{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;}
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
                <ul class="breadcrumb-custom"><li><a href="/sharonstore3.0/dashboard.php">Home</a></li><li class="active">Shift History</li></ul>
                <h1 class="page-header-title"><i class="fa-solid fa-clock-rotate-left me-2 text-emerald"></i>Shift History</h1>
                <p class="page-header-subtitle">Review cashier shifts, starting cash, and end-of-day discrepancies.</p>
            </div>
            <div>
                <div class="card shadow-sm border-0 bg-white px-4 py-2">
                    <div class="text-muted fw-bold" style="font-size:0.8rem">TODAY'S MANAGER PIN</div>
                    <div class="text-primary font-monospace fw-bold text-center fs-4"><?= htmlspecialchars(getDailyManagerPIN($db)) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Shift ID</th>
                                <th>Cashier</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th class="text-end">Starting Cash</th>
                                <th class="text-end">Expected</th>
                                <th class="text-end">Actual</th>
                                <th class="text-end">Short/Over</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shifts)): ?>
                                <tr><td colspan="9" class="text-center py-4 text-muted">No shifts recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($shifts as $s): 
                                    $variance = $s['short_over'] !== null ? (float)$s['short_over'] : null;
                                    $vClass = 'exact'; $vText = '₱0.00';
                                    if ($variance !== null) {
                                        if ($variance < 0) { $vClass = 'short'; $vText = '-₱' . number_format(abs($variance), 2); }
                                        elseif ($variance > 0) { $vClass = 'over'; $vText = '+₱' . number_format($variance, 2); }
                                    }
                                ?>
                                <tr>
                                    <td class="fw-bold text-muted">#<?= $s['shift_id'] ?></td>
                                    <td class="fw-bold"><i class="fa-solid fa-user text-muted me-2"></i><?= htmlspecialchars($s['cashier_name']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($s['start_time'])) ?></td>
                                    <td><?= $s['end_time'] ? date('M d, Y h:i A', strtotime($s['end_time'])) : '<span class="text-muted">Ongoing</span>' ?></td>
                                    <td class="text-end fw-600">₱<?= number_format($s['starting_cash'], 2) ?></td>
                                    <td class="text-end fw-600"><?= $s['expected_cash'] !== null ? '₱'.number_format($s['expected_cash'], 2) : '-' ?></td>
                                    <td class="text-end fw-600"><?= $s['actual_cash'] !== null ? '₱'.number_format($s['actual_cash'], 2) : '-' ?></td>
                                    <td class="text-end">
                                        <?php if ($variance !== null): ?>
                                            <span class="badge badge-variance <?= $vClass ?>"><?= $vText ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($s['status'] === 'open'): ?>
                                            <span class="badge bg-primary">Open</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="/sharonstore3.0/assets/js/app.js"></script>
</body>
</html>
