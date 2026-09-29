<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin();
header('Content-Type: application/json');

function respond(bool $s, string $m = '', mixed $d = null) {
    echo json_encode(['success' => $s, 'message' => $m, 'data' => $d]);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$db = getDB();
$userId = currentUserId();

// Ensure daily pin exists for today
$dailyPin = getDailyManagerPIN($db);

if ($action === 'check') {
    // Check if the current user has an open shift
    $stmt = $db->prepare("SELECT * FROM tbl_shifts WHERE cashier_id = ? AND status = 'open' ORDER BY shift_id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $shift = $stmt->fetch();

    if ($shift) {
        respond(true, 'Shift is open', ['shift_id' => $shift['shift_id'], 'starting_cash' => $shift['starting_cash']]);
    } else {
        respond(false, 'No open shift');
    }
}

if ($action === 'start') {
    $pin = trim($_POST['pin'] ?? '');
    $startingCash = (float)($_POST['starting_cash'] ?? 0);

    if ($pin !== $dailyPin) {
        respond(false, 'Invalid Manager PIN.');
    }

    if ($startingCash < 0) {
        respond(false, 'Starting cash cannot be negative.');
    }

    // Check if they already have an open shift
    $stmt = $db->prepare("SELECT shift_id FROM tbl_shifts WHERE cashier_id = ? AND status = 'open'");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        respond(false, 'You already have an open shift.');
    }

    $stmt = $db->prepare("INSERT INTO tbl_shifts (cashier_id, starting_cash, status) VALUES (?, ?, 'open')");
    if ($stmt->execute([$userId, $startingCash])) {
        $shiftId = $db->lastInsertId();
        respond(true, 'Shift started successfully.', ['shift_id' => $shiftId]);
    } else {
        respond(false, 'Failed to start shift.');
    }
}

if ($action === 'end') {
    $pin = trim($_POST['pin'] ?? '');
    $actualCash = (float)($_POST['actual_cash'] ?? 0);

    if ($pin !== $dailyPin) {
        respond(false, 'Invalid Manager PIN.');
    }

    if ($actualCash < 0) {
        respond(false, 'Actual cash cannot be negative.');
    }

    // Get current open shift
    $stmt = $db->prepare("SELECT * FROM tbl_shifts WHERE cashier_id = ? AND status = 'open' ORDER BY shift_id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $shift = $stmt->fetch();

    if (!$shift) {
        respond(false, 'No open shift found.');
    }

    $shiftId = $shift['shift_id'];
    $startingCash = (float)$shift['starting_cash'];

    // Calculate total cash sales during this shift (ignoring e-wallets, credit cards etc if implemented, but currently we only have total_amount)
    // In this basic version, all sales are cash.
    $stmt = $db->prepare("SELECT SUM(total_amount) FROM tbl_transactions WHERE shift_id = ? AND (status IS NULL OR status != 'voided')");
    $stmt->execute([$shiftId]);
    $totalSales = (float)$stmt->fetchColumn();

    $expectedCash = $startingCash + $totalSales;
    $shortOver = $actualCash - $expectedCash;

    $stmt = $db->prepare("UPDATE tbl_shifts SET end_time = CURRENT_TIMESTAMP, expected_cash = ?, actual_cash = ?, short_over = ?, status = 'closed' WHERE shift_id = ?");
    
    if ($stmt->execute([$expectedCash, $actualCash, $shortOver, $shiftId])) {
        respond(true, 'Shift closed successfully.', [
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'short_over' => $shortOver
        ]);
    } else {
        respond(false, 'Failed to close shift.');
    }
}

if ($action === 'summary') {
    // Get current open shift
    $stmt = $db->prepare("SELECT * FROM tbl_shifts WHERE cashier_id = ? AND status = 'open' ORDER BY shift_id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $shift = $stmt->fetch();

    if (!$shift) {
        respond(false, 'No open shift found.');
    }

    $shiftId = $shift['shift_id'];
    $startingCash = (float)$shift['starting_cash'];

    $stmt = $db->prepare("SELECT COUNT(*) as txn_count, COALESCE(SUM(total_amount), 0) as total_sales FROM tbl_transactions WHERE shift_id = ? AND (status IS NULL OR status != 'voided')");
    $stmt->execute([$shiftId]);
    $totals = $stmt->fetch();

    $totalSales = (float)$totals['total_sales'];
    $txnCount = (int)$totals['txn_count'];
    $expectedCash = $startingCash + $totalSales;

    respond(true, 'Shift summary retrieved.', [
        'start_time' => $shift['start_time'],
        'starting_cash' => $startingCash,
        'txn_count' => $txnCount,
        'total_sales' => $totalSales,
        'expected_cash' => $expectedCash
    ]);
}

respond(false, 'Unknown action.');
