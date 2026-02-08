<?php
/**
 * API: Client Decline Quote (by public token)
 * POST token, reason (optional)
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../libs/quotes-helper.php';

$token = isset($_POST['token']) ? trim($_POST['token']) : (isset($_GET['token']) ? trim($_GET['token']) : '');
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
if ($token === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'token required']);
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
    $has_token = $pdo->query("SHOW COLUMNS FROM quotes LIKE 'public_token'")->rowCount() > 0;
    $has_declined = $pdo->query("SHOW COLUMNS FROM quotes LIKE 'declined_at'")->rowCount() > 0;
    if (!$has_token) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM quotes WHERE public_token = ?");
    $stmt->execute([$token]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    if (!quotes_can_accept_or_decline($quote)) {
        echo json_encode(['success' => false, 'message' => 'Quote cannot be declined (wrong status or already accepted/declined)']);
        exit;
    }
    if ($has_declined) {
        $pdo->prepare("UPDATE quotes SET status = 'declined', declined_at = NOW(), decline_reason = ? WHERE id = ?")->execute([$reason ?: null, $quote['id']]);
    } else {
        $pdo->prepare("UPDATE quotes SET status = 'declined' WHERE id = ?")->execute([$quote['id']]);
    }
    $has_log = $pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0;
    if ($has_log) {
        quotes_log_activity($pdo, $quote['id'], 'declined', 'client', 'client', ['reason' => $reason]);
    }
    echo json_encode([
        'success' => true,
        'data' => ['id' => $quote['id'], 'status' => 'declined', 'message' => 'Quote declined'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
