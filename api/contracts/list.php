<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/database.php';

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    $has = $pdo->query("SHOW TABLES LIKE 'contracts'")->rowCount() > 0;
    if (!$has) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $sql = "SELECT c.*, l.name as lead_name FROM contracts c LEFT JOIN leads l ON l.id = c.lead_id WHERE 1=1";
    $params = [];
    if (!empty($_GET['lead_id'])) { $sql .= " AND c.lead_id = :lead_id"; $params[':lead_id'] = (int)$_GET['lead_id']; }
    if (!empty($_GET['project_id'])) { $sql .= " AND c.project_id = :project_id"; $params[':project_id'] = (int)$_GET['project_id']; }
    $sql .= " ORDER BY c.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
