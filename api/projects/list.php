<?php
/**
 * API Endpoint: Listar Projects
 * 
 * Endpoint: GET /api/projects/list.php?status=completed&customer_id=1&page=1
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

$status = isset($_GET['status']) ? trim($_GET['status']) : null;
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
$project_type = isset($_GET['project_type']) ? trim($_GET['project_type']) : null;
$post_service_status = isset($_GET['post_service_status']) ? trim($_GET['post_service_status']) : null;
$owner_id = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getDBConnection();
    
    // Build query
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "p.status = ?";
        $params[] = $status;
    }
    
    if ($customer_id) {
        $where[] = "p.customer_id = ?";
        $params[] = $customer_id;
    }
    
    if ($project_type) {
        $where[] = "p.project_type = ?";
        $params[] = $project_type;
    }
    
    if ($post_service_status) {
        $where[] = "p.post_service_status = ?";
        $params[] = $post_service_status;
    }
    
    if ($owner_id) {
        $where[] = "p.owner_id = ?";
        $params[] = $owner_id;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM projects p $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get projects with customer info
    $sql = "
        SELECT 
            p.*,
            c.name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone
        FROM projects p
        LEFT JOIN customers c ON c.id = p.customer_id
        $where_clause
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'projects' => $projects,
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
