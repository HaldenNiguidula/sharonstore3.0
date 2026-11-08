<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/time.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}
// Public clock only: no session, credentials or database access are needed.
echo json_encode(['success' => true, 'unix_ms' => microtime(true) * 1000, 'timezone' => APP_TIMEZONE]);
