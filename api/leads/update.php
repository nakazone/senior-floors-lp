<?php
/**
 * API Endpoint: Atualizar Lead
 * FASE 2 - MÓDULO 04: Painel Admin (MVP)
 * 
 * Endpoint: POST /api/leads/update.php
 * 
 * Atualiza status, prioridade e outros campos de um lead
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obter dados
$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$priority = isset($_POST['priority']) ? trim($_POST['priority']) : '';

// Validação
if ($lead_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

// Validar status
$valid_statuses = ['new', 'contacted', 'qualified', 'proposal', 'closed_won', 'closed_lost'];
if (!empty($status) && !in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Validar priority
$valid_priorities = ['low', 'medium', 'high'];
if (!empty($priority) && !in_array($priority, $valid_priorities)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid priority']);
    exit;
}

// Atualizar no banco de dados
if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('Failed to connect to database');
    }
    
    // Construir query dinamicamente baseado nos campos fornecidos
    $updates = [];
    $params = [':id' => $lead_id];
    
    if (!empty($status)) {
        $updates[] = 'status = :status';
        $params[':status'] = $status;
    }
    
    if (!empty($priority)) {
        $updates[] = 'priority = :priority';
        $params[':priority'] = $priority;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Lead updated successfully',
            'data' => [
                'lead_id' => $lead_id,
                'updated_fields' => array_keys($params)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found or no changes made']);
    }
    
} catch (Exception $e) {
    error_log("Lead update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update lead',
        'error' => $e->getMessage()
    ]);
}
