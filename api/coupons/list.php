<?php
/**
 * API Endpoint: Listar Coupons
 * 
 * Endpoint: GET /api/coupons/list.php?is_active=1
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

try {
    $pdo = getDBConnection();
    
    // Build query
    $where = [];
    $params = [];
    
    if ($is_active !== null) {
        $where[] = "is_active = ?";
        $params[] = $is_active;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get coupons
    $sql = "SELECT * FROM coupons $where_clause ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'coupons' => $coupons
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
