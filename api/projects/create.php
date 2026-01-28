<?php
/**
 * API Endpoint: Criar Project
 * 
 * Endpoint: POST /api/projects/create.php
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

// Get data
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$project_type = isset($_POST['project_type']) ? trim($_POST['project_type']) : 'installation';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'quoted';
$post_service_status = isset($_POST['post_service_status']) ? trim($_POST['post_service_status']) : null;
$address = isset($_POST['address']) ? trim($_POST['address']) : null;
$city = isset($_POST['city']) ? trim($_POST['city']) : null;
$state = isset($_POST['state']) ? trim($_POST['state']) : null;
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : null;
$estimated_start_date = isset($_POST['estimated_start_date']) ? trim($_POST['estimated_start_date']) : null;
$estimated_end_date = isset($_POST['estimated_end_date']) ? trim($_POST['estimated_end_date']) : null;
$estimated_cost = isset($_POST['estimated_cost']) ? (float)$_POST['estimated_cost'] : null;
$owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

// Validation
$errors = [];
if ($customer_id <= 0) {
    $errors[] = 'Customer ID is required';
}
if (empty($name)) {
    $errors[] = 'Project name is required';
}

$valid_types = ['installation', 'refinishing', 'repair', 'maintenance'];
if (!in_array($project_type, $valid_types)) {
    $errors[] = 'Invalid project type';
}

$valid_statuses = ['quoted', 'scheduled', 'in_progress', 'completed', 'cancelled', 'on_hold'];
if (!in_array($status, $valid_statuses)) {
    $errors[] = 'Invalid status';
}

if ($post_service_status) {
    $valid_post_statuses = ['installation_scheduled', 'installation_completed', 'follow_up_sent', 'review_requested', 'warranty_active'];
    if (!in_array($post_service_status, $valid_post_statuses)) {
        $errors[] = 'Invalid post service status';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Sanitize
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $address = $address ? htmlspecialchars($address, ENT_QUOTES, 'UTF-8') : null;
    $city = $city ? htmlspecialchars($city, ENT_QUOTES, 'UTF-8') : null;
    $state = $state ? htmlspecialchars($state, ENT_QUOTES, 'UTF-8') : null;
    $zipcode = $zipcode ? htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8') : null;
    $notes = $notes ? htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') : null;
    
    // Insert project
    $stmt = $pdo->prepare("
        INSERT INTO projects (
            customer_id, lead_id, name, project_type, status, post_service_status,
            address, city, state, zipcode,
            estimated_start_date, estimated_end_date, estimated_cost,
            owner_id, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $customer_id,
        $lead_id,
        $name,
        $project_type,
        $status,
        $post_service_status,
        $address,
        $city,
        $state,
        $zipcode,
        $estimated_start_date ?: null,
        $estimated_end_date ?: null,
        $estimated_cost,
        $owner_id,
        $notes
    ]);
    
    $project_id = $pdo->lastInsertId();
    
    // Log activity
    $activity_stmt = $pdo->prepare("
        INSERT INTO activities (customer_id, project_id, activity_type, subject, description, related_to)
        VALUES (?, ?, 'status_change', 'Project Created', 'New project created', 'project')
    ");
    $activity_stmt->execute([$customer_id, $project_id]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Project created successfully',
        'project_id' => $project_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
