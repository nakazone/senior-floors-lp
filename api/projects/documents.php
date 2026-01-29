<?php
/**
 * API: Listar ou adicionar documentos do projeto (pós-venda)
 * GET project_id | POST project_id, file_path, doc_type
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
    $has = $pdo->query("SHOW TABLES LIKE 'project_documents'")->rowCount() > 0;
    if (!$has) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            http_response_code(501);
            echo json_encode(['success' => false, 'message' => 'Table project_documents not found']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $file_path = isset($_POST['file_path']) ? trim($_POST['file_path']) : '';
        $doc_type = isset($_POST['doc_type']) ? trim($_POST['doc_type']) : null;
        $uploaded_by = isset($_POST['uploaded_by']) ? (int)$_POST['uploaded_by'] : null;
        if (!$file_path) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'file_path required']);
            exit;
        }
        $pdo->prepare("INSERT INTO project_documents (project_id, file_path, doc_type, uploaded_by) VALUES (?, ?, ?, ?)")
            ->execute([$project_id, $file_path, $doc_type, $uploaded_by]);
        echo json_encode(['success' => true, 'data' => ['id' => (int)$pdo->lastInsertId()], 'timestamp' => date('Y-m-d H:i:s')]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM project_documents WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$project_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
