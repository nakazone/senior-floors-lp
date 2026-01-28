<?php
/**
 * API Endpoint: Gerenciar Permissões de User
 * 
 * Endpoint: POST /api/users/permissions.php
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

if (!isset($_SESSION['admin_user_id']) || !hasPermission($_SESSION['admin_user_id'], 'users.manage_permissions')) {
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
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$permission_key = isset($_POST['permission_key']) ? trim($_POST['permission_key']) : '';

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

if (empty($action) || !in_array($action, ['grant', 'revoke', 'set_all'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $pdo = getDBConnection();
    $granted_by = $_SESSION['admin_user_id'];
    
    if ($action === 'grant') {
        if (empty($permission_key)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Permission key is required']);
            exit;
        }
        
        $result = grantPermission($user_id, $permission_key, $granted_by);
        
        if ($result) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Permission granted successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to grant permission']);
        }
        
    } elseif ($action === 'revoke') {
        if (empty($permission_key)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Permission key is required']);
            exit;
        }
        
        $result = revokePermission($user_id, $permission_key);
        
        if ($result) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Permission revoked successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to revoke permission']);
        }
        
    } elseif ($action === 'set_all') {
        // Definir todas as permissões de uma vez
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        
        if (!is_array($permissions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Permissions must be an array']);
            exit;
        }
        
        // Obter todas as permissões disponíveis
        $all_permissions_stmt = $pdo->query("SELECT permission_key FROM permissions");
        $all_permissions = $all_permissions_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Remover todas as permissões primeiro
        $stmt = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Adicionar permissões selecionadas
        $granted_count = 0;
        foreach ($permissions as $perm_key) {
            if (in_array($perm_key, $all_permissions)) {
                if (grantPermission($user_id, $perm_key, $granted_by)) {
                    $granted_count++;
                }
            }
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Permissions updated successfully ($granted_count permissions granted)"
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
