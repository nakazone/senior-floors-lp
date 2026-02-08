<?php
/**
 * API: Get Quote by public token (read-only). Marks viewed on first view.
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../libs/quotes-helper.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if ($token === '' || !isDatabaseConfigured()) {
    if ($token === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'token required']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database not configured']);
    }
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    if ($pdo->query("SHOW COLUMNS FROM quotes LIKE 'public_token'")->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT q.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE q.public_token = ?");
    $stmt->execute([$token]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote && $pdo->query("SHOW TABLES LIKE 'customers'")->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT q.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone FROM quotes q LEFT JOIN customers c ON c.id = q.customer_id WHERE q.public_token = ?");
        $stmt->execute([$token]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($quote) {
            $quote['lead_name'] = $quote['customer_name'] ?? null;
            $quote['lead_email'] = $quote['customer_email'] ?? null;
            $quote['lead_phone'] = $quote['customer_phone'] ?? null;
        }
    }
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    $allowed = ['sent', 'viewed', 'accepted', 'declined', 'approved', 'rejected'];
    if (!in_array($quote['status'], $allowed, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Quote not available']);
        exit;
    }
    if ($quote['status'] === 'sent') {
        $pdo->prepare("UPDATE quotes SET status = 'viewed', viewed_at = NOW() WHERE id = ?")->execute([$quote['id']]);
        $quote['status'] = 'viewed';
        if ($pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0) {
            quotes_log_activity($pdo, $quote['id'], 'viewed', 'client', 'client', null);
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id");
    $stmt->execute([$quote['id']]);
    $quote['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $quote['client_name'] = $quote['lead_name'] ?? $quote['customer_name'] ?? '';
    $quote['client_email'] = $quote['lead_email'] ?? $quote['customer_email'] ?? '';
    echo json_encode(['success' => true, 'data' => $quote]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
