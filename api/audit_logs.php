<?php
declare(strict_types=1);

// Audit records are retained for authorized database review, without a reader API.
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['success' => false, 'message' => 'Not Found']);
exit;
