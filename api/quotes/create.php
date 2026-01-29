<?php
/**
 * API: Criar orçamento (com itens opcionais)
 * POST lead_id, customer_id, project_id, items[] (floor_type, area_sqft, unit_price), margin_percent
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
$margin_percent = isset($_POST['margin_percent']) ? (float)str_replace(',', '.', $_POST['margin_percent']) : null;
$created_by = isset($_POST['created_by']) ? (int)$_POST['created_by'] : null;

if (!$lead_id && !$customer_id && !$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'lead_id, customer_id or project_id required']);
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
    $has = $pdo->query("SHOW TABLES LIKE 'quotes'")->rowCount() > 0;
    if (!$has) {
        http_response_code(501);
        echo json_encode(['success' => false, 'message' => 'Quotes table not found. Run migration.']);
        exit;
    }

    $items = [];
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        $items = $_POST['items'];
    } elseif (isset($_POST['floor_type']) && isset($_POST['area_sqft']) && isset($_POST['unit_price'])) {
        $items[] = [
            'floor_type' => $_POST['floor_type'],
            'area_sqft' => str_replace(',', '.', $_POST['area_sqft']),
            'unit_price' => str_replace(',', '.', $_POST['unit_price'])
        ];
    }

    $materials = 0;
    $labor = 0;
    foreach ($items as $it) {
        $area = (float)($it['area_sqft'] ?? 0);
        $unit = (float)($it['unit_price'] ?? 0);
        $total = $area * $unit;
        $materials += $total;
    }
    if ($margin_percent !== null && $margin_percent > 0) {
        $labor = $materials * ($margin_percent / 100);
    }
    $total_amount = $materials + $labor;

    $stmt = $pdo->prepare("
        INSERT INTO quotes (lead_id, customer_id, project_id, version, total_amount, labor_amount, materials_amount, margin_percent, status, created_by)
        VALUES (:lead_id, :customer_id, :project_id, 1, :total_amount, :labor_amount, :materials_amount, :margin_percent, 'draft', :created_by)
    ");
    $stmt->execute([
        ':lead_id' => $lead_id ?: null,
        ':customer_id' => $customer_id ?: null,
        ':project_id' => $project_id ?: null,
        ':total_amount' => $total_amount,
        ':labor_amount' => $labor,
        ':materials_amount' => $materials,
        ':margin_percent' => $margin_percent,
        ':created_by' => $created_by
    ]);
    $quote_id = (int)$pdo->lastInsertId();

    foreach ($items as $it) {
        $area = (float)($it['area_sqft'] ?? 0);
        $unit = (float)($it['unit_price'] ?? 0);
        $total = $area * $unit;
        $floor_type = $it['floor_type'] ?? 'Other';
        $pdo->prepare("INSERT INTO quote_items (quote_id, floor_type, area_sqft, unit_price, total_price) VALUES (?, ?, ?, ?, ?)")
            ->execute([$quote_id, $floor_type, $area, $unit, $total]);
    }

    if ($lead_id && $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0) {
        $stage = $pdo->query("SELECT id FROM pipeline_stages WHERE slug = 'quote_sent' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($stage) {
            $pdo->prepare("UPDATE leads SET pipeline_stage_id = ? WHERE id = ?")->execute([$stage['id'], $lead_id]);
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $quote_id,
            'total_amount' => $total_amount,
            'labor_amount' => $labor,
            'materials_amount' => $materials
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
