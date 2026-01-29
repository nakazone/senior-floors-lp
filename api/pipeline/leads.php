<?php
/**
 * API: Leads agrupados por estágio do pipeline (para Kanban)
 * GET /api/pipeline/leads.php
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');

    $owner_filter = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : null;

    $stages = [];
    try {
        $stmt = $pdo->query("SELECT id, name, slug, order_num FROM pipeline_stages ORDER BY order_num ASC");
        $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stages = [];
    }

    if (empty($stages)) {
        echo json_encode(['success' => true, 'data' => ['stages' => [], 'leadsByStage' => (object)[]], 'timestamp' => date('Y-m-d H:i:s')]);
        exit;
    }

    $sql = "
        SELECT l.id, l.name, l.email, l.phone, l.source, l.status, l.priority, l.created_at,
               l.pipeline_stage_id, l.owner_id, l.lead_score, l.property_type, l.service_type
        FROM leads l
        WHERE 1=1
    ";
    $params = [];
    if ($owner_filter > 0) {
        $sql .= " AND l.owner_id = :owner_id";
        $params[':owner_id'] = $owner_filter;
    }
    $sql .= " ORDER BY l.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $leadsByStage = [];
    foreach ($stages as $s) {
        $leadsByStage[$s['id']] = [];
    }
    $leadsByStage['_none'] = [];

    foreach ($leads as $lead) {
        $sid = isset($lead['pipeline_stage_id']) && $lead['pipeline_stage_id'] !== null
            ? (int)$lead['pipeline_stage_id'] : '_none';
        if (!isset($leadsByStage[$sid])) $leadsByStage[$sid] = [];
        $leadsByStage[$sid][] = $lead;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'stages' => $stages,
            'leadsByStage' => $leadsByStage
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Pipeline leads: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
