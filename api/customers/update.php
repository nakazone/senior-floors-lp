<?php
/**
 * API Endpoint: Atualizar Customer
 * 
 * Endpoint: POST /api/customers/update.php
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

$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
    exit;
}

// Get update fields
$updates = [];
$params = [];

if (isset($_POST['name']) && !empty($_POST['name'])) {
    $updates[] = "name = ?";
    $params[] = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $updates[] = "email = ?";
    $params[] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
}

if (isset($_POST['phone']) && !empty($_POST['phone'])) {
    $updates[] = "phone = ?";
    $params[] = htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8');
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

if (isset($_POST['customer_type'])) {
    $valid_types = ['residential', 'commercial', 'property_manager', 'investor', 'builder'];
    if (in_array($_POST['customer_type'], $valid_types)) {
        $updates[] = "customer_type = ?";
        $params[] = $_POST['customer_type'];
    }
}

if (isset($_POST['status'])) {
    $valid_statuses = ['active', 'inactive', 'archived'];
    if (in_array($_POST['status'], $valid_statuses)) {
        $updates[] = "status = ?";
        $params[] = $_POST['status'];
    }
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
    
    $params[] = $customer_id;
    $sql = "UPDATE customers SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Log activity
    $activity_stmt = $pdo->prepare("
        INSERT INTO activities (customer_id, activity_type, subject, description, related_to)
        VALUES (?, 'status_change', 'Customer Updated', 'Customer information was updated', 'customer')
    ");
    $activity_stmt->execute([$customer_id]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Customer updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
