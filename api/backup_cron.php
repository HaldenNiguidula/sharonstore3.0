<?php
declare(strict_types=1);

// Authenticate BEFORE loading database or session code. Never accept a query-string secret.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$secret = (string)getenv('CRON_SECRET');
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($secret === '' || !hash_equals('Bearer ' . $secret, $authorization)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { http_response_code(405); header('Allow: GET'); exit; }
require_once __DIR__ . '/../includes/scheduled_backup.php';
require_once __DIR__ . '/../includes/audit.php';
try {
    $result = runScheduledBackup(getDB(), 'vercel');
    if (empty($result['skipped'])) logAudit(null, 'DAILY_BACKUP', 'Backup', $result['message'] . ' ' . ($result['filename'] ?? ''));
    echo json_encode($result);
} catch (Throwable $e) {
    error_log('Daily cloud backup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Daily backup failed. Check Database Backup for details.']);
}
