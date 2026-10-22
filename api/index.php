<?php
// ============================================================
// Vercel Serverless Entrypoint Router — Sharon Store
// ============================================================
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="font-family:sans-serif;max-width:700px;margin:40px auto;padding:24px;background:#fef2f2;border:1px solid #f87171;border-radius:12px;color:#991b1b;">';
    echo '<h3 style="margin-top:0;">Sharon Store Application Error</h3>';
    echo '<p><b>' . htmlspecialchars($e->getMessage()) . '</b></p>';
    echo '<p style="font-size:0.85em;color:#7f1d1d;">In ' . htmlspecialchars($e->getFile()) . ' on line ' . $e->getLine() . '</p>';
    echo '<pre style="background:#fff;padding:12px;border-radius:8px;overflow:auto;font-size:0.8em;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
    exit();
});

// Determine requested script
$requested = $_GET['file'] ?? '';

if (empty($requested) || $requested === '/') {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $uri = ltrim((string)$uri, '/');
    if (str_starts_with($uri, 'sharonstore3.0/')) {
        $uri = substr($uri, strlen('sharonstore3.0/'));
    }
    $requested = $uri;
}

// Clean query string
if (($q = strpos($requested, '?')) !== false) {
    $requested = substr($requested, 0, $q);
}

$requested = ltrim($requested, '/');

if (str_starts_with($requested, 'sharonstore3.0/')) {
    $requested = substr($requested, strlen('sharonstore3.0/'));
}

if ($requested === '' || $requested === '/') {
    $requested = 'index.php';
}

if (!str_ends_with($requested, '.php') && !str_contains($requested, '.')) {
    $requested .= '.php';
}

$rootDir = realpath(__DIR__ . '/..') ?: dirname(__DIR__);

if (str_starts_with($requested, 'api/')) {
    $apiFile = substr($requested, 4);
    $cleanApi = basename($apiFile);
    $target = $rootDir . '/api/' . $cleanApi;
} else {
    $cleanFile = basename($requested);
    $target = $rootDir . '/' . $cleanFile;
}

if (file_exists($target)) {
    chdir(dirname($target));
    require $target;
    exit();
}

http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($requested) . " (Target file: " . htmlspecialchars($target) . ")";
