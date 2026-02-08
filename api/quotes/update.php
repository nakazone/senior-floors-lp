<?php
/**
 * API: Atualizar orçamento (status: draft, sent, viewed, approved, rejected)
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/../../config/database.php';
if (file_exists(__DIR__ . '/../../config/audit.php')) require_once __DIR__ . '/../../config/audit.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$valid = ['draft','sent','viewed','approved','rejected','accepted','declined','expired'];
if ($id <= 0 || !in_array($status, $valid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id and status (draft|sent|viewed|approved|rejected) required']);
    exit;
}

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    $old = $pdo->prepare("SELECT status FROM quotes WHERE id = ?");
    $old->execute([$id]);
    $old_row = $old->fetch(PDO::FETCH_ASSOC);
    $old_status = $old_row ? $old_row['status'] : null;

    $updates = "status = :status";
    $params = [':status' => $status, ':id' => $id];
    if ($status === 'sent') { $updates .= ", sent_at = NOW()"; }
    if ($status === 'viewed') { $updates .= ", viewed_at = NOW()"; }
    if ($status === 'approved') { $updates .= ", approved_at = NOW()"; }
    $pdo->prepare("UPDATE quotes SET $updates WHERE id = :id")->execute($params);

    if (function_exists('logAudit') && $old_status !== null) {
        logAudit('quote', $id, 'status_change', 'status', $old_status, $status, function_exists('auditCurrentUserId') ? auditCurrentUserId() : null);
    }
    echo json_encode(['success' => true, 'data' => ['id' => $id, 'status' => $status], 'timestamp' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
