<?php
/**
 * API: Convert accepted quote to Project. POST id (quote_id)
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0 || !isDatabaseConfigured()) {
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id required']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database not configured']);
    }
    exit;
}
try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = ?");
    $stmt->execute([$id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$quote) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Quote not found']);
        exit;
    }
    if (!in_array($quote['status'], ['accepted', 'approved'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only accepted/approved quotes can be converted']);
        exit;
    }
    if ($pdo->query("SHOW TABLES LIKE 'projects'")->rowCount() === 0) {
        http_response_code(501);
        echo json_encode(['success' => false, 'message' => 'Projects table not found']);
        exit;
    }
    $customer_id = $quote['customer_id'] ?? null;
    if (!$customer_id && $quote['lead_id'] && $pdo->query("SHOW TABLES LIKE 'customers'")->rowCount() > 0) {
        $st = $pdo->prepare("SELECT id FROM customers WHERE lead_id = ? LIMIT 1");
        $st->execute([$quote['lead_id']]);
        $customer_id = $st->fetchColumn();
    }
    if (!$customer_id) {
        echo json_encode(['success' => false, 'message' => 'Link quote to customer or create customer from lead first']);
        exit;
    }
    $name = 'Projeto Orç. ' . ($quote['quote_number'] ?? $quote['id']);
    $st = $pdo->prepare("INSERT INTO projects (customer_id, lead_id, name, status) VALUES (?, ?, ?, 'pending')");
    $st->execute([$customer_id, $quote['lead_id'] ?: null, $name]);
    $project_id = (int)$pdo->lastInsertId();
    if ($pdo->query("SHOW COLUMNS FROM projects LIKE 'quote_id'")->rowCount() > 0) {
        $pdo->prepare("UPDATE projects SET quote_id = ? WHERE id = ?")->execute([$id, $project_id]);
    }
    echo json_encode(['success' => true, 'data' => ['project_id' => $project_id], 'timestamp' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
