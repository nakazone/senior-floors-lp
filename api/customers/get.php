<?php
/**
 * API Endpoint: Buscar Customer
 * 
 * Endpoint: GET /api/customers/get.php?id=123
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get customer
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit;
    }
    
    // Get notes
    $notes_stmt = $pdo->prepare("SELECT * FROM customer_notes WHERE customer_id = ? ORDER BY created_at DESC");
    $notes_stmt->execute([$customer_id]);
    $customer['notes'] = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tags
    $tags_stmt = $pdo->prepare("SELECT tag_name FROM customer_tags WHERE customer_id = ?");
    $tags_stmt->execute([$customer_id]);
    $customer['tags'] = array_column($tags_stmt->fetchAll(PDO::FETCH_ASSOC), 'tag_name');
    
    // Get projects
    $projects_stmt = $pdo->prepare("SELECT * FROM projects WHERE customer_id = ? ORDER BY created_at DESC");
    $projects_stmt->execute([$customer_id]);
    $customer['projects'] = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get activities
    $activities_stmt = $pdo->prepare("SELECT * FROM activities WHERE customer_id = ? ORDER BY activity_date DESC LIMIT 50");
    $activities_stmt->execute([$customer_id]);
    $customer['activities'] = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'customer' => $customer
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
