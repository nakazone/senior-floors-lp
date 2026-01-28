<?php
/**
 * API Endpoint: Usar Coupon
 * 
 * Endpoint: POST /api/coupons/use.php
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
$coupon_id = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;
$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
$discount_amount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : null;
$used_by = isset($_POST['used_by']) ? (int)$_POST['used_by'] : null;

// Validation
if ($coupon_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid coupon ID']);
    exit;
}

if (!$lead_id && !$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Either lead_id or project_id is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get coupon
    $coupon_stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $coupon_stmt->execute([$coupon_id]);
    $coupon = $coupon_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Coupon not found']);
        exit;
    }
    
    // Check if active
    if (!$coupon['is_active']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Coupon is not active']);
        exit;
    }
    
    // Check validity dates
    $now = date('Y-m-d');
    if ($coupon['valid_from'] && $now < $coupon['valid_from']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Coupon is not yet valid']);
        exit;
    }
    
    if ($coupon['valid_until'] && $now > $coupon['valid_until']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Coupon has expired']);
        exit;
    }
    
    // Check max uses
    if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Coupon has reached maximum uses']);
        exit;
    }
    
    // Insert usage
    $usage_stmt = $pdo->prepare("
        INSERT INTO coupon_usage (coupon_id, lead_id, project_id, discount_amount, used_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $usage_stmt->execute([
        $coupon_id,
        $lead_id,
        $project_id,
        $discount_amount,
        $used_by
    ]);
    
    // Update coupon used_count
    $update_stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
    $update_stmt->execute([$coupon_id]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Coupon used successfully',
        'discount_amount' => $discount_amount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
