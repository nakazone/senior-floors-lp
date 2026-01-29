<?php
/**
 * API: Listar estágios do pipeline (Kanban)
 * GET /api/pipeline/stages.php
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

    $stmt = $pdo->query("
        SELECT id, name, slug, order_num, sla_hours, required_actions, required_fields, is_closed
        FROM pipeline_stages
        ORDER BY order_num ASC
    ");
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: se tabela não existir, retornar estágios do config
    if (empty($stages) && file_exists(__DIR__ . '/../../config/pipeline.php')) {
        $config = require __DIR__ . '/../../config/pipeline.php';
        $stages = [];
        $i = 1;
        foreach ($config['stages'] as $slug => $name) {
            $stages[] = [
                'id' => $i,
                'name' => $name,
                'slug' => $slug,
                'order_num' => $i,
                'sla_hours' => null,
                'required_actions' => null,
                'required_fields' => null,
                'is_closed' => in_array($slug, ['closed_won', 'closed_lost']) ? 1 : 0
            ];
            $i++;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $stages,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Pipeline stages: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
