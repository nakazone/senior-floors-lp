<?php
/**
 * API Endpoint: Criar Activity
 * 
 * Endpoint: POST /api/activities/create.php
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
$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
$activity_type = isset($_POST['activity_type']) ? trim($_POST['activity_type']) : 'note';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$activity_date = isset($_POST['activity_date']) ? trim($_POST['activity_date']) : date('Y-m-d H:i:s');
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : null;

// Validation
$valid_types = ['email_sent', 'whatsapp_message', 'phone_call', 'meeting_scheduled', 'site_visit', 'proposal_sent', 'note', 'status_change', 'assignment', 'other'];
if (!in_array($activity_type, $valid_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid activity type']);
    exit;
}

if (!$lead_id && !$customer_id && !$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one of lead_id, customer_id, or project_id is required']);
    exit;
}

// Determine related_to
$related_to = null;
if ($lead_id) $related_to = 'lead';
elseif ($customer_id) $related_to = 'customer';
elseif ($project_id) $related_to = 'project';

try {
    $pdo = getDBConnection();
    
    // Sanitize
    $subject = $subject ? htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') : null;
    $description = $description ? htmlspecialchars($description, ENT_QUOTES, 'UTF-8') : null;
    
    // Insert activity
    $stmt = $pdo->prepare("
        INSERT INTO activities (
            lead_id, customer_id, project_id, activity_type, subject, description,
            activity_date, user_id, owner_id, related_to
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $lead_id,
        $customer_id,
        $project_id,
        $activity_type,
        $subject,
        $description,
        $activity_date,
        $user_id,
        $owner_id,
        $related_to
    ]);
    
    $activity_id = $pdo->lastInsertId();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Activity created successfully',
        'activity_id' => $activity_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
