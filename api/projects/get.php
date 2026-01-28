<?php
/**
 * API Endpoint: Buscar Project
 * 
 * Endpoint: GET /api/projects/get.php?id=123
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get project with customer info
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone
        FROM projects p
        LEFT JOIN customers c ON c.id = p.customer_id
        WHERE p.id = ?
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }
    
    // Get notes
    $notes_stmt = $pdo->prepare("SELECT * FROM project_notes WHERE project_id = ? ORDER BY created_at DESC");
    $notes_stmt->execute([$project_id]);
    $project['notes'] = $notes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tags
    $tags_stmt = $pdo->prepare("SELECT tag_name FROM project_tags WHERE project_id = ?");
    $tags_stmt->execute([$project_id]);
    $project['tags'] = array_column($tags_stmt->fetchAll(PDO::FETCH_ASSOC), 'tag_name');
    
    // Get activities
    $activities_stmt = $pdo->prepare("SELECT * FROM activities WHERE project_id = ? ORDER BY activity_date DESC LIMIT 50");
    $activities_stmt->execute([$project_id]);
    $project['activities'] = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'project' => $project
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
