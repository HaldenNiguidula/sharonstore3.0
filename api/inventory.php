<?php
// ============================================================
// Inventory API — Sharon Store System
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/audit.php';

header('Content-Type: application/json');

// ── Helper ────────────────────────────────────────────────────
function respond(bool $success, string $message = '', mixed $data = null, int $code = 200): never {
    http_response_code($code);
    $payload = ['success' => $success, 'message' => $message];
    if ($data !== null) $payload['data'] = $data;
    echo json_encode($payload);
    exit();
}

function intval_or_null(mixed $v): ?int {
    return ($v === '' || $v === null) ? null : (int)$v;
}

function floatval_or_null(mixed $v): ?float {
    return ($v === '' || $v === null) ? null : (float)$v;
}

// ── Route ─────────────────────────────────────────────────────
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

// Read-only actions: cashiers may access these
$readOnlyActions = ['barcode_lookup', 'list', 'get', 'stats', 'categories', 'search'];
if (in_array($action, $readOnlyActions)) {
    requireLogin();
} else {
    requireAdmin();  // add, update, delete, adjust_stock, add_category, delete_category, etc.
}

$db     = getDB();
$userId = currentUserId();

// ══════════════════════════════════════════════════════════════
// action = list
// ══════════════════════════════════════════════════════════════
if ($action === 'list') {
    $search   = trim($_GET['q'] ?? $_POST['q'] ?? '');
    $catId    = intval_or_null($_GET['category_id'] ?? $_POST['category_id'] ?? '');
    $page     = max(1, (int)($_GET['page'] ?? $_POST['page'] ?? 1));
    $perPage  = 20;
    $offset   = ($page - 1) * $perPage;

    $where  = ['i.is_active = 1'];
    $params = [];

    if ($search !== '') {
        $where[]         = '(i.item_name LIKE :q OR i.barcode LIKE :q2)';
        $params[':q']    = "%{$search}%";
        $params[':q2']   = "%{$search}%";
    }
    if ($catId !== null) {
        $where[]           = 'i.category_id = :cat';
        $params[':cat']    = $catId;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    // Total count
    $countSql  = "SELECT COUNT(*) FROM tbl_inventory i {$whereClause}";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    // Main query
    $sql = "SELECT
                i.item_id,
                i.barcode,
                i.item_name,
                i.category_id,
                c.category_name,
                i.unit,
                i.price,
                i.cost_price,
                i.stock_qty,
                i.low_stock_threshold,
                i.expiry_date,
                i.is_active,
                i.created_at,
                i.updated_at,
                CASE
                    WHEN i.stock_qty <= 0                        THEN 'out'
                    WHEN i.stock_qty <= i.low_stock_threshold    THEN 'low'
                    ELSE                                              'ok'
                END AS stock_status
            FROM tbl_inventory i
            JOIN tbl_categories c ON c.category_id = i.category_id
            {$whereClause}
            ORDER BY i.item_name ASC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();

    respond(true, 'OK', [
        'items'      => $items,
        'total'      => $totalRows,
        'page'       => $page,
        'per_page'   => $perPage,
        'total_pages'=> (int)ceil($totalRows / $perPage),
    ]);
}

// ══════════════════════════════════════════════════════════════
// action = get
// ══════════════════════════════════════════════════════════════
if ($action === 'get') {
    $itemId = intval_or_null($_GET['item_id'] ?? $_POST['item_id'] ?? '');
    if (!$itemId) respond(false, 'item_id is required.', null, 400);

    $stmt = $db->prepare("SELECT i.*, c.category_name,
                            CASE
                                WHEN i.stock_qty <= 0                     THEN 'out'
                                WHEN i.stock_qty <= i.low_stock_threshold THEN 'low'
                                ELSE 'ok'
                            END AS stock_status
                          FROM tbl_inventory i
                          JOIN tbl_categories c ON c.category_id = i.category_id
                          WHERE i.item_id = :id AND i.is_active = 1");
    $stmt->execute([':id' => $itemId]);
    $item = $stmt->fetch();

    if (!$item) respond(false, 'Item not found.', null, 404);
    respond(true, 'OK', $item);
}

// ══════════════════════════════════════════════════════════════
// action = add
// ══════════════════════════════════════════════════════════════
if ($action === 'add') {
    $barcode          = trim($_POST['barcode']             ?? '');
    $itemName         = trim($_POST['item_name']           ?? '');
    $categoryId       = intval_or_null($_POST['category_id']       ?? '');
    $unit             = trim($_POST['unit']                ?? 'pieces');
    $price            = floatval_or_null($_POST['price']           ?? '');
    $costPrice        = floatval_or_null($_POST['cost_price']      ?? '');
    $stockQty         = floatval_or_null($_POST['stock_qty']       ?? '');
    $lowStockThresh   = intval_or_null($_POST['low_stock_threshold'] ?? '');
    $expiryDate       = trim($_POST['expiry_date']         ?? '');

    // Validation
    $errors = [];
    if ($itemName === '')    $errors[] = 'Item name is required.';
    if ($categoryId === null) $errors[] = 'Category is required.';
    if ($price === null || $price < 0) $errors[] = 'Valid selling price is required.';
    if ($costPrice === null || $costPrice < 0) $errors[] = 'Valid cost price is required.';
    if ($stockQty === null || $stockQty < 0) $errors[] = 'Valid stock quantity is required.';
    if ($lowStockThresh === null || $lowStockThresh < 0) $errors[] = 'Valid low stock threshold is required.';
    if (!in_array($unit, ['pieces','packs','kg'], true)) $errors[] = 'Invalid unit.';

    // Check barcode uniqueness
    if ($barcode !== '') {
        $chk = $db->prepare("SELECT COUNT(*) FROM tbl_inventory WHERE barcode = :bc AND is_active = 1");
        $chk->execute([':bc' => $barcode]);
        if ((int)$chk->fetchColumn() > 0) $errors[] = 'Barcode already exists.';
    }

    if ($errors) respond(false, implode(' ', $errors), null, 422);

    $expiryVal = ($expiryDate !== '') ? $expiryDate : null;
    $barcodeVal = ($barcode !== '') ? $barcode : null;

    $sql = "INSERT INTO tbl_inventory
                (barcode, item_name, category_id, unit, price, cost_price, stock_qty, low_stock_threshold, expiry_date)
            VALUES
                (:bc, :name, :cat, :unit, :price, :cost, :qty, :thresh, :expiry)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':bc'     => $barcodeVal,
        ':name'   => $itemName,
        ':cat'    => $categoryId,
        ':unit'   => $unit,
        ':price'  => $price,
        ':cost'   => $costPrice,
        ':qty'    => $stockQty,
        ':thresh' => $lowStockThresh,
        ':expiry' => $expiryVal,
    ]);
    $newId = (int)$db->lastInsertId();

    logAudit($userId, 'ADD_ITEM', 'Inventory', "Added item #{$newId}: {$itemName} (qty: {$stockQty})");
    respond(true, 'Item added successfully.', ['item_id' => $newId]);
}

// ══════════════════════════════════════════════════════════════
// action = update
// ══════════════════════════════════════════════════════════════
if ($action === 'update') {
    $itemId           = intval_or_null($_POST['item_id']            ?? '');
    $barcode          = trim($_POST['barcode']             ?? '');
    $itemName         = trim($_POST['item_name']           ?? '');
    $categoryId       = intval_or_null($_POST['category_id']       ?? '');
    $unit             = trim($_POST['unit']                ?? 'pieces');
    $price            = floatval_or_null($_POST['price']           ?? '');
    $costPrice        = floatval_or_null($_POST['cost_price']      ?? '');
    $stockQty         = floatval_or_null($_POST['stock_qty']       ?? '');
    $lowStockThresh   = intval_or_null($_POST['low_stock_threshold'] ?? '');
    $expiryDate       = trim($_POST['expiry_date']         ?? '');

    $errors = [];
    if (!$itemId)       $errors[] = 'item_id is required.';
    if ($itemName === '') $errors[] = 'Item name is required.';
    if ($categoryId === null) $errors[] = 'Category is required.';
    if ($price === null || $price < 0)     $errors[] = 'Valid selling price is required.';
    if ($costPrice === null || $costPrice < 0) $errors[] = 'Valid cost price is required.';
    if ($stockQty === null || $stockQty < 0)   $errors[] = 'Valid stock quantity is required.';
    if ($lowStockThresh === null || $lowStockThresh < 0) $errors[] = 'Valid low stock threshold is required.';
    if (!in_array($unit, ['pieces','packs','kg'], true)) $errors[] = 'Invalid unit.';

    // Barcode uniqueness (excluding self)
    if ($barcode !== '') {
        $chk = $db->prepare("SELECT COUNT(*) FROM tbl_inventory WHERE barcode = :bc AND item_id != :id AND is_active = 1");
        $chk->execute([':bc' => $barcode, ':id' => $itemId]);
        if ((int)$chk->fetchColumn() > 0) $errors[] = 'Barcode already used by another item.';
    }

    if ($errors) respond(false, implode(' ', $errors), null, 422);

    // Confirm item exists
    $chkItem = $db->prepare("SELECT item_id, item_name FROM tbl_inventory WHERE item_id = :id AND is_active = 1");
    $chkItem->execute([':id' => $itemId]);
    if (!$chkItem->fetch()) respond(false, 'Item not found.', null, 404);

    $expiryVal  = ($expiryDate !== '') ? $expiryDate : null;
    $barcodeVal = ($barcode !== '') ? $barcode : null;

    $sql = "UPDATE tbl_inventory SET
                barcode              = :bc,
                item_name            = :name,
                category_id          = :cat,
                unit                 = :unit,
                price                = :price,
                cost_price           = :cost,
                stock_qty            = :qty,
                low_stock_threshold  = :thresh,
                expiry_date          = :expiry
            WHERE item_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':bc'     => $barcodeVal,
        ':name'   => $itemName,
        ':cat'    => $categoryId,
        ':unit'   => $unit,
        ':price'  => $price,
        ':cost'   => $costPrice,
        ':qty'    => $stockQty,
        ':thresh' => $lowStockThresh,
        ':expiry' => $expiryVal,
        ':id'     => $itemId,
    ]);

    logAudit($userId, 'UPDATE_ITEM', 'Inventory', "Updated item #{$itemId}: {$itemName}");
    respond(true, 'Item updated successfully.');
}

// ══════════════════════════════════════════════════════════════
// action = delete  (soft delete)
// ══════════════════════════════════════════════════════════════
if ($action === 'delete') {
    $itemId = intval_or_null($_POST['item_id'] ?? '');
    if (!$itemId) respond(false, 'item_id is required.', null, 400);

    $chk = $db->prepare("SELECT item_name FROM tbl_inventory WHERE item_id = :id AND is_active = 1");
    $chk->execute([':id' => $itemId]);
    $row = $chk->fetch();
    if (!$row) respond(false, 'Item not found.', null, 404);

    $db->prepare("UPDATE tbl_inventory SET is_active = 0 WHERE item_id = :id")
       ->execute([':id' => $itemId]);

    logAudit($userId, 'DELETE_ITEM', 'Inventory', "Soft-deleted item #{$itemId}: {$row['item_name']}");
    respond(true, 'Item deleted successfully.');
}

// ══════════════════════════════════════════════════════════════
// action = adjust_stock
// ══════════════════════════════════════════════════════════════
if ($action === 'adjust_stock') {
    $itemId    = intval_or_null($_POST['item_id']    ?? '');
    $adjType   = trim($_POST['adjust_type']  ?? '');   // 'add' or 'subtract' or 'set'
    $adjQty    = floatval_or_null($_POST['adjust_qty'] ?? '');
    $reason    = trim($_POST['reason']       ?? '');

    $errors = [];
    if (!$itemId)  $errors[] = 'item_id is required.';
    if (!in_array($adjType, ['add','subtract','set'], true)) $errors[] = 'adjust_type must be add, subtract, or set.';
    if ($adjQty === null || $adjQty < 0) $errors[] = 'Valid adjustment quantity is required.';
    if ($errors) respond(false, implode(' ', $errors), null, 422);

    $chk = $db->prepare("SELECT item_name, stock_qty FROM tbl_inventory WHERE item_id = :id AND is_active = 1");
    $chk->execute([':id' => $itemId]);
    $item = $chk->fetch();
    if (!$item) respond(false, 'Item not found.', null, 404);

    if ($adjType === 'add') {
        $newQty = (float)$item['stock_qty'] + $adjQty;
    } elseif ($adjType === 'subtract') {
        $newQty = max(0, (float)$item['stock_qty'] - $adjQty);
    } else {
        $newQty = $adjQty;
    }

    $db->prepare("UPDATE tbl_inventory SET stock_qty = :qty WHERE item_id = :id")
       ->execute([':qty' => $newQty, ':id' => $itemId]);

    $reasonText = $reason !== '' ? " | Reason: {$reason}" : '';
    logAudit(
        $userId, 'ADJUST_STOCK', 'Inventory',
        "Stock adjusted for #{$itemId} {$item['item_name']}: {$item['stock_qty']} → {$newQty} ({$adjType} {$adjQty}){$reasonText}"
    );
    respond(true, 'Stock adjusted successfully.', ['new_qty' => $newQty]);
}

// ══════════════════════════════════════════════════════════════
// action = categories
// ══════════════════════════════════════════════════════════════
if ($action === 'categories') {
    $stmt = $db->query("SELECT category_id, category_name FROM tbl_categories ORDER BY category_name ASC");
    respond(true, 'OK', $stmt->fetchAll());
}

// ══════════════════════════════════════════════════════════════
// action = add_category
// ══════════════════════════════════════════════════════════════
if ($action === 'add_category') {
    requireAdmin();
    $name = trim($_POST['category_name'] ?? '');
    if ($name === '') respond(false, 'Category name is required.', null, 422);

    // Check duplicate
    $chk = $db->prepare("SELECT category_id FROM tbl_categories WHERE LOWER(category_name) = LOWER(:n)");
    $chk->execute([':n' => $name]);
    if ($chk->fetch()) respond(false, 'Category already exists.', null, 409);

    $ins = $db->prepare("INSERT INTO tbl_categories (category_name) VALUES (:n)");
    $ins->execute([':n' => $name]);
    $newId = (int)$db->lastInsertId();
    respond(true, 'Category created.', ['category_id' => $newId, 'category_name' => $name]);
}

// ══════════════════════════════════════════════════════════════
// action = categories_with_counts  (for Manage Categories modal)
// ══════════════════════════════════════════════════════════════
if ($action === 'categories_with_counts') {
    requireAdmin();
    $stmt = $db->query("
        SELECT  c.category_id,
                c.category_name,
                COUNT(i.item_id)                                    AS total_items,
                SUM(CASE WHEN i.stock_qty > 0 THEN 1 ELSE 0 END)   AS stocked_items
        FROM    tbl_categories c
        LEFT JOIN tbl_inventory i ON i.category_id = c.category_id AND i.is_active = 1
        GROUP BY c.category_id, c.category_name
        ORDER BY c.category_name ASC
    ");
    respond(true, 'OK', $stmt->fetchAll());
}

// ══════════════════════════════════════════════════════════════
// action = check_category  — returns item summary before delete
// ══════════════════════════════════════════════════════════════
if ($action === 'check_category') {
    requireAdmin();
    $catId = intval($_GET['category_id'] ?? 0);
    if (!$catId) respond(false, 'Category ID required.', null, 422);

    // Category name
    $cat = $db->prepare("SELECT category_name FROM tbl_categories WHERE category_id = :id");
    $cat->execute([':id' => $catId]);
    $catRow = $cat->fetch();
    if (!$catRow) respond(false, 'Category not found.', null, 404);

    // Items split into stocked / unstocked
    $items = $db->prepare("
        SELECT item_id, item_name, stock_qty
        FROM   tbl_inventory
        WHERE  category_id = :id AND is_active = 1
        ORDER BY item_name ASC
    ");
    $items->execute([':id' => $catId]);
    $rows = $items->fetchAll();

    $stocked   = array_values(array_filter($rows, fn($r) => $r['stock_qty'] > 0));
    $unstocked = array_values(array_filter($rows, fn($r) => $r['stock_qty'] <= 0));

    respond(true, 'OK', [
        'category_id'    => $catId,
        'category_name'  => $catRow['category_name'],
        'stocked_count'  => count($stocked),
        'unstocked_count'=> count($unstocked),
        'total_items'    => count($rows),
        'stocked_items'  => $stocked,   // items with stock > 0
    ]);
}

// ══════════════════════════════════════════════════════════════
// action = delete_category
// POST: category_id, reassign_to (optional, required if stocked)
// Logic:
//   • No items          → delete outright
//   • 0-stock items only→ move them to Uncategorized, then delete
//   • Stocked items     → must reassign_to another category
// ══════════════════════════════════════════════════════════════
if ($action === 'delete_category') {
    requireAdmin();
    $catId     = intval($_POST['category_id'] ?? 0);
    $reassignTo = intval($_POST['reassign_to'] ?? 0);
    if (!$catId) respond(false, 'Category ID required.', null, 422);

    // Fetch category
    $cat = $db->prepare("SELECT category_name FROM tbl_categories WHERE category_id = :id");
    $cat->execute([':id' => $catId]);
    $catRow = $cat->fetch();
    if (!$catRow) respond(false, 'Category not found.', null, 404);
    $catName = $catRow['category_name'];

    // Count stocked items
    $chkStocked = $db->prepare(
        "SELECT COUNT(*) FROM tbl_inventory WHERE category_id = :id AND is_active = 1 AND stock_qty > 0"
    );
    $chkStocked->execute([':id' => $catId]);
    $stockedCount = (int)$chkStocked->fetchColumn();

    // Count all active items
    $chkAll = $db->prepare(
        "SELECT COUNT(*) FROM tbl_inventory WHERE category_id = :id AND is_active = 1"
    );
    $chkAll->execute([':id' => $catId]);
    $totalCount = (int)$chkAll->fetchColumn();

    $db->beginTransaction();
    try {
        if ($stockedCount > 0) {
            // Must have a valid reassign target
            if (!$reassignTo) {
                respond(false, 'This category has products with stock. Choose a category to reassign them to.', null, 409);
            }
            if ($reassignTo === $catId) {
                respond(false, 'Reassignment target cannot be the same category.', null, 422);
            }
            // Verify target exists
            $tgt = $db->prepare("SELECT category_id FROM tbl_categories WHERE category_id = :id");
            $tgt->execute([':id' => $reassignTo]);
            if (!$tgt->fetch()) respond(false, 'Reassignment category not found.', null, 404);

            // Reassign ALL items (stocked + unstocked) to target
            $mv = $db->prepare("UPDATE tbl_inventory SET category_id = :to WHERE category_id = :from");
            $mv->execute([':to' => $reassignTo, ':from' => $catId]);
            $moved = $mv->rowCount();

        } elseif ($totalCount > 0) {
            // Only 0-stock items — move to Uncategorized (create if missing)
            $unc = $db->query("SELECT category_id FROM tbl_categories WHERE category_name = 'Uncategorized' LIMIT 1");
            $uncRow = $unc->fetch();
            if ($uncRow) {
                $uncId = (int)$uncRow['category_id'];
            } else {
                $db->exec("INSERT INTO tbl_categories (category_name) VALUES ('Uncategorized')");
                $uncId = (int)$db->lastInsertId();
            }
            $mv = $db->prepare("UPDATE tbl_inventory SET category_id = :to WHERE category_id = :from");
            $mv->execute([':to' => $uncId, ':from' => $catId]);
            $moved = $mv->rowCount();
        } else {
            $moved = 0;
        }

        // Delete the category
        $del = $db->prepare("DELETE FROM tbl_categories WHERE category_id = :id");
        $del->execute([':id' => $catId]);

        $db->commit();

        respond(true, "Category \"{$catName}\" deleted.", [
            'category_id'   => $catId,
            'items_moved'   => $moved,
            'stocked_moved' => $stockedCount,
        ]);

    } catch (Throwable $e) {
        $db->rollBack();
        respond(false, 'Delete failed: ' . $e->getMessage(), null, 500);
    }
}

// ══════════════════════════════════════════════════════════════
// action = barcode_lookup
// ══════════════════════════════════════════════════════════════
if ($action === 'barcode_lookup') {
    $barcode = trim($_GET['barcode'] ?? $_POST['barcode'] ?? '');
    if ($barcode === '') respond(false, 'Barcode is required.', null, 400);

    $stmt = $db->prepare("SELECT i.*, c.category_name FROM tbl_inventory i
                          JOIN tbl_categories c ON c.category_id = i.category_id
                          WHERE i.barcode = :bc AND i.is_active = 1 LIMIT 1");
    $stmt->execute([':bc' => $barcode]);
    $item = $stmt->fetch();

    if (!$item) respond(false, 'Item not found.', null, 404);
    respond(true, 'OK', $item);
}

// ══════════════════════════════════════════════════════════════
// action = stats  (summary stats for dashboard widgets)
// ══════════════════════════════════════════════════════════════
if ($action === 'stats') {
    $row = $db->query("SELECT
        COUNT(*)                                                          AS total_items,
        COALESCE(SUM(price * stock_qty), 0)                              AS total_value,
        SUM(CASE WHEN stock_qty <= 0                              THEN 1 ELSE 0 END) AS out_of_stock,
        SUM(CASE WHEN stock_qty > 0 AND stock_qty <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock
        FROM tbl_inventory WHERE is_active = 1")->fetch();
    respond(true, 'OK', $row);
}

// Unknown action
respond(false, "Unknown action: {$action}", null, 400);
