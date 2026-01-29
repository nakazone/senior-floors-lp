<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
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
    $stmt = $pdo->prepare("SELECT q.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE q.id = ?");
    $stmt->execute([$id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $quote['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $quote]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
