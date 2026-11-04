<?php
declare(strict_types=1);

/** Consecutive calendar months ending in the current month, including zero sales. */
function forecastHistory(array $rows, int $periods, ?DateTimeImmutable $month = null): array {
    $month = ($month ?? new DateTimeImmutable('first day of this month'))->modify('first day of this month')->setTime(0, 0);
    $sales = array_column($rows, 'qty_sold', 'month_key');
    $history = [];
    for ($i = $periods - 1; $i >= 0; $i--) {
        $date = $month->modify("-{$i} months");
        $key = $date->format('Y-m');
        $history[] = ['month_key' => $key, 'month_label' => $date->format('M Y'), 'qty_sold' => (float)($sales[$key] ?? 0)];
    }
    return $history;
}
