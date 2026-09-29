<?php
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
$db = getDB();

if ($action === 'sales_report') {
    $type = $_GET['type'] ?? 'daily';
    
    $query = "";
    if ($type === 'daily') {
        $query = "
            SELECT DATE(transaction_date) as raw_date, DATE_FORMAT(transaction_date, '%b %d, %Y') as label, SUM(total_amount) as total, COUNT(*) as tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided') AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(transaction_date)
            ORDER BY DATE(transaction_date) ASC
        ";
    } elseif ($type === 'weekly') {
        $query = "
            SELECT CONCAT(YEAR(transaction_date), '-', LPAD(WEEK(transaction_date), 2, '0')) as raw_date, CONCAT('Week ', WEEK(transaction_date), ', ', YEAR(transaction_date)) as label, SUM(total_amount) as total, COUNT(*) as tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided') AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
            GROUP BY YEAR(transaction_date), WEEK(transaction_date)
            ORDER BY YEAR(transaction_date) ASC, WEEK(transaction_date) ASC
        ";
    } elseif ($type === 'monthly') {
        $query = "
            SELECT DATE_FORMAT(transaction_date, '%Y-%m') as raw_date, DATE_FORMAT(transaction_date, '%M %Y') as label, SUM(total_amount) as total, COUNT(*) as tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided') AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY YEAR(transaction_date), MONTH(transaction_date)
            ORDER BY YEAR(transaction_date) ASC, MONTH(transaction_date) ASC
        ";
    } else {
        respond(false, 'Invalid report type.');
    }

    try {
        $stmt = $db->query($query);
        $results = $stmt->fetchAll();
        
        $labels = [];
        $totals = [];
        $tableData = [];
        
        foreach ($results as $r) {
            $labels[] = $r['label'];
            $totals[] = (float)$r['total'];
            $tableData[] = [
                'raw_date' => $r['raw_date'],
                'period' => $r['label'],
                'tx_count' => (int)$r['tx_count'],
                'revenue' => (float)$r['total']
            ];
        }
        
        // Reverse table data so newest is at the top
        $tableData = array_reverse($tableData);

        respond(true, 'Report generated', [
            'labels' => $labels,
            'totals' => $totals,
            'tableData' => $tableData
        ]);
    } catch (Exception $e) {
        respond(false, 'Database error: ' . $e->getMessage());
    }
}

if ($action === 'item_breakdown') {
    $type = $_GET['type'] ?? '';
    $raw_date = $_GET['raw_date'] ?? '';

    if (!$type || !$raw_date) respond(false, 'Missing parameters.');

    $where = "(t.status IS NULL OR t.status != 'voided')";
    $params = [];

    if ($type === 'daily') {
        $where .= " AND DATE(t.transaction_date) = ?";
        $params[] = $raw_date;
    } elseif ($type === 'weekly') {
        // raw_date format: YYYY-WW
        $parts = explode('-', $raw_date);
        if (count($parts) !== 2) respond(false, 'Invalid date format.');
        $where .= " AND YEAR(t.transaction_date) = ? AND WEEK(t.transaction_date) = ?";
        $params[] = $parts[0];
        $params[] = $parts[1];
    } elseif ($type === 'monthly') {
        // raw_date format: YYYY-MM
        $parts = explode('-', $raw_date);
        if (count($parts) !== 2) respond(false, 'Invalid date format.');
        $where .= " AND YEAR(t.transaction_date) = ? AND MONTH(t.transaction_date) = ?";
        $params[] = $parts[0];
        $params[] = $parts[1];
    } else {
        respond(false, 'Invalid report type.');
    }

    $sql = "
        SELECT 
            i.item_name, 
            SUM(td.quantity) as qty_sold, 
            SUM(td.subtotal) as total_revenue
        FROM tbl_transactions t
        JOIN tbl_transaction_details td ON td.transaction_id = t.transaction_id
        JOIN tbl_inventory i ON i.item_id = td.item_id
        WHERE {$where}
        GROUP BY i.item_id, i.item_name
        ORDER BY total_revenue DESC
    ";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert numerics
        foreach ($results as &$r) {
            $r['qty_sold'] = (float)$r['qty_sold'];
            $r['total_revenue'] = (float)$r['total_revenue'];
        }

        respond(true, 'Breakdown generated', $results);
    } catch (Exception $e) {
        respond(false, 'Database error: ' . $e->getMessage());
    }
}

respond(false, 'Unknown action.');
