<?php
/**
 * Auditoria e logs - Senior Floors CRM
 * Registro de mudanças de status, alterações em propostas/valores, responsável por cada ação
 */

require_once __DIR__ . '/database.php';

/**
 * Registra mudança de status do lead (pipeline)
 */
function logLeadStatusChange($lead_id, $from_stage_id, $to_stage_id, $user_id = null, $notes = '') {
    if (!isDatabaseConfigured()) return false;
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO lead_status_change_log (lead_id, from_stage_id, to_stage_id, changed_by, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            (int) $lead_id,
            $from_stage_id ? (int) $from_stage_id : null,
            (int) $to_stage_id,
            $user_id ? (int) $user_id : null,
            $notes
        ]);
    } catch (Exception $e) {
        if (table_exists($pdo ?? null, 'lead_status_change_log')) {
            error_log("logLeadStatusChange: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Registra alteração genérica (propostas, valores, etc.)
 */
function logAudit($entity_type, $entity_id, $action, $field_name = null, $old_value = null, $new_value = null, $user_id = null) {
    if (!isDatabaseConfigured()) return false;
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (entity_type, entity_id, action, field_name, old_value, new_value, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $entity_type,
            (int) $entity_id,
            $action,
            $field_name,
            $old_value !== null ? (string) $old_value : null,
            $new_value !== null ? (string) $new_value : null,
            $user_id ? (int) $user_id : null
        ]);
    } catch (Exception $e) {
        if (function_exists('table_exists') && table_exists($pdo ?? null, 'audit_log')) {
            error_log("logAudit: " . $e->getMessage());
        }
        return false;
    }
}

function table_exists($pdo, $table) {
    if (!$pdo) return false;
    try {
        $r = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        return $r && $r->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Retorna o user_id atual da sessão (para preencher changed_by/user_id)
 */
function auditCurrentUserId() {
    return isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;
}
