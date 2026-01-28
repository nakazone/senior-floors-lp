<?php
/**
 * API Endpoint: Gerenciar Tags do Customer
 * 
 * Endpoint: POST /api/customers/tags.php
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/tags.php';

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
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$tag = isset($_POST['tag']) ? trim($_POST['tag']) : '';

if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
    exit;
}

if (empty($action) || !in_array($action, ['add', 'remove'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

if (empty($tag)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tag is required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Sanitize tag
    $tag = strtolower(trim($tag));
    $tag = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
    
    if ($action === 'add') {
        // Check if tag already exists
        $check_stmt = $pdo->prepare("SELECT id FROM customer_tags WHERE customer_id = ? AND tag_name = ?");
        $check_stmt->execute([$customer_id, $tag]);
        
        if ($check_stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tag already exists']);
            exit;
        }
        
        // Add tag
        $stmt = $pdo->prepare("INSERT INTO customer_tags (customer_id, tag_name) VALUES (?, ?)");
        $stmt->execute([$customer_id, $tag]);
    } else {
        // Remove tag
        $stmt = $pdo->prepare("DELETE FROM customer_tags WHERE customer_id = ? AND tag_name = ?");
        $stmt->execute([$customer_id, $tag]);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Tag $action" . ($action === 'add' ? 'ed' : 'd') . " successfully"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
