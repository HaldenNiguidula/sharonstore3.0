<?php
declare(strict_types=1);

/** Calendar windows include today/current week/month, including periods without sales. */
function salesReportRows(PDO $db, string $type, ?DateTimeImmutable $today = null): array {
    if (!in_array($type, ['daily', 'weekly', 'monthly'], true)) throw new InvalidArgumentException('Invalid report type.');
    // Use the database calendar, just as transaction timestamps and dashboard totals do.
    $today = ($today ?? new DateTimeImmutable((string)$db->query('SELECT CURDATE()')->fetchColumn()))->setTime(0, 0);
    [$count, $step, $keySql] = match ($type) {
        'daily' => [30, 'day', "DATE_FORMAT(transaction_date, '%Y-%m-%d')"],
        'weekly' => [12, 'week', "DATE_FORMAT(transaction_date, '%x-%v')"],
        'monthly' => [12, 'month', "DATE_FORMAT(transaction_date, '%Y-%m')"],
    };
    $current = match ($type) {
        'weekly' => $today->modify('-' . ((int)$today->format('N') - 1) . ' days'),
        'monthly' => $today->modify('first day of this month'),
        default => $today,
    };
    $start = $current->modify('-' . ($count - 1) . ' ' . $step);
    $stmt = $db->prepare("SELECT {$keySql} AS raw_date, COALESCE(SUM(total_amount), 0) AS total, COUNT(*) AS tx_count
        FROM tbl_transactions
        WHERE (status IS NULL OR status != 'voided') AND transaction_date >= ? AND transaction_date < ?
        GROUP BY {$keySql}");
    $stmt->execute([$start->format('Y-m-d'), $today->modify('+1 day')->format('Y-m-d')]);
    $totals = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), null, 'raw_date');
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $date = $current->modify("-{$i} {$step}");
        $key = $date->format(match ($type) { 'weekly' => 'o-W', 'monthly' => 'Y-m', default => 'Y-m-d' });
        $rows[] = [
            'raw_date' => $key,
            'label' => match ($type) {
                'weekly' => 'Week ' . (int)$date->format('W') . ', ' . $date->format('o'),
                'monthly' => $date->format('F Y'),
                default => $date->format('M d, Y'),
            },
            'yr' => (int)$date->format('o'),
            'wk' => (int)$date->format('W'),
            'total' => (float)($totals[$key]['total'] ?? 0),
            'tx_count' => (int)($totals[$key]['tx_count'] ?? 0),
        ];
    }
    return $rows;
}
