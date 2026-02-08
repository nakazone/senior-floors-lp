<?php
/**
 * API: Client Accept Quote (by public token). POST token
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../libs/quotes-helper.php';

$token = isset($_POST['token']) ? trim($_POST['token']) : (isset($_GET['token']) ? trim($_GET['token']) : '');
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
    $has_expiration = $pdo->query("SHOW COLUMNS FROM quotes LIKE 'expiration_date'")->rowCount() > 0;
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
        $exp = $has_expiration ? ($quote['expiration_date'] ?? null) : null;
        if ($exp && strtotime($exp) < time()) {
            $pdo->prepare("UPDATE quotes SET status = 'expired' WHERE id = ?")->execute([$quote['id']]);
            echo json_encode(['success' => false, 'message' => 'Quote has expired']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Quote cannot be accepted']);
        }
        exit;
    }
    $pdo->prepare("UPDATE quotes SET status = 'accepted', approved_at = NOW() WHERE id = ?")->execute([$quote['id']]);
    $has_log = $pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0;
    if ($has_log) {
        quotes_log_activity($pdo, $quote['id'], 'accepted', 'client', 'client', ['accepted_at' => date('Y-m-d H:i:s')]);
    }
    echo json_encode([
        'success' => true,
        'data' => ['id' => $quote['id'], 'status' => 'accepted', 'message' => 'Quote accepted successfully'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
