<?php
// ============================================================
// Audit Logger Helper — Sharon Store System
// ============================================================
require_once __DIR__ . '/db.php';

function logAudit(int $userId, string $action, string $module, string $description): void {
    try {
        $db  = getDB();
        $sql = "INSERT INTO tbl_audit_logs (user_id, action, module, description, ip_address)
                VALUES (:uid, :action, :module, :desc, :ip)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':uid'    => $userId,
            ':action' => $action,
            ':module' => $module,
            ':desc'   => $description,
            ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (Exception $e) {
        // Silent fail for audit — don't break main flow
        error_log("Audit log failed: " . $e->getMessage());
    }
}
