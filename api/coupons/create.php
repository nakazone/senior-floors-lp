<?php
/**
 * API Endpoint: Criar Coupon
 * 
 * Endpoint: POST /api/coupons/create.php
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
$code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$discount_type = isset($_POST['discount_type']) ? trim($_POST['discount_type']) : 'percentage';
$discount_value = isset($_POST['discount_value']) ? (float)$_POST['discount_value'] : 0;
$max_uses = isset($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
$valid_from = isset($_POST['valid_from']) ? trim($_POST['valid_from']) : null;
$valid_until = isset($_POST['valid_until']) ? trim($_POST['valid_until']) : null;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
$created_by = isset($_POST['created_by']) ? (int)$_POST['created_by'] : null;

// Validation
$errors = [];
if (empty($code)) {
    $errors[] = 'Coupon code is required';
}

$valid_types = ['percentage', 'fixed'];
if (!in_array($discount_type, $valid_types)) {
    $errors[] = 'Invalid discount type';
}

if ($discount_value <= 0) {
    $errors[] = 'Discount value must be greater than 0';
}

if ($discount_type === 'percentage' && $discount_value > 100) {
    $errors[] = 'Percentage discount cannot exceed 100%';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if code already exists
    $check_stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
    $check_stmt->execute([$code]);
    if ($check_stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Coupon code already exists']);
        exit;
    }
    
    // Sanitize
    $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $name = $name ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : null;
    
    // Insert coupon
    $stmt = $pdo->prepare("
        INSERT INTO coupons (
            code, name, discount_type, discount_value, max_uses,
            valid_from, valid_until, is_active, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $code,
        $name,
        $discount_type,
        $discount_value,
        $max_uses,
        $valid_from ?: null,
        $valid_until ?: null,
        $is_active,
        $created_by
    ]);
    
    $coupon_id = $pdo->lastInsertId();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Coupon created successfully',
        'coupon_id' => $coupon_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
