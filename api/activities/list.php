<?php
/**
 * API Endpoint: Listar Activities
 * 
 * Endpoint: GET /api/activities/list.php?lead_id=1&customer_id=1&project_id=1
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

$lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : null;
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
$activity_type = isset($_GET['activity_type']) ? trim($_GET['activity_type']) : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 50;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getDBConnection();
    
    // Build query
    $where = [];
    $params = [];
    
    if ($lead_id) {
        $where[] = "a.lead_id = ?";
        $params[] = $lead_id;
    }
    
    if ($customer_id) {
        $where[] = "a.customer_id = ?";
        $params[] = $customer_id;
    }
    
    if ($project_id) {
        $where[] = "a.project_id = ?";
        $params[] = $project_id;
    }
    
    if ($activity_type) {
        $where[] = "a.activity_type = ?";
        $params[] = $activity_type;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM activities a $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get activities
    $sql = "
        SELECT a.*
        FROM activities a
        $where_clause
        ORDER BY a.activity_date DESC, a.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
