<?php
// ============================================================
// Auth Check — Sharon Store System
// ============================================================
require_once __DIR__ . '/db.php';
startAppSession();

function syncSessionUser(): void {
    if (isset($_SESSION['user_id'])) {
        static $synced = false;
        if ($synced) return;
        $synced = true;

        try {
            $db = getDB();
            $chk = $db->prepare("SELECT user_id, role, is_active FROM tbl_users WHERE user_id = ? LIMIT 1");
            $chk->execute([$_SESSION['user_id']]);
            $row = $chk->fetch();

            if (!$row && !empty($_SESSION['username'])) {
                // User ID in session doesn't match DB (e.g. after a DB restore or import) - resync by username
                $byUser = $db->prepare("SELECT user_id, role, is_active FROM tbl_users WHERE username = ? LIMIT 1");
                $byUser->execute([$_SESSION['username']]);
                $uRow = $byUser->fetch();
                if ($uRow) {
                    $_SESSION['user_id'] = (int)$uRow['user_id'];
                    $_SESSION['role']    = $uRow['role'];
                }
            }
        } catch (\Throwable $e) {}
    }
}
syncSessionUser();

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        // If it's an API/AJAX request, return JSON 401 instead of redirect
        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
        );
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
            exit();
        }
        header('Location: /sharonstore3.0/index.php');
        exit();
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
        );
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin privileges required.']);
            exit();
        }
        header('Location: /sharonstore3.0/pos.php');
        exit();
    }
}

function requireCashier(): void {
    requireLogin();
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentUserName(): string {
    return $_SESSION['full_name'] ?? 'Unknown';
}

function currentRole(): string {
    return $_SESSION['role'] ?? 'cashier';
}
