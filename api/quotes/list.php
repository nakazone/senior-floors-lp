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
    $has = $pdo->query("SHOW TABLES LIKE 'quotes'")->rowCount() > 0;
    if (!$has) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $sql = "SELECT q.*, l.name as lead_name FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE 1=1";
    $params = [];
    if (!empty($_GET['lead_id'])) { $sql .= " AND q.lead_id = :lead_id"; $params[':lead_id'] = (int)$_GET['lead_id']; }
    if (!empty($_GET['customer_id'])) { $sql .= " AND q.customer_id = :customer_id"; $params[':customer_id'] = (int)$_GET['customer_id']; }
    if (!empty($_GET['project_id'])) { $sql .= " AND q.project_id = :project_id"; $params[':project_id'] = (int)$_GET['project_id']; }
    if (!empty($_GET['status'])) { $sql .= " AND q.status = :status"; $params[':status'] = $_GET['status']; }
    if (!empty($_GET['search'])) {
        $search = '%' . trim($_GET['search']) . '%';
        if ($pdo->query("SHOW COLUMNS FROM quotes LIKE 'quote_number'")->rowCount() > 0) {
            $sql .= " AND (q.quote_number LIKE :search OR l.name LIKE :search2)";
            $params[':search'] = $search;
            $params[':search2'] = $search;
        } else {
            $sql .= " AND l.name LIKE :search";
            $params[':search'] = $search;
        }
    }
    if (!empty($_GET['date_from'])) { $sql .= " AND q.created_at >= :date_from"; $params[':date_from'] = $_GET['date_from'] . ' 00:00:00'; }
    if (!empty($_GET['date_to'])) { $sql .= " AND q.created_at <= :date_to"; $params[':date_to'] = $_GET['date_to'] . ' 23:59:59'; }
    $sql .= " ORDER BY q.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
