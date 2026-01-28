<?php
/**
 * API Endpoint: Criar User
 * 
 * Endpoint: POST /api/users/create.php
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config/database.php';

// Load permissions if available
if (file_exists(__DIR__ . '/../../config/permissions.php')) {
    require_once __DIR__ . '/../../config/permissions.php';
}

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verificar autenticação básica
if (!isset($_SESSION['admin_authenticated'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Verificar permissão (se sistema de permissões estiver disponível)
$has_permission = true;
if (function_exists('hasPermission') && isset($_SESSION['admin_user_id'])) {
    $has_permission = hasPermission($_SESSION['admin_user_id'], 'users.create');
} elseif (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') {
    $has_permission = true;
} else {
    $has_permission = false;
}

if (!$has_permission) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

// Get data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
$role = isset($_POST['role']) ? trim($_POST['role']) : 'sales_rep';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Validation
$errors = [];
if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name is required';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

$valid_roles = ['admin', 'sales_rep', 'project_manager', 'support'];
if (!in_array($role, $valid_roles)) {
    $errors[] = 'Invalid role';
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if email already exists
    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->execute([$email]);
    if ($check_stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Sanitize
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $phone = $phone ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : null;
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, phone, role, password_hash, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $name,
        $email,
        $phone,
        $role,
        $password_hash,
        $is_active
    ]);
    
    $user_id = $pdo->lastInsertId();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
