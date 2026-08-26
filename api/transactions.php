<?php
// ============================================================
// API: Transactions — Sharon Store POS System
// Endpoint: /sharonstore3.0/api/transactions.php
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Helper: send JSON response and exit ───────────────────────
function jsonOut(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// ── Helper: format peso ───────────────────────────────────────
function peso(float $v): string {
    return '&#8369;' . number_format($v, 2);
}

// ── Route: detect action ──────────────────────────────────────
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// For POST with JSON body, decode it
$body = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $body   = $decoded;
            $action = $body['action'] ?? $action;
        }
    }
}

// ── Dispatch ──────────────────────────────────────────────────
match ($action) {
    'barcode_lookup'      => handleBarcodeLookup(),
    'search_items'        => handleSearchItems(),
    'complete_sale'       => handleCompleteSale($body),
    'daily_summary'       => handleDailySummary(),
    'void_transaction'    => handleVoidTransaction($body),
    'recent_transactions' => handleRecentTransactions(),
    default               => jsonOut(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)], 400),
};

// ============================================================
// ACTION: barcode_lookup
// GET ?action=barcode_lookup&barcode=xxx
// ============================================================
function handleBarcodeLookup(): never {
    $barcode = trim($_GET['barcode'] ?? '');
    if ($barcode === '') {
        jsonOut(['success' => false, 'message' => 'Barcode is required.'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT i.item_id, i.barcode, i.item_name, i.unit, i.price, i.stock_qty,
                i.low_stock_threshold, c.category_name
         FROM   tbl_inventory i
         JOIN   tbl_categories c ON c.category_id = i.category_id
         WHERE  i.barcode = :barcode AND i.is_active = 1
         LIMIT  1"
    );
    $stmt->execute([':barcode' => $barcode]);
    $item = $stmt->fetch();

    if (!$item) {
        jsonOut(['success' => false, 'message' => 'Item not found for barcode: ' . htmlspecialchars($barcode)], 404);
    }

    $stock = (float)$item['stock_qty'];
    if ($stock <= 0) {
        jsonOut([
            'success' => false,
            'message' => 'Out of stock: ' . htmlspecialchars($item['item_name']),
            'out_of_stock' => true,
            'item' => $item,
        ], 200);
    }

    jsonOut([
        'success' => true,
        'item'    => [
            'item_id'             => (int)$item['item_id'],
            'barcode'             => $item['barcode'],
            'item_name'           => $item['item_name'],
            'unit'                => $item['unit'],
            'price'               => (float)$item['price'],
            'stock_qty'           => $stock,
            'low_stock_threshold' => (int)$item['low_stock_threshold'],
            'category_name'       => $item['category_name'],
        ],
    ]);
}

// ============================================================
// ACTION: search_items
// GET ?action=search_items&q=keyword&limit=20
// Searches active inventory by name, barcode, or category
// ============================================================
function handleSearchItems(): never {
    $q     = trim($_GET['q'] ?? '');
    $limit = min((int)($_GET['limit'] ?? 30), 100);

    $db = getDB();

    if ($q === '') {
        // Return all in-stock items (first $limit)
        $stmt = $db->prepare(
            "SELECT i.item_id, i.barcode, i.item_name, i.unit, i.price,
                    i.stock_qty, i.low_stock_threshold, c.category_name
             FROM   tbl_inventory i
             JOIN   tbl_categories c ON c.category_id = i.category_id
             WHERE  i.is_active = 1 AND i.stock_qty > 0
             ORDER  BY i.item_name ASC
             LIMIT  :lim"
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $like = '%' . $q . '%';
        $stmt = $db->prepare(
            "SELECT i.item_id, i.barcode, i.item_name, i.unit, i.price,
                    i.stock_qty, i.low_stock_threshold, c.category_name
             FROM   tbl_inventory i
             JOIN   tbl_categories c ON c.category_id = i.category_id
             WHERE  i.is_active = 1
               AND  (i.item_name LIKE :q OR i.barcode LIKE :q2 OR c.category_name LIKE :q3)
             ORDER  BY i.stock_qty DESC, i.item_name ASC
             LIMIT  :lim"
        );
        $stmt->bindValue(':q',   $like, \PDO::PARAM_STR);
        $stmt->bindValue(':q2',  $like, \PDO::PARAM_STR);
        $stmt->bindValue(':q3',  $like, \PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
    }

    $items = $stmt->fetchAll();
    foreach ($items as &$i) {
        $i['item_id']   = (int)$i['item_id'];
        $i['price']     = (float)$i['price'];
        $i['stock_qty'] = (float)$i['stock_qty'];
        $i['in_stock']  = $i['stock_qty'] > 0;
    }
    unset($i);

    jsonOut(['success' => true, 'items' => $items, 'count' => count($items)]);
}

// ============================================================
// ACTION: complete_sale
// POST JSON body:
//   { action, items: [{item_id, quantity, unit_price}], amount_tendered }
// ============================================================
function handleCompleteSale(array $body): never {
    $items          = $body['items']          ?? [];
    $amountTendered = (float)($body['amount_tendered'] ?? 0);
    $userId         = currentUserId();

    // ── Validate input ────────────────────────────────────────
    if (empty($items) || !is_array($items)) {
        jsonOut(['success' => false, 'message' => 'Cart is empty.'], 400);
    }
    if ($amountTendered <= 0) {
        jsonOut(['success' => false, 'message' => 'Amount tendered is required.'], 400);
    }

    $db = getDB();

    // ── Validate all items before opening transaction ─────────
    $validatedItems = [];
    $grandTotal     = 0.0;

    foreach ($items as $idx => $cartItem) {
        $itemId   = (int)($cartItem['item_id']   ?? 0);
        $qty      = (float)($cartItem['quantity']  ?? 0);
        $price    = (float)($cartItem['unit_price'] ?? 0);

        if ($itemId <= 0 || $qty <= 0 || $price < 0) {
            jsonOut(['success' => false, 'message' => "Invalid item data at index {$idx}."], 400);
        }

        // Fresh stock check
        $chk = $db->prepare(
            "SELECT item_id, item_name, stock_qty, price, unit
             FROM   tbl_inventory
             WHERE  item_id = :id AND is_active = 1
             LIMIT  1"
        );
        $chk->execute([':id' => $itemId]);
        $row = $chk->fetch();

        if (!$row) {
            jsonOut(['success' => false, 'message' => "Item ID {$itemId} not found or inactive."], 404);
        }
        if ((float)$row['stock_qty'] < $qty) {
            jsonOut([
                'success' => false,
                'message' => "Insufficient stock for '{$row['item_name']}'. Available: {$row['stock_qty']}.",
            ], 409);
        }

        $subtotal         = round($qty * $price, 2);
        $grandTotal      += $subtotal;
        $validatedItems[] = [
            'item_id'   => $itemId,
            'item_name' => $row['item_name'],
            'unit'      => $row['unit'],
            'quantity'  => $qty,
            'unit_price'=> $price,
            'subtotal'  => $subtotal,
        ];
    }

    // ── Check amount tendered ─────────────────────────────────
    $grandTotal = round($grandTotal, 2);
    if ($amountTendered < $grandTotal) {
        jsonOut([
            'success' => false,
            'message' => 'Amount tendered is less than total amount.',
        ], 400);
    }

    $changeAmount = round($amountTendered - $grandTotal, 2);

    // ── Generate transaction code: TXN-YYYYMMDD-#### ──────────
    $today   = date('Ymd');
    $prefix  = 'TXN-' . $today . '-';

    // Find the highest existing sequence for today's prefix
    $prefixLen = strlen($prefix);
    $seqStmt = $db->prepare(
        "SELECT MAX(CAST(SUBSTRING(transaction_code, {$prefixLen} + 1) AS UNSIGNED)) AS max_seq
         FROM   tbl_transactions
         WHERE  transaction_code LIKE :like"
    );
    $seqStmt->execute([':like' => $prefix . '%']);
    $maxSeq  = (int)($seqStmt->fetchColumn() ?: 0);
    $seq     = $maxSeq + 1;
    $transactionCode = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

    // ── PDO transaction: INSERT + stock deduction ─────────────
    try {
        $db->beginTransaction();

        // Insert header
        $insHdr = $db->prepare(
            "INSERT INTO tbl_transactions
                (transaction_code, user_id, total_amount, amount_tendered, change_amount, transaction_date)
             VALUES
                (:code, :uid, :total, :tendered, :change, NOW())"
        );
        $insHdr->execute([
            ':code'     => $transactionCode,
            ':uid'      => $userId,
            ':total'    => $grandTotal,
            ':tendered' => $amountTendered,
            ':change'   => $changeAmount,
        ]);
        $transactionId = (int)$db->lastInsertId();

        // Insert details + deduct stock
        $insDtl  = $db->prepare(
            "INSERT INTO tbl_transaction_details
                (transaction_id, item_id, quantity, unit_price, subtotal)
             VALUES
                (:txn_id, :item_id, :qty, :price, :sub)"
        );
        $deduct = $db->prepare(
            "UPDATE tbl_inventory
             SET    stock_qty = stock_qty - :qty
             WHERE  item_id = :id"
        );

        foreach ($validatedItems as $vi) {
            $insDtl->execute([
                ':txn_id'  => $transactionId,
                ':item_id' => $vi['item_id'],
                ':qty'     => $vi['quantity'],
                ':price'   => $vi['unit_price'],
                ':sub'     => $vi['subtotal'],
            ]);
            $deduct->execute([
                ':qty' => $vi['quantity'],
                ':id'  => $vi['item_id'],
            ]);
        }

        $db->commit();

    } catch (\Throwable $e) {
        $db->rollBack();
        error_log('complete_sale error: ' . $e->getMessage());
        jsonOut(['success' => false, 'message' => 'Transaction failed. Please try again.'], 500);
    }

    // ── Audit log ─────────────────────────────────────────────
    logAudit(
        $userId,
        'COMPLETE_SALE',
        'POS',
        "Transaction {$transactionCode} completed. Total: {$grandTotal}. Items: " . count($validatedItems)
    );

    // ── Return receipt data ───────────────────────────────────
    jsonOut([
        'success'          => true,
        'message'          => 'Sale completed successfully.',
        'transaction_id'   => $transactionId,
        'transaction_code' => $transactionCode,
        'transaction_date' => date('Y-m-d H:i:s'),
        'cashier_name'     => currentUserName(),
        'items'            => $validatedItems,
        'subtotal'         => $grandTotal,         // subtotal before VAT (VAT-inclusive pricing)
        'vat_amount'       => round($grandTotal * 12 / 112, 2), // VAT-inclusive breakdown
        'grand_total'      => $grandTotal,
        'amount_tendered'  => $amountTendered,
        'change_amount'    => $changeAmount,
    ]);
}

// ============================================================
// ACTION: daily_summary
// Returns today's transaction count and total sales
// ============================================================
function handleDailySummary(): never {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT COUNT(*)         AS transaction_count,
                COALESCE(SUM(total_amount), 0) AS total_sales
         FROM   tbl_transactions
         WHERE  DATE(transaction_date) = CURDATE()
           AND  (status IS NULL OR status != 'voided')"
    );
    $stmt->execute();
    $row = $stmt->fetch();

    jsonOut([
        'success'           => true,
        'transaction_count' => (int)$row['transaction_count'],
        'total_sales'       => (float)$row['total_sales'],
        'total_sales_fmt'   => peso((float)$row['total_sales']),
        'date'              => date('Y-m-d'),
    ]);
}

// ============================================================
// ACTION: void_transaction
// Admin only. Voids a transaction within the last 24 hours.
// POST JSON: { action, transaction_id }
// ============================================================
function handleVoidTransaction(array $body): never {
    // Admin-only
    if (!isAdmin()) {
        jsonOut(['success' => false, 'message' => 'Unauthorized. Admin only.'], 403);
    }

    $txnId = (int)($body['transaction_id'] ?? 0);
    if ($txnId <= 0) {
        jsonOut(['success' => false, 'message' => 'transaction_id is required.'], 400);
    }

    $db = getDB();

    // Fetch transaction (must exist and be within 24h)
    $stmt = $db->prepare(
        "SELECT transaction_id, transaction_code, total_amount, status, transaction_date
         FROM   tbl_transactions
         WHERE  transaction_id = :id
         LIMIT  1"
    );
    $stmt->execute([':id' => $txnId]);
    $txn = $stmt->fetch();

    if (!$txn) {
        jsonOut(['success' => false, 'message' => 'Transaction not found.'], 404);
    }
    if (($txn['status'] ?? '') === 'voided') {
        jsonOut(['success' => false, 'message' => 'Transaction is already voided.'], 409);
    }

    // Within last 24 hours check
    $txnTime  = strtotime($txn['transaction_date']);
    $cutoff   = time() - (24 * 3600);
    if ($txnTime < $cutoff) {
        jsonOut([
            'success' => false,
            'message' => 'Cannot void transaction older than 24 hours.',
        ], 403);
    }

    // Fetch details to restore stock
    $detStmt = $db->prepare(
        "SELECT item_id, quantity FROM tbl_transaction_details WHERE transaction_id = :id"
    );
    $detStmt->execute([':id' => $txnId]);
    $details = $detStmt->fetchAll();

    try {
        $db->beginTransaction();

        // Check if status column exists; add a soft status field
        // Mark transaction as voided
        $voidStmt = $db->prepare(
            "UPDATE tbl_transactions SET status = 'voided' WHERE transaction_id = :id"
        );
        $voidStmt->execute([':id' => $txnId]);

        // Restore stock for each item
        $restore = $db->prepare(
            "UPDATE tbl_inventory SET stock_qty = stock_qty + :qty WHERE item_id = :id"
        );
        foreach ($details as $d) {
            $restore->execute([':qty' => $d['quantity'], ':id' => $d['item_id']]);
        }

        $db->commit();

    } catch (\Throwable $e) {
        $db->rollBack();
        error_log('void_transaction error: ' . $e->getMessage());
        jsonOut(['success' => false, 'message' => 'Void failed. Please try again.'], 500);
    }

    logAudit(
        currentUserId(),
        'VOID_TRANSACTION',
        'POS',
        "Voided transaction {$txn['transaction_code']} (ID: {$txnId}). Amount: {$txn['total_amount']}"
    );

    jsonOut([
        'success'          => true,
        'message'          => "Transaction {$txn['transaction_code']} has been voided and stock restored.",
        'transaction_code' => $txn['transaction_code'],
    ]);
}

// ============================================================
// ACTION: recent_transactions
// Returns last 10 transactions for the cashier view
// ============================================================
function handleRecentTransactions(): never {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT t.transaction_id, t.transaction_code, t.total_amount,
                t.amount_tendered, t.change_amount,
                t.transaction_date, t.status,
                u.full_name AS cashier_name,
                COUNT(d.detail_id) AS item_count
         FROM   tbl_transactions t
         JOIN   tbl_users u ON u.user_id = t.user_id
         LEFT   JOIN tbl_transaction_details d ON d.transaction_id = t.transaction_id
         GROUP  BY t.transaction_id
         ORDER  BY t.transaction_date DESC
         LIMIT  10"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Format amounts
    foreach ($rows as &$r) {
        $r['total_amount_fmt']    = peso((float)$r['total_amount']);
        $r['amount_tendered_fmt'] = peso((float)$r['amount_tendered']);
        $r['change_amount_fmt']   = peso((float)$r['change_amount']);
        $r['item_count']          = (int)$r['item_count'];
        $r['is_voided']           = ($r['status'] ?? '') === 'voided';
    }
    unset($r);

    jsonOut(['success' => true, 'transactions' => $rows]);
}
