<?php
declare(strict_types=1);

/** Hold the same inventory row lock as checkout until the adjustment is saved. */
function adjustInventoryStock(PDO $db, int $itemId, string $type, float $quantity): array {
    if (!in_array($type, ['add', 'subtract', 'set'], true) || !is_finite($quantity) || $quantity < 0) {
        throw new InvalidArgumentException('Invalid stock adjustment.');
    }
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('SELECT item_name, stock_qty FROM tbl_inventory WHERE item_id = ? AND is_active = 1 FOR UPDATE');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) throw new RuntimeException('Item not found.', 404);
        $previous = (float)$item['stock_qty'];
        $next = match ($type) {
            'add' => $previous + $quantity,
            'subtract' => max(0, $previous - $quantity),
            'set' => $quantity,
        };
        $db->prepare('UPDATE tbl_inventory SET stock_qty = ? WHERE item_id = ?')->execute([$next, $itemId]);
        $db->commit();
        return ['item_name' => $item['item_name'], 'previous_qty' => $previous, 'new_qty' => $next];
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
