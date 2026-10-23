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
        // Safe with ONLY_FULL_GROUP_BY: all selected expressions are in GROUP BY
        $query = "
            SELECT DATE(transaction_date) AS raw_date,
                   DATE_FORMAT(transaction_date, '%b %d, %Y') AS label,
                   COALESCE(SUM(total_amount), 0) AS total,
                   COUNT(*) AS tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided')
            GROUP BY DATE(transaction_date), DATE_FORMAT(transaction_date, '%b %d, %Y')
            ORDER BY DATE(transaction_date) DESC
            LIMIT 30
        ";
    } elseif ($type === 'weekly') {
        $query = "
            SELECT 
                YEAR(transaction_date) AS yr,
                WEEK(transaction_date, 1) AS wk,
                CONCAT(YEAR(transaction_date), '-', LPAD(WEEK(transaction_date, 1), 2, '0')) AS raw_date, 
                CONCAT('Week ', WEEK(transaction_date, 1), ', ', YEAR(transaction_date)) AS label, 
                COALESCE(SUM(total_amount), 0) AS total, 
                COUNT(*) AS tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided')
            GROUP BY YEAR(transaction_date), WEEK(transaction_date, 1), raw_date, label
            ORDER BY yr DESC, wk DESC
            LIMIT 12
        ";
    } elseif ($type === 'monthly') {
        $query = "
            SELECT 
                DATE_FORMAT(transaction_date, '%Y-%m') AS raw_date,
                DATE_FORMAT(transaction_date, '%M %Y') AS label,
                COALESCE(SUM(total_amount), 0) AS total,
                COUNT(*) AS tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided')
            GROUP BY raw_date, label, YEAR(transaction_date), MONTH(transaction_date)
            ORDER BY raw_date DESC
            LIMIT 12
        ";
    } else {
        respond(false, 'Invalid report type.');
    }

    try {
        $stmt = $db->query($query);
        $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Reverse so chart displays chronologically (oldest to newest)
        $chronological = array_reverse($rawRows);
        $labels = [];
        $totals = [];
        foreach ($chronological as $r) {
            $labels[] = $r['label'];
            $totals[] = (float)$r['total'];
        }

        // Table data: newest first
        $tableData = [];
        foreach ($rawRows as $r) {
            $item = [
                'raw_date' => $r['raw_date'],
                'period'   => $r['label'],
                'tx_count' => (int)$r['tx_count'],
                'revenue'  => (float)$r['total']
            ];

            if ($type === 'weekly') {
                try {
                    $dto = new DateTime();
                    $dto->setISODate((int)$r['yr'], (int)$r['wk']);
                    $startStr = $dto->format('M d, Y');
                    $dto->modify('+6 days');
                    $endStr = $dto->format('M d, Y');
                    $item['date_range'] = "$startStr – $endStr";
                } catch (\Throwable $e) {
                    $item['date_range'] = $r['label'];
                }
            }

            $tableData[] = $item;
        }

        respond(true, 'Report generated', [
            'labels'    => $labels,
            'totals'    => $totals,
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
        $where .= " AND YEAR(t.transaction_date) = ? AND WEEK(t.transaction_date, 1) = ?";
        $params[] = (int)$parts[0];
        $params[] = (int)$parts[1];
    } elseif ($type === 'monthly') {
        // raw_date format: YYYY-MM
        $parts = explode('-', $raw_date);
        if (count($parts) !== 2) respond(false, 'Invalid date format.');
        $where .= " AND YEAR(t.transaction_date) = ? AND MONTH(t.transaction_date) = ?";
        $params[] = (int)$parts[0];
        $params[] = (int)$parts[1];
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
