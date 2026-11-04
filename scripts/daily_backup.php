<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/scheduled_backup.php';
require_once __DIR__ . '/../includes/audit.php';
try {
    $result = runScheduledBackup(getDB(), 'local');
    if (empty($result['skipped'])) logAudit(null, 'DAILY_BACKUP', 'Backup', $result['message'] . ' ' . ($result['filename'] ?? ''));
    fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Daily backup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
