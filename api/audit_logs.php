<?php
// ============================================================
// API: Audit Logs — Sharon Store System
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();
header('Content-Type: application/json');

$db     = getDB();
$page   = max(1, (int)($_GET['page']  ?? 1));
$limit  = min(50, max(10, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if (!empty($_GET['user_id']))  { $where[] = 'l.user_id = :uid'; $params[':uid'] = (int)$_GET['user_id']; }
if (!empty($_GET['module']))   { $where[] = 'l.module = :mod';  $params[':mod'] = $_GET['module']; }
if (!empty($_GET['search']))   { $where[] = '(l.action LIKE :srch OR l.description LIKE :srch2)'; $params[':srch'] = '%'.$_GET['search'].'%'; $params[':srch2'] = '%'.$_GET['search'].'%'; }
if (!empty($_GET['date_from'])) { $where[] = 'DATE(l.log_time) >= :df'; $params[':df'] = $_GET['date_from']; }
if (!empty($_GET['date_to']))   { $where[] = 'DATE(l.log_time) <= :dt'; $params[':dt'] = $_GET['date_to']; }

$wc = 'WHERE ' . implode(' AND ', $where);

// ── COUNT total rows ─────────────────────────────────────────
$cntStmt = $db->prepare("SELECT COUNT(*) FROM tbl_audit_logs l $wc");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

// ── Fetch page ───────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT l.log_id, l.user_id, l.action, l.module, l.description,
           l.ip_address, l.log_time, u.full_name, u.role
    FROM   tbl_audit_logs l
    LEFT JOIN tbl_users u ON u.user_id = l.user_id
    $wc
    ORDER BY l.log_time DESC
    LIMIT  :lim OFFSET :off
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// ── Filter dropdown data ─────────────────────────────────────
$modules = $db->query("SELECT DISTINCT module FROM tbl_audit_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);
$users   = $db->query("SELECT user_id, full_name FROM tbl_users ORDER BY full_name ASC")->fetchAll();

echo json_encode([
    'success'     => true,
    'data'        => $logs,
    'total'       => $total,
    'page'        => $page,
    'per_page'    => $limit,
    'total_pages' => (int)ceil($total / $limit),
    'modules'     => $modules,
    'users'       => $users,
]);
