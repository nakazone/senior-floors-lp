<?php
/**
 * API Endpoint: Listar Users (Sales Reps)
 * 
 * Endpoint: GET /api/users/list.php
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

$is_active = isset($_GET['is_active']) ? (int)$_GET['is_active'] : null;
$role = isset($_GET['role']) ? trim($_GET['role']) : null;

try {
    $pdo = getDBConnection();
    
    // Build query
    $where = [];
    $params = [];
    
    if ($is_active !== null) {
        $where[] = "is_active = ?";
        $params[] = $is_active;
    }
    
    if ($role) {
        $where[] = "role = ?";
        $params[] = $role;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get users
    $sql = "SELECT id, name, email, phone, role, is_active FROM users $where_clause ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
