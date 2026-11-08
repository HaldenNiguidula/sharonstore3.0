<?php
declare(strict_types=1);

// Activity recording remains in includes/audit.php; no audit viewer is exposed.
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
echo 'Not Found';
exit;
