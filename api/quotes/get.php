<?php
/**
 * API: Get Quote by ID (full detail: items, activity log, client/lead)
 */
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
    $stmt = $pdo->prepare("SELECT q.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone, l.address as lead_address FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE q.id = ?");
    $stmt->execute([$id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        $stmt = $pdo->prepare("SELECT q.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address FROM quotes q LEFT JOIN customers c ON c.id = q.customer_id WHERE q.id = ?");
        $stmt->execute([$id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $quote['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($quote['customer_id']) && $pdo->query("SHOW TABLES LIKE 'customers'")->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, address, tax_id FROM customers WHERE id = ?");
        $stmt->execute([$quote['customer_id']]);
        $quote['client'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    if (empty($quote['client'])) {
        $quote['client'] = [
            'name' => $quote['lead_name'] ?? null,
            'email' => $quote['lead_email'] ?? null,
            'phone' => $quote['lead_phone'] ?? null,
            'address' => $quote['lead_address'] ?? null,
        ];
    }
    $has_log = $pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0;
    $quote['activity_log'] = [];
    if ($has_log) {
        $stmt = $pdo->prepare("SELECT id, quote_id, action, performed_by, performed_by_type, metadata, created_at FROM quote_activity_log WHERE quote_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$id]);
        $quote['activity_log'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode(['success' => true, 'data' => $quote]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
