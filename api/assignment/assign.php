<?php
/**
 * API Endpoint: Atribuir Lead/Customer/Project
 * 
 * Endpoint: POST /api/assignment/assign.php
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
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
$to_user_id = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;
$assigned_by = isset($_POST['assigned_by']) ? (int)$_POST['assigned_by'] : null;

// Validation
if (!$lead_id && !$customer_id && !$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one of lead_id, customer_id, or project_id is required']);
    exit;
}

if ($to_user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get current owner
    $current_owner = null;
    $table_name = '';
    $record_id = 0;
    
    if ($lead_id) {
        $table_name = 'leads';
        $record_id = $lead_id;
        $has_owner = $pdo->query("SHOW COLUMNS FROM leads LIKE 'owner_id'")->rowCount() > 0;
        if (!$has_owner) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Coluna owner_id não existe na tabela leads. Execute database/migration-lead-owner-and-activities.sql']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT owner_id FROM leads WHERE id = ?");
        $stmt->execute([$lead_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Lead not found']);
            exit;
        }
        $current_owner = $record['owner_id'];
    } elseif ($customer_id) {
        $table_name = 'customers';
        $record_id = $customer_id;
        $stmt = $pdo->prepare("SELECT owner_id FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
            exit;
        }
        $current_owner = $record['owner_id'];
    } elseif ($project_id) {
        $table_name = 'projects';
        $record_id = $project_id;
        $stmt = $pdo->prepare("SELECT owner_id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Project not found']);
            exit;
        }
        $current_owner = $record['owner_id'];
    }
    
    // Update owner
    $update_stmt = $pdo->prepare("UPDATE $table_name SET owner_id = ? WHERE id = ?");
    $update_stmt->execute([$to_user_id, $record_id]);
    
    // Log assignment history (se a tabela existir)
    try {
        if ($pdo->query("SHOW TABLES LIKE 'assignment_history'")->rowCount() > 0) {
            $history_stmt = $pdo->prepare("
                INSERT INTO assignment_history (lead_id, customer_id, project_id, from_user_id, to_user_id, reason, assigned_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $history_stmt->execute([$lead_id ?: null, $customer_id ?: null, $project_id ?: null, $current_owner, $to_user_id, $reason, $assigned_by]);
        }
    } catch (Exception $e) { /* ignore */ }
    
    // Log activity (se a tabela existir)
    try {
        if ($pdo->query("SHOW TABLES LIKE 'activities'")->rowCount() > 0) {
            $related_to = $lead_id ? 'lead' : ($customer_id ? 'customer' : 'project');
            $activity_stmt = $pdo->prepare("
                INSERT INTO activities (lead_id, customer_id, project_id, activity_type, subject, description, related_to)
                VALUES (?, ?, ?, ?, 'assignment', ?, ?)
            ");
            $activity_stmt->execute([
                $lead_id ?: null,
                $customer_id ?: null,
                $project_id ?: null,
                "Encaminhado para usuário ID: $to_user_id" . ($reason ? " - $reason" : ''),
                $related_to
            ]);
        }
    } catch (Exception $e) { /* ignore */ }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Assignment updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
