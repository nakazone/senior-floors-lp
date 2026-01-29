<?php
/**
 * API: Listar ou criar problemas do projeto (pós-venda)
 * GET project_id | POST project_id, description, status
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/database.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
if ($project_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'project_id required']);
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
    $has = $pdo->query("SHOW TABLES LIKE 'project_issues'")->rowCount() > 0;
    if (!$has) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            http_response_code(501);
            echo json_encode(['success' => false, 'message' => 'Table project_issues not found']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'open';
        $reported_by = isset($_POST['reported_by']) ? (int)$_POST['reported_by'] : null;
        if (!$description) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'description required']);
            exit;
        }
        if (!in_array($status, ['open','in_progress','resolved'])) $status = 'open';
        $pdo->prepare("INSERT INTO project_issues (project_id, description, status, reported_by) VALUES (?, ?, ?, ?)")
            ->execute([$project_id, $description, $status, $reported_by]);
        echo json_encode(['success' => true, 'data' => ['id' => (int)$pdo->lastInsertId()], 'timestamp' => date('Y-m-d H:i:s')]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM project_issues WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$project_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
