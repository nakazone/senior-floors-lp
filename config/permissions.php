<?php
/**
 * Sistema de Permissões - Senior Floors CRM
 * 
 * Funções para verificar e gerenciar permissões de usuários
 */

require_once __DIR__ . '/database.php';

/**
 * Verifica se o usuário tem uma permissão específica
 * 
 * @param int $user_id ID do usuário
 * @param string $permission_key Chave da permissão (ex: 'leads.view')
 * @return bool True se tem permissão, False caso contrário
 */
function hasPermission($user_id, $permission_key) {
    if (!isDatabaseConfigured()) {
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Admin tem todas as permissões
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['role'] === 'admin') {
            return true;
        }
        
        // Verificar permissão específica
        $stmt = $pdo->prepare("
            SELECT up.granted 
            FROM user_permissions up
            INNER JOIN permissions p ON p.id = up.permission_id
            WHERE up.user_id = ? AND p.permission_key = ? AND up.granted = 1
        ");
        $stmt->execute([$user_id, $permission_key]);
        
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se o usuário tem pelo menos uma das permissões
 * 
 * @param int $user_id ID do usuário
 * @param array $permission_keys Array de chaves de permissão
 * @return bool True se tem pelo menos uma permissão
 */
function hasAnyPermission($user_id, $permission_keys) {
    foreach ($permission_keys as $key) {
        if (hasPermission($user_id, $key)) {
            return true;
        }
    }
    return false;
}

/**
 * Verifica se o usuário tem todas as permissões
 * 
 * @param int $user_id ID do usuário
 * @param array $permission_keys Array de chaves de permissão
 * @return bool True se tem todas as permissões
 */
function hasAllPermissions($user_id, $permission_keys) {
    foreach ($permission_keys as $key) {
        if (!hasPermission($user_id, $key)) {
            return false;
        }
    }
    return true;
}

/**
 * Obtém todas as permissões de um usuário
 * 
 * @param int $user_id ID do usuário
 * @return array Array de chaves de permissão
 */
function getUserPermissions($user_id) {
    if (!isDatabaseConfigured()) {
        return [];
    }
    
    try {
        $pdo = getDBConnection();
        
        // Admin tem todas as permissões
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['role'] === 'admin') {
            // Retornar todas as permissões disponíveis
            $stmt = $pdo->query("SELECT permission_key FROM permissions");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Obter permissões específicas do usuário
        $stmt = $pdo->prepare("
            SELECT p.permission_key 
            FROM user_permissions up
            INNER JOIN permissions p ON p.id = up.permission_id
            WHERE up.user_id = ? AND up.granted = 1
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (Exception $e) {
        error_log("Get user permissions error: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém todas as permissões disponíveis agrupadas
 * 
 * @return array Array associativo com grupos de permissões
 */
function getAllPermissionsGrouped() {
    if (!isDatabaseConfigured()) {
        return [];
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT permission_key, permission_name, permission_group, description
            FROM permissions
            ORDER BY permission_group, permission_name
        ");
        
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        
        foreach ($permissions as $perm) {
            $group = $perm['permission_group'] ?: 'other';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $perm;
        }
        
        return $grouped;
        
    } catch (Exception $e) {
        error_log("Get all permissions error: " . $e->getMessage());
        return [];
    }
}

/**
 * Concede uma permissão a um usuário
 * 
 * @param int $user_id ID do usuário
 * @param string $permission_key Chave da permissão
 * @param int $granted_by ID do usuário que está concedendo
 * @return bool True se bem-sucedido
 */
function grantPermission($user_id, $permission_key, $granted_by = null) {
    if (!isDatabaseConfigured()) {
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Obter ID da permissão
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE permission_key = ?");
        $stmt->execute([$permission_key]);
        $permission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$permission) {
            return false;
        }
        
        // Inserir ou atualizar permissão
        $stmt = $pdo->prepare("
            INSERT INTO user_permissions (user_id, permission_id, granted, granted_by)
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE granted = 1, granted_by = ?
        ");
        
        return $stmt->execute([$user_id, $permission['id'], $granted_by, $granted_by]);
        
    } catch (Exception $e) {
        error_log("Grant permission error: " . $e->getMessage());
        return false;
    }
}

/**
 * Revoga uma permissão de um usuário
 * 
 * @param int $user_id ID do usuário
 * @param string $permission_key Chave da permissão
 * @return bool True se bem-sucedido
 */
function revokePermission($user_id, $permission_key) {
    if (!isDatabaseConfigured()) {
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Obter ID da permissão
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE permission_key = ?");
        $stmt->execute([$permission_key]);
        $permission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$permission) {
            return false;
        }
        
        // Remover permissão
        $stmt = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ? AND permission_id = ?");
        return $stmt->execute([$user_id, $permission['id']]);
        
    } catch (Exception $e) {
        error_log("Revoke permission error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se o usuário atual tem permissão (usa sessão)
 * 
 * @param string $permission_key Chave da permissão
 * @return bool True se tem permissão
 */
function currentUserHasPermission($permission_key) {
    if (!isset($_SESSION['admin_user_id'])) {
        return false;
    }
    
    return hasPermission($_SESSION['admin_user_id'], $permission_key);
}

/**
 * Requer permissão - redireciona se não tiver
 * 
 * @param string $permission_key Chave da permissão
 * @param string $redirect_url URL para redirecionar se não tiver permissão
 */
function requirePermission($permission_key, $redirect_url = '?module=dashboard') {
    if (!currentUserHasPermission($permission_key)) {
        header('Location: ' . $redirect_url . '&error=no_permission');
        exit;
    }
}
