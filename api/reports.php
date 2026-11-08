<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sales_reports.php';

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
    
    if (!in_array($type, ['daily', 'weekly', 'monthly'], true)) respond(false, 'Invalid report type.');
    try {
        $rawRows = salesReportRows($db, $type);

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
    $localTransactionTime = appLocalSql('t.transaction_date');

    if ($type === 'daily') {
        $where .= " AND DATE({$localTransactionTime}) = ?";
        $params[] = $raw_date;
    } elseif ($type === 'weekly') {
        // raw_date format: YYYY-WW
        $parts = explode('-', $raw_date);
        if (count($parts) !== 2) respond(false, 'Invalid date format.');
        $where .= ' AND ' . appIsoWeekSql('t.transaction_date') . ' = ?';
        $params[] = sprintf('%04d-%02d', (int)$parts[0], (int)$parts[1]);
    } elseif ($type === 'monthly') {
        // raw_date format: YYYY-MM
        $parts = explode('-', $raw_date);
        if (count($parts) !== 2) respond(false, 'Invalid date format.');
        $where .= " AND YEAR({$localTransactionTime}) = ? AND MONTH({$localTransactionTime}) = ?";
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
