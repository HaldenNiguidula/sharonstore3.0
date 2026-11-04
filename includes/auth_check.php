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
            $chk = $db->prepare("SELECT user_id, username, full_name, role, is_active FROM tbl_users WHERE user_id = ? LIMIT 1");
            $chk->execute([$_SESSION['user_id']]);
            $row = $chk->fetch();

            // Never transfer an authenticated session to another account after a restore.
            if (!$row || (int)$row['is_active'] !== 1 || $row['username'] !== ($_SESSION['username'] ?? '')) {
                $_SESSION = [];
            } else {
                $_SESSION['role'] = $row['role'];
                $_SESSION['full_name'] = $row['full_name'];
            }
        } catch (\Throwable $e) {
            // Fail closed when current account permissions cannot be verified.
            $_SESSION = [];
        }
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
