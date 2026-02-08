<?php
/**
 * API: Send Quote - set public_token, status=sent, return public link
 * POST id
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../libs/quotes-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id required']);
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
    $stmt = $pdo->prepare("SELECT id, status, public_token FROM quotes WHERE id = ?");
    $stmt->execute([$id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    if (!in_array($quote['status'], ['draft'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only draft quotes can be sent']);
        exit;
    }
    $has_public_token = $pdo->query("SHOW COLUMNS FROM quotes LIKE 'public_token'")->rowCount() > 0;
    $token = null;
    if ($has_public_token) {
        $token = $quote['public_token'] ?: quotes_generate_public_token();
        $pdo->prepare("UPDATE quotes SET status = 'sent', sent_at = NOW(), public_token = COALESCE(NULLIF(public_token,''), ?) WHERE id = ?")->execute([$token, $id]);
    } else {
        $pdo->prepare("UPDATE quotes SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$id]);
    }
    $has_log = $pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0;
    if ($has_log) {
        quotes_log_activity($pdo, $id, 'sent', null, 'user', ['token_set' => (bool)$token]);
    }
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $public_link = $token ? ($base_url . '/quote-view.php?token=' . urlencode($token)) : null;
    echo json_encode([
        'success' => true,
        'data' => ['id' => $id, 'status' => 'sent', 'public_token' => $token, 'public_link' => $public_link],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
