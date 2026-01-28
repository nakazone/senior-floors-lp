<?php
/**
 * API Endpoint: Histórico de Atribuições
 * 
 * Endpoint: GET /api/assignment/history.php?lead_id=1&customer_id=1&project_id=1
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

try {
    $pdo = getDBConnection();
    
    // Build query
    $where = [];
    $params = [];
    
    if ($lead_id) {
        $where[] = "lead_id = ?";
        $params[] = $lead_id;
    }
    
    if ($customer_id) {
        $where[] = "customer_id = ?";
        $params[] = $customer_id;
    }
    
    if ($project_id) {
        $where[] = "project_id = ?";
        $params[] = $project_id;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get assignment history
    $sql = "
        SELECT 
            ah.*,
            u_from.name as from_user_name,
            u_to.name as to_user_name,
            u_assigned.name as assigned_by_name
        FROM assignment_history ah
        LEFT JOIN users u_from ON u_from.id = ah.from_user_id
        LEFT JOIN users u_to ON u_to.id = ah.to_user_id
        LEFT JOIN users u_assigned ON u_assigned.id = ah.assigned_by
        $where_clause
        ORDER BY ah.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
