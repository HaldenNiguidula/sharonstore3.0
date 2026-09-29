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
            SELECT DATE_FORMAT(transaction_date, '%b %d, %Y') as label, SUM(total_amount) as total, COUNT(*) as tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided') AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(transaction_date)
            ORDER BY DATE(transaction_date) ASC
        ";
    } elseif ($type === 'weekly') {
        $query = "
            SELECT CONCAT('Week ', WEEK(transaction_date), ', ', YEAR(transaction_date)) as label, SUM(total_amount) as total, COUNT(*) as tx_count
            FROM tbl_transactions
            WHERE (status IS NULL OR status != 'voided') AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
            GROUP BY YEAR(transaction_date), WEEK(transaction_date)
            ORDER BY YEAR(transaction_date) ASC, WEEK(transaction_date) ASC
        ";
    } elseif ($type === 'monthly') {
        $query = "
            SELECT DATE_FORMAT(transaction_date, '%M %Y') as label, SUM(total_amount) as total, COUNT(*) as tx_count
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

respond(false, 'Unknown action.');
