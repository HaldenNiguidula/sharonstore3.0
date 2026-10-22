<?php
// ============================================================
// Vercel Serverless Entrypoint Router — Sharon Store
// ============================================================

$requested = $_GET['file'] ?? '';

if (empty($requested) || $requested === '/') {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $uri = ltrim((string)$uri, '/');
    if (str_starts_with($uri, 'sharonstore3.0/')) {
        $uri = substr($uri, strlen('sharonstore3.0/'));
    }
    $requested = $uri;
}

// Strip query string if present
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

$rootDir = realpath(__DIR__ . '/..');

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
echo "404 Not Found: " . htmlspecialchars($requested);
