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
$pipeline_stage_id = isset($_POST['pipeline_stage_id']) ? (int)$_POST['pipeline_stage_id'] : 0;
$budget_estimated = isset($_POST['budget_estimated']) ? trim($_POST['budget_estimated']) : null;
$urgency = isset($_POST['urgency']) ? trim($_POST['urgency']) : null;
$is_decision_maker = isset($_POST['is_decision_maker']) ? (int)$_POST['is_decision_maker'] : null;
$payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : null;
$has_competition = isset($_POST['has_competition']) ? (int)$_POST['has_competition'] : null;

// Validação
if ($lead_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

$valid_statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
if (!empty($status) && !in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}
$valid_priorities = ['low', 'medium', 'high'];
if (!empty($priority) && !in_array($priority, $valid_priorities)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid priority']);
    exit;
}
$valid_urgency = ['imediato', '30_dias', '60_mais'];
if ($urgency !== null && $urgency !== '' && !in_array($urgency, $valid_urgency)) $urgency = null;
$valid_payment = ['cash', 'financing'];
if ($payment_type !== null && $payment_type !== '' && !in_array($payment_type, $valid_payment)) $payment_type = null;

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
    
    $updates = [];
    $params = [':id' => $lead_id];

    if (!empty($status)) { $updates[] = 'status = :status'; $params[':status'] = $status; }
    if (!empty($priority)) { $updates[] = 'priority = :priority'; $params[':priority'] = $priority; }
    if ($pipeline_stage_id > 0) { $updates[] = 'pipeline_stage_id = :pipeline_stage_id'; $params[':pipeline_stage_id'] = $pipeline_stage_id; $updates[] = 'last_activity_at = NOW()'; }
    if ($budget_estimated !== null && $budget_estimated !== '') { $updates[] = 'budget_estimated = :budget_estimated'; $params[':budget_estimated'] = (float)str_replace([',',' '], ['',''], $budget_estimated); }
    if ($urgency !== null && $urgency !== '') { $updates[] = 'urgency = :urgency'; $params[':urgency'] = $urgency; }
    if ($is_decision_maker !== null) { $updates[] = 'is_decision_maker = :is_decision_maker'; $params[':is_decision_maker'] = (int)$is_decision_maker; }
    if ($payment_type !== null && $payment_type !== '') { $updates[] = 'payment_type = :payment_type'; $params[':payment_type'] = $payment_type; }
    if ($has_competition !== null) { $updates[] = 'has_competition = :has_competition'; $params[':has_competition'] = (int)$has_competition; }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0 && file_exists(__DIR__ . '/../../config/lead-logic.php')) {
        require_once __DIR__ . '/../../config/lead-logic.php';
        $fetch = $pdo->prepare("SELECT budget_estimated, urgency, is_decision_maker, payment_type, has_competition, property_type FROM leads WHERE id = :id");
        $fetch->execute([':id' => $lead_id]);
        $row = $fetch->fetch(PDO::FETCH_ASSOC);
        if ($row && function_exists('calculateLeadScore')) {
            $score = calculateLeadScore($row);
            $pdo->prepare("UPDATE leads SET lead_score = ? WHERE id = ?")->execute([$score, $lead_id]);
        }
        if ($row && function_exists('applyAutoTags')) {
            $row['lead_id'] = $lead_id;
            applyAutoTags($pdo, $lead_id, $row);
        }
    }
    
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
