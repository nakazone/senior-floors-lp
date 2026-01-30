<?php
/**
 * API: Mover lead para outro estágio do pipeline
 * POST /api/pipeline/move.php   lead_id=1&stage_id=2
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
if (file_exists(__DIR__ . '/../../config/audit.php')) {
    require_once __DIR__ . '/../../config/audit.php';
}
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : (isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0);
$stage_id = isset($_POST['stage_id']) ? (int)$_POST['stage_id'] : (isset($_GET['stage_id']) ? (int)$_GET['stage_id'] : 0);

if ($lead_id <= 0 || $stage_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'lead_id and stage_id required']);
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

    $get_current = $pdo->prepare("SELECT pipeline_stage_id FROM leads WHERE id = ?");
    $get_current->execute([$lead_id]);
    $row = $get_current->fetch(PDO::FETCH_ASSOC);
    $from_stage_id = $row ? ($row['pipeline_stage_id'] ?? null) : null;

    $has_activity = $pdo->query("SHOW COLUMNS FROM leads LIKE 'last_activity_at'")->rowCount() > 0;
    $sql = $has_activity
        ? "UPDATE leads SET pipeline_stage_id = :stage_id, last_activity_at = NOW() WHERE id = :id"
        : "UPDATE leads SET pipeline_stage_id = :stage_id WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':stage_id' => $stage_id, ':id' => $lead_id]);

    if ($stmt->rowCount() > 0) {
        if (function_exists('logLeadStatusChange')) {
            $uid = function_exists('auditCurrentUserId') ? auditCurrentUserId() : null;
            logLeadStatusChange($lead_id, $from_stage_id, $stage_id, $uid, '');
        }
        echo json_encode([
            'success' => true,
            'message' => 'Lead moved',
            'data' => ['lead_id' => $lead_id, 'pipeline_stage_id' => $stage_id],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found or no change']);
    }
} catch (Exception $e) {
    error_log("Pipeline move: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
