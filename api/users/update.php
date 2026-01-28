<?php
/**
 * API Endpoint: Atualizar User
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/permissions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['admin_user_id']) || !hasPermission($_SESSION['admin_user_id'], 'users.edit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

$updates = [];
$params = [];

if (isset($_POST['name']) && !empty($_POST['name'])) {
    $updates[] = "name = ?";
    $params[] = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    // Check if email is already taken by another user
    try {
        $pdo = getDBConnection();
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->execute([$_POST['email'], $user_id]);
        if ($check_stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }
    } catch (Exception $e) {
        // Ignore
    }
    
    $updates[] = "email = ?";
    $params[] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
}

if (isset($_POST['phone'])) {
    $updates[] = "phone = ?";
    $params[] = htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['role'])) {
    $valid_roles = ['admin', 'sales_rep', 'project_manager', 'support'];
    if (in_array($_POST['role'], $valid_roles)) {
        $updates[] = "role = ?";
        $params[] = $_POST['role'];
    }
}

if (isset($_POST['is_active'])) {
    $updates[] = "is_active = ?";
    $params[] = (int)$_POST['is_active'];
}

if (isset($_POST['password']) && !empty($_POST['password'])) {
    if (strlen($_POST['password']) >= 6) {
        $updates[] = "password_hash = ?";
        $params[] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $params[] = $user_id;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
