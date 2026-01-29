<?php
/**
 * API: Criar contrato (fechamento)
 * POST lead_id, customer_id, project_id, quote_id, closed_amount, payment_method, installments, start_date, end_date, responsible_id
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

$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
$quote_id = isset($_POST['quote_id']) ? (int)$_POST['quote_id'] : null;
$closed_amount = isset($_POST['closed_amount']) ? (float)str_replace([',',' '], ['',''], $_POST['closed_amount']) : 0;
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : null;
$installments = isset($_POST['installments']) ? (int)$_POST['installments'] : 1;
$start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : null;
$end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : null;
$responsible_id = isset($_POST['responsible_id']) ? (int)$_POST['responsible_id'] : null;

if (!$closed_amount || (!$lead_id && !$customer_id && !$project_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'closed_amount and at least one of lead_id, customer_id, project_id required']);
    exit;
}

$valid_payment = ['cash','financing','check','card','other'];
if ($payment_method && !in_array($payment_method, $valid_payment)) $payment_method = null;

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    $has = $pdo->query("SHOW TABLES LIKE 'contracts'")->rowCount() > 0;
    if (!$has) {
        http_response_code(501);
        echo json_encode(['success' => false, 'message' => 'Contracts table not found. Run migration.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO contracts (lead_id, customer_id, project_id, quote_id, closed_amount, payment_method, installments, start_date, end_date, responsible_id)
        VALUES (:lead_id, :customer_id, :project_id, :quote_id, :closed_amount, :payment_method, :installments, :start_date, :end_date, :responsible_id)
    ");
    $stmt->execute([
        ':lead_id' => $lead_id ?: null,
        ':customer_id' => $customer_id ?: null,
        ':project_id' => $project_id ?: null,
        ':quote_id' => $quote_id ?: null,
        ':closed_amount' => $closed_amount,
        ':payment_method' => $payment_method,
        ':installments' => $installments,
        ':start_date' => $start_date ? date('Y-m-d', strtotime($start_date)) : null,
        ':end_date' => $end_date ? date('Y-m-d', strtotime($end_date)) : null,
        ':responsible_id' => $responsible_id ?: null
    ]);
    $id = (int)$pdo->lastInsertId();

    if ($lead_id && $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0) {
        $stage = $pdo->query("SELECT id FROM pipeline_stages WHERE slug = 'closed_won' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($stage) {
            $pdo->prepare("UPDATE leads SET pipeline_stage_id = ?, status = 'closed_won' WHERE id = ?")->execute([$stage['id'], $lead_id]);
        }
    }
    if ($quote_id) {
        $pdo->prepare("UPDATE quotes SET status = 'approved', approved_at = NOW() WHERE id = ?")->execute([$quote_id]);
    }

    echo json_encode(['success' => true, 'data' => ['id' => $id], 'timestamp' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
