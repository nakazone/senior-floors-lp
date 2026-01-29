<?php
/**
 * API: Obter visita por ID (com medições e anexos)
 * GET /api/visits/get.php?id=1
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
    $stmt = $pdo->prepare("SELECT * FROM visits WHERE id = ?");
    $stmt->execute([$id]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$visit) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Visit not found']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM measurements WHERE visit_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $visit['measurements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $has_att = $pdo->query("SHOW TABLES LIKE 'visit_attachments'")->rowCount() > 0;
    $visit['attachments'] = [];
    if ($has_att) {
        $stmt = $pdo->prepare("SELECT * FROM visit_attachments WHERE visit_id = ?");
        $stmt->execute([$id]);
        $visit['attachments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode(['success' => true, 'data' => $visit]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
