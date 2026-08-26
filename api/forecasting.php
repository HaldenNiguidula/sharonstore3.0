<?php
// ============================================================
// Forecasting API — Sharon Store System
// SMA + WMA Sales Forecasting
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

requireAdmin();
header('Content-Type: application/json');

function respond(bool $s, string $m = '', mixed $d = null): never {
    echo json_encode(['success' => $s, 'message' => $m, 'data' => $d]);
    exit();
}

$action = $_GET['action'] ?? '';
$db     = getDB();

// ── Items List ────────────────────────────────────────────────
if ($action === 'items_list') {
    $stmt = $db->query("SELECT item_id, item_name, stock_qty, unit FROM tbl_inventory WHERE is_active = 1 ORDER BY item_name ASC");
    respond(true, 'OK', $stmt->fetchAll());
}

// ── Forecast Overview (all items) ────────────────────────────
if ($action === 'forecast_report') {
    $stmt = $db->query("
        SELECT i.item_id, i.item_name, c.category_name, i.stock_qty, i.unit,
               f_sma.predicted_qty AS sma_forecast,
               f_wma.predicted_qty AS wma_forecast,
               f_sma.forecast_period AS forecast_period
        FROM tbl_inventory i
        JOIN tbl_categories c ON c.category_id = i.category_id
        LEFT JOIN tbl_forecasts f_sma ON f_sma.item_id = i.item_id AND f_sma.method = 'SMA'
            AND f_sma.forecast_id = (SELECT MAX(f2.forecast_id) FROM tbl_forecasts f2 WHERE f2.item_id = i.item_id AND f2.method = 'SMA')
        LEFT JOIN tbl_forecasts f_wma ON f_wma.item_id = i.item_id AND f_wma.method = 'WMA'
            AND f_wma.forecast_id = (SELECT MAX(f3.forecast_id) FROM tbl_forecasts f3 WHERE f3.item_id = i.item_id AND f3.method = 'WMA')
        WHERE i.is_active = 1
        ORDER BY i.item_name ASC
        LIMIT 100
    ");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $sma = (float)($row['sma_forecast'] ?? 0);
        $wma = (float)($row['wma_forecast'] ?? 0);
        $qty = (float)$row['stock_qty'];
        $row['sma_restock']  = max(0, round($sma - $qty, 2));
        $row['wma_restock']  = max(0, round($wma - $qty, 2));
        $row['needs_restock']= ($row['sma_restock'] > 0 || $row['wma_restock'] > 0);
    }
    unset($row);
    respond(true, 'OK', $rows);
}

// ── Individual Forecast ───────────────────────────────────────
if ($action === 'forecast') {
    $itemId  = (int)($_GET['item_id']  ?? 0);
    $periods = (int)($_GET['periods']  ?? 6);   // number of months lookback (3, 6, 12)

    if (!$itemId) respond(false, 'item_id is required.', null);
    $periods = in_array($periods, [3,6,12], true) ? $periods : 6;

    // Get item details
    $itemStmt = $db->prepare("SELECT item_id, item_name, stock_qty, unit FROM tbl_inventory WHERE item_id = :id AND is_active = 1");
    $itemStmt->execute([':id' => $itemId]);
    $item = $itemStmt->fetch();
    if (!$item) respond(false, 'Item not found.', null);

    // Get monthly historical sales for this item
    $histStmt = $db->prepare("
        SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') AS month_key,
               DATE_FORMAT(t.transaction_date, '%b %Y') AS month_label,
               COALESCE(SUM(d.quantity), 0)             AS qty_sold
        FROM tbl_transaction_details d
        JOIN tbl_transactions t ON t.transaction_id = d.transaction_id
        WHERE d.item_id = :id
          AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL :periods MONTH)
          AND (t.status IS NULL OR t.status != 'voided')
        GROUP BY month_key, month_label
        ORDER BY month_key ASC
    ");
    $histStmt->execute([':id' => $itemId, ':periods' => $periods]);
    $history = $histStmt->fetchAll();

    // Build array of qty values
    $qtys = array_map(fn($r) => (float)$r['qty_sold'], $history);
    $n    = count($qtys);

    // ── SMA: Simple Moving Average ──────────────────────────
    $sma_forecast = $n > 0 ? round(array_sum($qtys) / $n, 2) : 0;

    // ── WMA: Weighted Moving Average ────────────────────────
    // Most recent month gets weight N, oldest gets weight 1
    $wma_numerator   = 0;
    $wma_denominator = 0;
    foreach ($qtys as $i => $qty) {
        $weight           = $i + 1;                   // 1-based, oldest first
        $wma_numerator   += $weight * $qty;
        $wma_denominator += $weight;
    }
    $wma_forecast = $wma_denominator > 0 ? round($wma_numerator / $wma_denominator, 2) : 0;

    $currentStock  = (float)$item['stock_qty'];
    $sma_restock   = max(0, round($sma_forecast - $currentStock, 2));
    $wma_restock   = max(0, round($wma_forecast - $currentStock, 2));

    // Save to tbl_forecasts
    $nextMonth = date('Y-m-01', strtotime('first day of next month'));
    $db->prepare("DELETE FROM tbl_forecasts WHERE item_id = :id AND forecast_period = :period AND method = 'SMA'")->execute([':id'=>$itemId,':period'=>$nextMonth]);
    $db->prepare("DELETE FROM tbl_forecasts WHERE item_id = :id AND forecast_period = :period AND method = 'WMA'")->execute([':id'=>$itemId,':period'=>$nextMonth]);
    $ins = $db->prepare("INSERT INTO tbl_forecasts (item_id, forecast_period, predicted_qty, method) VALUES (:id,:period,:qty,:method)");
    $ins->execute([':id'=>$itemId, ':period'=>$nextMonth, ':qty'=>$sma_forecast, ':method'=>'SMA']);
    $ins->execute([':id'=>$itemId, ':period'=>$nextMonth, ':qty'=>$wma_forecast, ':method'=>'WMA']);

    respond(true, 'Forecast generated.', [
        'item_id'       => $itemId,
        'item_name'     => $item['item_name'],
        'unit'          => $item['unit'],
        'current_stock' => $currentStock,
        'periods'       => $periods,
        'history'       => $history,
        'qtys'          => $qtys,
        'sma_forecast'  => $sma_forecast,
        'wma_forecast'  => $wma_forecast,
        'sma_restock'   => $sma_restock,
        'wma_restock'   => $wma_restock,
        'next_period'   => date('F Y', strtotime('first day of next month')),
    ]);
}

respond(false, 'Unknown action.');
