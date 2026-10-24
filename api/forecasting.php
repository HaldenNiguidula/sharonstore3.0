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

/**
 * Check if the unit represents discrete countable items (pieces, packs, etc.)
 */
function isPieceUnit(?string $unit): bool {
    $u = strtolower(trim((string)$unit));
    return in_array($u, ['pieces', 'pcs', 'piece', 'packs', 'pack', 'bottles', 'cans', 'boxes', 'sachets', 'items', 'units'], true);
}

$action = $_GET['action'] ?? '';
$db     = getDB();

// ── Items List ────────────────────────────────────────────────
if ($action === 'items_list') {
    try {
        $stmt = $db->query("SELECT i.item_id, i.barcode, i.item_name, c.category_name, i.stock_qty, i.unit FROM tbl_inventory i LEFT JOIN tbl_categories c ON c.category_id = i.category_id WHERE i.is_active = 1 ORDER BY i.item_name ASC");
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $isPiece = isPieceUnit($item['unit'] ?? '');
            $qty = (float)$item['stock_qty'];
            $item['stock_qty'] = ($isPiece || $qty == (int)$qty) ? (int)$qty : $qty;
        }
        unset($item);
        respond(true, 'OK', $items);
    } catch (Throwable $e) {
        respond(false, 'Database error: ' . $e->getMessage());
    }
}

// ── Forecast Overview (all items) ────────────────────────────
if ($action === 'forecast_report') {
    try {
        // Safe standard derived table join - avoid correlated subqueries inside LEFT JOIN ON condition
        $sql = "
            SELECT i.item_id, i.item_name, c.category_name, i.stock_qty, i.unit,
                   f_sma.predicted_qty AS sma_forecast,
                   f_wma.predicted_qty AS wma_forecast,
                   f_sma.forecast_period AS forecast_period
            FROM tbl_inventory i
            LEFT JOIN tbl_categories c ON c.category_id = i.category_id
            LEFT JOIN (
                SELECT f1.item_id, f1.predicted_qty, f1.forecast_period
                FROM tbl_forecasts f1
                INNER JOIN (
                    SELECT item_id, MAX(forecast_id) AS max_id
                    FROM tbl_forecasts
                    WHERE method = 'SMA'
                    GROUP BY item_id
                ) m_sma ON f1.forecast_id = m_sma.max_id
            ) f_sma ON f_sma.item_id = i.item_id
            LEFT JOIN (
                SELECT f2.item_id, f2.predicted_qty
                FROM tbl_forecasts f2
                INNER JOIN (
                    SELECT item_id, MAX(forecast_id) AS max_id
                    FROM tbl_forecasts
                    WHERE method = 'WMA'
                    GROUP BY item_id
                ) m_wma ON f2.forecast_id = m_wma.max_id
            ) f_wma ON f_wma.item_id = i.item_id
            WHERE i.is_active = 1
            ORDER BY i.item_name ASC
            LIMIT 100
        ";
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $isPiece = isPieceUnit($row['unit'] ?? '');
            $sma = $row['sma_forecast'] !== null ? (float)$row['sma_forecast'] : null;
            $wma = $row['wma_forecast'] !== null ? (float)$row['wma_forecast'] : null;
            $qty = (float)$row['stock_qty'];

            if ($isPiece) {
                $row['stock_qty']    = (int)round($qty);
                $row['sma_forecast'] = $sma !== null ? (int)round($sma) : null;
                $row['wma_forecast'] = $wma !== null ? (int)round($wma) : null;
                $smaVal = $row['sma_forecast'] ?? 0;
                $wmaVal = $row['wma_forecast'] ?? 0;
                $row['sma_restock']  = max(0, (int)($smaVal - $row['stock_qty']));
                $row['wma_restock']  = max(0, (int)($wmaVal - $row['stock_qty']));
            } else {
                $row['stock_qty']    = ($qty == (int)$qty) ? (int)$qty : round($qty, 2);
                $row['sma_forecast'] = $sma !== null ? (($sma == (int)$sma) ? (int)$sma : round($sma, 2)) : null;
                $row['wma_forecast'] = $wma !== null ? (($wma == (int)$wma) ? (int)$wma : round($wma, 2)) : null;
                $smaVal = $sma ?? 0;
                $wmaVal = $wma ?? 0;
                $row['sma_restock']  = max(0, round($smaVal - $qty, 2));
                $row['wma_restock']  = max(0, round($wmaVal - $qty, 2));
                if ($row['sma_restock'] == (int)$row['sma_restock']) $row['sma_restock'] = (int)$row['sma_restock'];
                if ($row['wma_restock'] == (int)$row['wma_restock']) $row['wma_restock'] = (int)$row['wma_restock'];
            }
            $row['needs_restock'] = ($row['sma_restock'] > 0 || $row['wma_restock'] > 0);
        }
        unset($row);
        respond(true, 'OK', $rows);
    } catch (Throwable $e) {
        respond(false, 'Database error: ' . $e->getMessage());
    }
}

// ── Individual Forecast ───────────────────────────────────────
if ($action === 'forecast') {
    $itemId  = (int)($_GET['item_id']  ?? 0);
    $periods = (int)($_GET['periods']  ?? 6);   // number of months lookback (3, 6, 12)

    if (!$itemId) respond(false, 'item_id is required.', null);
    $periods = in_array($periods, [3,6,12], true) ? $periods : 6;

    try {
        // Get item details
        $itemStmt = $db->prepare("SELECT item_id, item_name, stock_qty, unit FROM tbl_inventory WHERE item_id = :id AND is_active = 1");
        $itemStmt->execute([':id' => $itemId]);
        $item = $itemStmt->fetch();
        if (!$item) respond(false, 'Item not found.', null);

        // Get monthly historical sales for this item
        // Note: $periods is directly interpolated as validated integer (MySQL/TiDB syntax forbids binding parameters to INTERVAL)
        $histStmt = $db->prepare("
            SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') AS month_key,
                   DATE_FORMAT(t.transaction_date, '%b %Y') AS month_label,
                   COALESCE(SUM(d.quantity), 0)             AS qty_sold
            FROM tbl_transaction_details d
            JOIN tbl_transactions t ON t.transaction_id = d.transaction_id
            WHERE d.item_id = :id
              AND (t.status IS NULL OR t.status != 'voided')
            GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m'), DATE_FORMAT(t.transaction_date, '%b %Y')
            ORDER BY month_key DESC
            LIMIT {$periods}
        ");
        $histStmt->execute([':id' => $itemId]);
        $rawHistory = $histStmt->fetchAll();

        // Reverse so history goes chronologically (oldest to newest) for SMA/WMA
        $history = array_reverse($rawHistory);

        // Build array of qty values
        $qtys = array_map(fn($r) => (float)$r['qty_sold'], $history);
        $n    = count($qtys);

        $isPiece = isPieceUnit($item['unit'] ?? '');
        $currentStock = (float)$item['stock_qty'];

        // ── SMA & WMA Calculations ──────────────────────────────
        $wma_numerator   = 0;
        $wma_denominator = 0;
        foreach ($qtys as $i => $qty) {
            $weight           = $i + 1;                   // 1-based, oldest first
            $wma_numerator   += $weight * $qty;
            $wma_denominator += $weight;
        }

        if ($isPiece) {
            $sma_forecast = $n > 0 ? (int)round(array_sum($qtys) / $n) : 0;
            $wma_forecast = $wma_denominator > 0 ? (int)round($wma_numerator / $wma_denominator) : 0;
            $currentStock = (int)round($currentStock);
            $sma_restock  = max(0, (int)($sma_forecast - $currentStock));
            $wma_restock  = max(0, (int)($wma_forecast - $currentStock));
        } else {
            $sma_forecast = $n > 0 ? round(array_sum($qtys) / $n, 2) : 0;
            $wma_forecast = $wma_denominator > 0 ? round($wma_numerator / $wma_denominator, 2) : 0;
            $sma_restock  = max(0, round($sma_forecast - $currentStock, 2));
            $wma_restock  = max(0, round($wma_forecast - $currentStock, 2));
            if ($currentStock == (int)$currentStock) $currentStock = (int)$currentStock;
            if ($sma_forecast == (int)$sma_forecast) $sma_forecast = (int)$sma_forecast;
            if ($wma_forecast == (int)$wma_forecast) $wma_forecast = (int)$wma_forecast;
            if ($sma_restock == (int)$sma_restock) $sma_restock = (int)$sma_restock;
            if ($wma_restock == (int)$wma_restock) $wma_restock = (int)$wma_restock;
        }

        // Save to tbl_forecasts
        $nextMonth = date('Y-m-01', strtotime('first day of next month'));
        $del = $db->prepare("DELETE FROM tbl_forecasts WHERE item_id = :id AND forecast_period = :period AND method = :method");
        $del->execute([':id'=>$itemId, ':period'=>$nextMonth, ':method'=>'SMA']);
        $del->execute([':id'=>$itemId, ':period'=>$nextMonth, ':method'=>'WMA']);

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
    } catch (Throwable $e) {
        respond(false, 'Database error: ' . $e->getMessage());
    }
}

// ── Forecast All Products ───────────────────────────────────────
if ($action === 'forecast_all') {
    $periods = (int)($_GET['periods'] ?? 6);
    $periods = in_array($periods, [3,6,12], true) ? $periods : 6;

    try {
        $items = $db->query("SELECT item_id, unit FROM tbl_inventory WHERE is_active = 1")->fetchAll();
        $nextMonth = date('Y-m-01', strtotime('first day of next month'));

        $histStmt = $db->prepare("
            SELECT COALESCE(SUM(d.quantity), 0) AS qty_sold
            FROM tbl_transaction_details d
            JOIN tbl_transactions t ON t.transaction_id = d.transaction_id
            WHERE d.item_id = :id
              AND (t.status IS NULL OR t.status != 'voided')
            GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
            ORDER BY DATE_FORMAT(t.transaction_date, '%Y-%m') DESC
            LIMIT {$periods}
        ");
        
        $delStmt = $db->prepare("DELETE FROM tbl_forecasts WHERE item_id = :id AND forecast_period = :period");
        $insStmt = $db->prepare("INSERT INTO tbl_forecasts (item_id, forecast_period, predicted_qty, method) VALUES (:id,:period,:qty,:method)");

        foreach ($items as $item) {
            $itemId = (int)$item['item_id'];
            $isPiece = isPieceUnit($item['unit'] ?? '');
            
            $histStmt->execute([':id' => $itemId]);
            $rawQtys = array_map(fn($r) => (float)$r['qty_sold'], $histStmt->fetchAll());
            $qtys = array_reverse($rawQtys);
            $n = count($qtys);

            $wma_numerator = 0; $wma_denominator = 0;
            foreach ($qtys as $i => $qty) {
                $w = $i + 1;
                $wma_numerator += $w * $qty;
                $wma_denominator += $w;
            }

            if ($isPiece) {
                $sma_forecast = $n > 0 ? (int)round(array_sum($qtys) / $n) : 0;
                $wma_forecast = $wma_denominator > 0 ? (int)round($wma_numerator / $wma_denominator) : 0;
            } else {
                $sma_forecast = $n > 0 ? round(array_sum($qtys) / $n, 2) : 0;
                $wma_forecast = $wma_denominator > 0 ? round($wma_numerator / $wma_denominator, 2) : 0;
                if ($sma_forecast == (int)$sma_forecast) $sma_forecast = (int)$sma_forecast;
                if ($wma_forecast == (int)$wma_forecast) $wma_forecast = (int)$wma_forecast;
            }

            $delStmt->execute([':id' => $itemId, ':period' => $nextMonth]);
            $insStmt->execute([':id' => $itemId, ':period' => $nextMonth, ':qty' => $sma_forecast, ':method' => 'SMA']);
            $insStmt->execute([':id' => $itemId, ':period' => $nextMonth, ':qty' => $wma_forecast, ':method' => 'WMA']);
        }

        respond(true, 'Generated forecast for all ' . count($items) . ' products.', ['count' => count($items)]);
    } catch (Throwable $e) {
        respond(false, 'Database error: ' . $e->getMessage());
    }
}

respond(false, 'Unknown action.');
