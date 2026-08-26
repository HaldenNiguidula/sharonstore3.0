<?php
// ============================================================
// Dashboard API — Sharon Store System
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

// ── Stats ──────────────────────────────────────────────────────
if ($action === 'stats') {
    $today = $db->query("
        SELECT COALESCE(SUM(total_amount),0) AS today_revenue,
               COUNT(*) AS today_transactions
        FROM tbl_transactions
        WHERE DATE(transaction_date) = CURDATE()
          AND (status IS NULL OR status != 'voided')
    ")->fetch();

    $weekly = $db->query("
        SELECT COALESCE(SUM(total_amount),0) AS weekly_revenue
        FROM tbl_transactions
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          AND (status IS NULL OR status != 'voided')
    ")->fetch();

    $monthly = $db->query("
        SELECT COALESCE(SUM(total_amount),0) AS monthly_revenue
        FROM tbl_transactions
        WHERE MONTH(transaction_date) = MONTH(CURDATE())
          AND YEAR(transaction_date) = YEAR(CURDATE())
          AND (status IS NULL OR status != 'voided')
    ")->fetch();

    $inv = $db->query("
        SELECT COUNT(*) AS total_items,
               SUM(CASE WHEN stock_qty <= 0 THEN 1 ELSE 0 END) AS out_of_stock,
               SUM(CASE WHEN stock_qty > 0 AND stock_qty <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock,
               SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_soon,
               COALESCE(SUM(price * stock_qty), 0) AS total_stock_value
        FROM tbl_inventory WHERE is_active = 1
    ")->fetch();

    respond(true, 'OK', array_merge($today, $weekly, $monthly, $inv));
}

// ── Sales Trend (last 30 days) ─────────────────────────────────
if ($action === 'sales_trend') {
    $stmt = $db->query("
        SELECT DATE(transaction_date) AS sale_date,
               COALESCE(SUM(total_amount),0) AS daily_total,
               COUNT(*) AS tx_count
        FROM tbl_transactions
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND (status IS NULL OR status != 'voided')
        GROUP BY DATE(transaction_date)
        ORDER BY sale_date ASC
    ");
    $rows   = $stmt->fetchAll();
    $labels = array_column($rows, 'sale_date');
    $data   = array_map(fn($r) => (float)$r['daily_total'], $rows);
    respond(true, 'OK', ['labels' => $labels, 'data' => $data]);
}

// ── Top 10 Fast-Moving Items (last 30 days) ────────────────────
if ($action === 'top_items') {
    $stmt = $db->query("
        SELECT i.item_name, SUM(d.quantity) AS total_qty, SUM(d.subtotal) AS total_sales
        FROM tbl_transaction_details d
        JOIN tbl_inventory i ON i.item_id = d.item_id
        JOIN tbl_transactions t ON t.transaction_id = d.transaction_id
        WHERE t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND (t.status IS NULL OR t.status != 'voided')
        GROUP BY i.item_id, i.item_name
        ORDER BY total_qty DESC
        LIMIT 10
    ");
    $rows = $stmt->fetchAll();
    respond(true, 'OK', [
        'labels' => array_column($rows, 'item_name'),
        'data'   => array_map(fn($r) => (float)$r['total_qty'], $rows),
    ]);
}

// ── Category Sales Distribution ────────────────────────────────
if ($action === 'category_sales') {
    $stmt = $db->query("
        SELECT c.category_name, COALESCE(SUM(d.subtotal),0) AS total
        FROM tbl_transaction_details d
        JOIN tbl_inventory i ON i.item_id = d.item_id
        JOIN tbl_categories c ON c.category_id = i.category_id
        JOIN tbl_transactions t ON t.transaction_id = d.transaction_id
        WHERE t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND (t.status IS NULL OR t.status != 'voided')
        GROUP BY c.category_id, c.category_name
        ORDER BY total DESC
    ");
    $rows = $stmt->fetchAll();
    respond(true, 'OK', [
        'labels' => array_column($rows, 'category_name'),
        'data'   => array_map(fn($r) => (float)$r['total'], $rows),
    ]);
}

// ── Low Stock Items ────────────────────────────────────────────
if ($action === 'low_stock') {
    $stmt = $db->query("
        SELECT i.item_id, i.item_name, i.stock_qty, i.low_stock_threshold,
               i.unit, c.category_name,
               CASE WHEN i.stock_qty <= 0 THEN 'out' ELSE 'low' END AS status
        FROM tbl_inventory i
        JOIN tbl_categories c ON c.category_id = i.category_id
        WHERE i.is_active = 1 AND i.stock_qty <= i.low_stock_threshold
        ORDER BY i.stock_qty ASC
        LIMIT 10
    ");
    respond(true, 'OK', $stmt->fetchAll());
}

// ── Expiring Soon ──────────────────────────────────────────────
if ($action === 'expiring_soon') {
    $stmt = $db->query("
        SELECT i.item_id, i.item_name, i.expiry_date, i.stock_qty, i.unit, c.category_name,
               DATEDIFF(i.expiry_date, CURDATE()) AS days_left
        FROM tbl_inventory i
        JOIN tbl_categories c ON c.category_id = i.category_id
        WHERE i.is_active = 1
          AND i.expiry_date IS NOT NULL
          AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY i.expiry_date ASC
        LIMIT 10
    ");
    respond(true, 'OK', $stmt->fetchAll());
}

respond(false, 'Unknown action.', null);
