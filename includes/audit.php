<?php
// ============================================================
// Audit Logger Helper — Sharon Store System
// ============================================================
require_once __DIR__ . '/db.php';

function logAudit(?int $userId, string $action, string $module, string $description): void {
    try {
        $db  = getDB();
        $validUid = null;
        if ($userId && $userId > 0) {
            $chk = $db->prepare("SELECT user_id FROM tbl_users WHERE user_id = ? LIMIT 1");
            $chk->execute([$userId]);
            if ($chk->fetchColumn()) {
                $validUid = $userId;
            }
        }
        $sql = "INSERT INTO tbl_audit_logs (user_id, action, module, description, ip_address)
                VALUES (:uid, :action, :module, :desc, :ip)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid', $validUid, $validUid === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':module', $module);
        $stmt->bindValue(':desc', $description);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $stmt->execute();
    } catch (\Throwable $e) {
        // Silent fail for audit — don't break main flow
        error_log("Audit log failed: " . $e->getMessage());
    }
}
