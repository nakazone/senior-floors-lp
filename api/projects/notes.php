<?php
/**
 * API Endpoint: Adicionar Nota ao Project
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';
$created_by = isset($_POST['created_by']) ? trim($_POST['created_by']) : 'admin';

if ($project_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

if (empty($note)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Note is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
    $created_by = htmlspecialchars($created_by, ENT_QUOTES, 'UTF-8');
    
    $stmt = $pdo->prepare("INSERT INTO project_notes (project_id, note, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$project_id, $note, $created_by]);
    
    // Log activity
    $activity_stmt = $pdo->prepare("
        INSERT INTO activities (project_id, activity_type, subject, description, related_to)
        VALUES (?, 'note', 'Note Added', ?, 'project')
    ");
    $activity_stmt->execute([$project_id, $note]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
