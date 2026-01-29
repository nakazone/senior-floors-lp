<?php
/**
 * API: Criar visita
 * POST lead_id, customer_id, project_id, scheduled_at, seller_id, technician_id, address, notes
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
$scheduled_at = isset($_POST['scheduled_at']) ? trim($_POST['scheduled_at']) : '';
$seller_id = isset($_POST['seller_id']) ? (int)$_POST['seller_id'] : null;
$technician_id = isset($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
$address = isset($_POST['address']) ? trim($_POST['address']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if (!$scheduled_at || strtotime($scheduled_at) === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'scheduled_at required (Y-m-d H:i)']);
    exit;
}
if (!$lead_id && !$customer_id && !$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one of lead_id, customer_id, project_id required']);
    exit;
}

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    $stmt = $pdo->prepare("
        INSERT INTO visits (lead_id, customer_id, project_id, scheduled_at, seller_id, technician_id, address, notes, status)
        VALUES (:lead_id, :customer_id, :project_id, :scheduled_at, :seller_id, :technician_id, :address, :notes, 'scheduled')
    ");
    $stmt->execute([
        ':lead_id' => $lead_id ?: null,
        ':customer_id' => $customer_id ?: null,
        ':project_id' => $project_id ?: null,
        ':scheduled_at' => date('Y-m-d H:i:s', strtotime($scheduled_at)),
        ':seller_id' => $seller_id ?: null,
        ':technician_id' => $technician_id ?: null,
        ':address' => $address ?: null,
        ':notes' => $notes ?: null
    ]);
    $id = (int)$pdo->lastInsertId();
    if ($lead_id && $pdo->query("SHOW TABLES LIKE 'pipeline_stages'")->rowCount() > 0) {
        $stage_visit = $pdo->query("SELECT id FROM pipeline_stages WHERE slug = 'visit_scheduled' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($stage_visit && $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0) {
            $pdo->prepare("UPDATE leads SET pipeline_stage_id = ? WHERE id = ?")->execute([$stage_visit['id'], $lead_id]);
        }
    }
    echo json_encode(['success' => true, 'data' => ['id' => $id], 'timestamp' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
