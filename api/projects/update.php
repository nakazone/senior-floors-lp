<?php
/**
 * API Endpoint: Atualizar Project
 * 
 * Endpoint: POST /api/projects/update.php
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

if ($project_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit;
}

// Get update fields
$updates = [];
$params = [];

if (isset($_POST['name']) && !empty($_POST['name'])) {
    $updates[] = "name = ?";
    $params[] = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['project_type'])) {
    $valid_types = ['installation', 'refinishing', 'repair', 'maintenance'];
    if (in_array($_POST['project_type'], $valid_types)) {
        $updates[] = "project_type = ?";
        $params[] = $_POST['project_type'];
    }
}

if (isset($_POST['status'])) {
    $valid_statuses = ['quoted', 'scheduled', 'in_progress', 'completed', 'cancelled', 'on_hold'];
    if (in_array($_POST['status'], $valid_statuses)) {
        $updates[] = "status = ?";
        $params[] = $_POST['status'];
    }
}

if (isset($_POST['post_service_status'])) {
    $valid_post_statuses = ['installation_scheduled', 'installation_completed', 'follow_up_sent', 'review_requested', 'warranty_active'];
    if (in_array($_POST['post_service_status'], $valid_post_statuses)) {
        $updates[] = "post_service_status = ?";
        $params[] = $_POST['post_service_status'];
    }
}

if (isset($_POST['address'])) {
    $updates[] = "address = ?";
    $params[] = htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['city'])) {
    $updates[] = "city = ?";
    $params[] = htmlspecialchars(trim($_POST['city']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['state'])) {
    $updates[] = "state = ?";
    $params[] = htmlspecialchars(trim($_POST['state']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['zipcode'])) {
    $updates[] = "zipcode = ?";
    $params[] = htmlspecialchars(trim($_POST['zipcode']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['estimated_start_date'])) {
    $updates[] = "estimated_start_date = ?";
    $params[] = $_POST['estimated_start_date'] ?: null;
}

if (isset($_POST['estimated_end_date'])) {
    $updates[] = "estimated_end_date = ?";
    $params[] = $_POST['estimated_end_date'] ?: null;
}

if (isset($_POST['actual_start_date'])) {
    $updates[] = "actual_start_date = ?";
    $params[] = $_POST['actual_start_date'] ?: null;
}

if (isset($_POST['actual_end_date'])) {
    $updates[] = "actual_end_date = ?";
    $params[] = $_POST['actual_end_date'] ?: null;
}

if (isset($_POST['estimated_cost'])) {
    $updates[] = "estimated_cost = ?";
    $params[] = (float)$_POST['estimated_cost'];
}

if (isset($_POST['actual_cost'])) {
    $updates[] = "actual_cost = ?";
    $params[] = (float)$_POST['actual_cost'];
}

if (isset($_POST['owner_id'])) {
    $updates[] = "owner_id = ?";
    $params[] = (int)$_POST['owner_id'];
}

if (isset($_POST['notes'])) {
    $updates[] = "notes = ?";
    $params[] = htmlspecialchars(trim($_POST['notes']), ENT_QUOTES, 'UTF-8');
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $params[] = $project_id;
    $sql = "UPDATE projects SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Log activity
    $activity_stmt = $pdo->prepare("
        INSERT INTO activities (project_id, activity_type, subject, description, related_to)
        VALUES (?, 'status_change', 'Project Updated', 'Project information was updated', 'project')
    ");
    $activity_stmt->execute([$project_id]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Project updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
