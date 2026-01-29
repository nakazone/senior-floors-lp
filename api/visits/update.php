<?php
/**
 * API: Atualizar visita (status, ended_at, notes, etc.) e criar medição
 * POST id, status, ended_at, notes, address | Para medição: visit_id, area_sqft, rooms, technical_notes
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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'id required']);
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
    $stmt = $pdo->prepare("SELECT id, lead_id FROM visits WHERE id = ?");
    $stmt->execute([$id]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$visit) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Visit not found']);
        exit;
    }

    $updates = [];
    $params = [':id' => $id];
    if (isset($_POST['status']) && in_array($_POST['status'], ['scheduled','completed','cancelled','no_show'])) {
        $updates[] = 'status = :status';
        $params[':status'] = $_POST['status'];
    }
    if (!empty($_POST['ended_at'])) {
        $updates[] = 'ended_at = :ended_at';
        $params[':ended_at'] = date('Y-m-d H:i:s', strtotime($_POST['ended_at']));
    }
    if (isset($_POST['notes'])) { $updates[] = 'notes = :notes'; $params[':notes'] = $_POST['notes']; }
    if (isset($_POST['address'])) { $updates[] = 'address = :address'; $params[':address'] = $_POST['address']; }
    if (!empty($updates)) {
        $pdo->prepare("UPDATE visits SET " . implode(', ', $updates) . " WHERE id = :id")->execute($params);
    }

    if (isset($_POST['status']) && $_POST['status'] === 'completed' && $visit['lead_id'] && $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0) {
        $stage = $pdo->query("SELECT id FROM pipeline_stages WHERE slug = 'measurement_done' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($stage) {
            $pdo->prepare("UPDATE leads SET pipeline_stage_id = ? WHERE id = ?")->execute([$stage['id'], $visit['lead_id']]);
        }
    }

    $measurement_visit_id = isset($_POST['measurement_visit_id']) ? (int)$_POST['measurement_visit_id'] : $id;
    $area_sqft = isset($_POST['area_sqft']) ? str_replace(',', '.', $_POST['area_sqft']) : null;
    $rooms = isset($_POST['rooms']) ? trim($_POST['rooms']) : null;
    $technical_notes = isset($_POST['technical_notes']) ? trim($_POST['technical_notes']) : null;
    if ($area_sqft !== null && $area_sqft !== '' && $measurement_visit_id > 0) {
        $pdo->prepare("INSERT INTO measurements (visit_id, lead_id, area_sqft, rooms, technical_notes) VALUES (?, ?, ?, ?, ?)")
            ->execute([$measurement_visit_id, $visit['lead_id'], (float)$area_sqft, $rooms, $technical_notes]);
    }

    echo json_encode(['success' => true, 'data' => ['id' => $id], 'timestamp' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
