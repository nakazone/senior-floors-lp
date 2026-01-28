<?php
/**
 * API Endpoint: Criar Customer
 * 
 * Endpoint: POST /api/customers/create.php
 * 
 * Converte um lead em customer ou cria customer diretamente
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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : null;
$city = isset($_POST['city']) ? trim($_POST['city']) : null;
$state = isset($_POST['state']) ? trim($_POST['state']) : null;
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : null;
$customer_type = isset($_POST['customer_type']) ? trim($_POST['customer_type']) : 'residential';
$owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

// Validation
$errors = [];
if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name is required';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}
if (empty($phone)) {
    $errors[] = 'Phone is required';
}

$valid_types = ['residential', 'commercial', 'property_manager', 'investor', 'builder'];
if (!in_array($customer_type, $valid_types)) {
    $errors[] = 'Invalid customer type';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // If lead_id provided, get data from lead
    if ($lead_id) {
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$lead_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lead) {
            $name = $name ?: $lead['name'];
            $email = $email ?: $lead['email'];
            $phone = $phone ?: $lead['phone'];
            $zipcode = $zipcode ?: $lead['zipcode'];
            $customer_type = $customer_type !== 'residential' ? $customer_type : $lead['customer_type'];
            $owner_id = $owner_id ?: $lead['owner_id'];
        }
    }
    
    // Sanitize
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $address = $address ? htmlspecialchars($address, ENT_QUOTES, 'UTF-8') : null;
    $city = $city ? htmlspecialchars($city, ENT_QUOTES, 'UTF-8') : null;
    $state = $state ? htmlspecialchars($state, ENT_QUOTES, 'UTF-8') : null;
    $zipcode = $zipcode ? htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8') : null;
    $notes = $notes ? htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') : null;
    
    // Insert customer
    $stmt = $pdo->prepare("
        INSERT INTO customers (
            lead_id, name, email, phone, address, city, state, zipcode,
            customer_type, owner_id, notes, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([
        $lead_id,
        $name,
        $email,
        $phone,
        $address,
        $city,
        $state,
        $zipcode,
        $customer_type,
        $owner_id,
        $notes
    ]);
    
    $customer_id = $pdo->lastInsertId();
    
    // Log activity
    if ($lead_id) {
        $activity_stmt = $pdo->prepare("
            INSERT INTO activities (lead_id, customer_id, activity_type, subject, description, related_to)
            VALUES (?, ?, 'status_change', 'Lead Converted to Customer', 'Lead converted to customer', 'customer')
        ");
        $activity_stmt->execute([$lead_id, $customer_id]);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Customer created successfully',
        'customer_id' => $customer_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
