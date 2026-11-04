<?php
declare(strict_types=1);

/** Lock administrators in a consistent order before any account can lose access. */
function lockAccountForChange(PDO $db, int $actorId, int $targetId): array {
    if (!$db->inTransaction()) throw new LogicException('Account changes require a transaction.');
    $admins = $db->query("SELECT user_id FROM tbl_users WHERE role = 'admin' AND is_active = 1 ORDER BY user_id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
    $admins = array_map('intval', $admins);
    if (!in_array($actorId, $admins, true)) throw new RuntimeException('Your administrator access has changed. Please sign in again.');
    $stmt = $db->prepare('SELECT user_id, username, role, is_active FROM tbl_users WHERE user_id = ? FOR UPDATE');
    $stmt->execute([$targetId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new RuntimeException('User not found.');
    return ['user' => $user, 'admins' => $admins];
}

function guardAccountAccess(array $locked, int $actorId, string $role, int $active, bool $deleting = false): void {
    if (!in_array($active, [0, 1], true)) throw new RuntimeException('Invalid account status.');
    $user = $locked['user'];
    if ((int)$user['user_id'] === $actorId && ($deleting || $role !== 'admin' || $active !== 1)) {
        throw new RuntimeException('You cannot remove your own administrator access. Ask another administrator to make this change.');
    }
    if (in_array((int)$user['user_id'], $locked['admins'], true)
        && ($deleting || $role !== 'admin' || $active !== 1) && count($locked['admins']) <= 1) {
        throw new RuntimeException('At least one active administrator must remain.');
    }
}
