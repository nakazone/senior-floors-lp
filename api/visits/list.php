<?php
/**
 * API: Listar visitas (com filtros lead_id, customer_id, project_id, seller_id, status)
 * GET /api/visits/list.php?lead_id=1&status=scheduled
 */
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
    $has_table = $pdo->query("SHOW TABLES LIKE 'visits'")->rowCount() > 0;
    if (!$has_table) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $sql = "SELECT v.*, l.name as lead_name, l.phone as lead_phone FROM visits v LEFT JOIN leads l ON l.id = v.lead_id WHERE 1=1";
    $params = [];
    if (!empty($_GET['lead_id'])) { $sql .= " AND v.lead_id = :lead_id"; $params[':lead_id'] = (int)$_GET['lead_id']; }
    if (!empty($_GET['customer_id'])) { $sql .= " AND v.customer_id = :customer_id"; $params[':customer_id'] = (int)$_GET['customer_id']; }
    if (!empty($_GET['project_id'])) { $sql .= " AND v.project_id = :project_id"; $params[':project_id'] = (int)$_GET['project_id']; }
    if (!empty($_GET['seller_id'])) { $sql .= " AND v.seller_id = :seller_id"; $params[':seller_id'] = (int)$_GET['seller_id']; }
    if (!empty($_GET['status'])) { $sql .= " AND v.status = :status"; $params[':status'] = $_GET['status']; }
    $sql .= " ORDER BY v.scheduled_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $list]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
