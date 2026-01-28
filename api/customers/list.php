<?php
/**
 * API Endpoint: Listar Customers
 * 
 * Endpoint: GET /api/customers/list.php?status=active&owner_id=1&page=1
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
$owner_id = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : null;
$customer_type = isset($_GET['customer_type']) ? trim($_GET['customer_type']) : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getDBConnection();
    
    // Build query
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "c.status = ?";
        $params[] = $status;
    }
    
    if ($owner_id) {
        $where[] = "c.owner_id = ?";
        $params[] = $owner_id;
    }
    
    if ($customer_type) {
        $where[] = "c.customer_type = ?";
        $params[] = $customer_type;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM customers c $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get customers
    $sql = "
        SELECT 
            c.*,
            COUNT(DISTINCT p.id) as project_count,
            COUNT(DISTINCT a.id) as activity_count
        FROM customers c
        LEFT JOIN projects p ON p.customer_id = c.id
        LEFT JOIN activities a ON a.customer_id = c.id
        $where_clause
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'customers' => $customers,
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
