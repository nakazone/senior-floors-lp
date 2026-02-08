<?php
/**
 * API: Get Quote PDF (stub - returns quote PDF path or generates link)
 * GET id
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
    $stmt = $pdo->prepare("SELECT id, pdf_path, quote_number FROM quotes WHERE id = ?");
    $stmt->execute([$id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    $pdf_path = $quote['pdf_path'] ?? null;
    $pdf_url = $pdf_path ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/' . ltrim($pdf_path, '/')) : null;
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $id,
            'quote_number' => $quote['quote_number'] ?? (string)$id,
            'pdf_path' => $pdf_path,
            'pdf_url' => $pdf_url,
            'message' => $pdf_url ? 'PDF available' : 'PDF not generated yet. Use Print to PDF from the quote preview page.',
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
