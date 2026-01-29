<?php
/**
 * API: Listar ou atualizar checklist de entrega (pós-venda)
 * GET project_id | POST project_id, item_name (add) | POST id, completed (toggle)
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/database.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
if ($project_id <= 0 && empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'project_id or id required']);
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
    $has = $pdo->query("SHOW TABLES LIKE 'delivery_checklists'")->rowCount() > 0;
    if (!$has) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            http_response_code(501);
            echo json_encode(['success' => false, 'message' => 'Table delivery_checklists not found']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0 && isset($_POST['completed'])) {
            $completed = (int)$_POST['completed'];
            $pdo->prepare("UPDATE delivery_checklists SET completed = ?, completed_at = " . ($completed ? "NOW()" : "NULL") . " WHERE id = ?")->execute([$completed, $id]);
            echo json_encode(['success' => true, 'data' => ['id' => $id], 'timestamp' => date('Y-m-d H:i:s')]);
            exit;
        }
        $item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
        if ($item_name && $project_id > 0) {
            $pdo->prepare("INSERT INTO delivery_checklists (project_id, item_name, completed) VALUES (?, ?, 0)")->execute([$project_id, $item_name]);
            echo json_encode(['success' => true, 'data' => ['id' => (int)$pdo->lastInsertId()], 'timestamp' => date('Y-m-d H:i:s')]);
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM delivery_checklists WHERE project_id = ? ORDER BY id");
    $stmt->execute([$project_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
