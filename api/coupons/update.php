<?php
/**
 * API Endpoint: Atualizar Coupon
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

$coupon_id = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;

if ($coupon_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid coupon ID']);
    exit;
}

$updates = [];
$params = [];

if (isset($_POST['is_active'])) {
    $updates[] = "is_active = ?";
    $params[] = (int)$_POST['is_active'];
}

if (isset($_POST['name'])) {
    $updates[] = "name = ?";
    $params[] = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['max_uses'])) {
    $updates[] = "max_uses = ?";
    $params[] = $_POST['max_uses'] ? (int)$_POST['max_uses'] : null;
}

if (isset($_POST['valid_from'])) {
    $updates[] = "valid_from = ?";
    $params[] = $_POST['valid_from'] ?: null;
}

if (isset($_POST['valid_until'])) {
    $updates[] = "valid_until = ?";
    $params[] = $_POST['valid_until'] ?: null;
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $params[] = $coupon_id;
    $sql = "UPDATE coupons SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Coupon updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
