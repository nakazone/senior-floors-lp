<?php
/**
 * API: Buscar leads por nome, email ou telefone
 * GET q=xxx&limit=30
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../config/database.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? min(100, max(5, (int)$_GET['limit'])) : 30;

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    if ($pdo->query("SHOW TABLES LIKE 'leads'")->rowCount() === 0) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $sql = "SELECT id, name, email, phone, created_at FROM leads WHERE 1=1";
    $params = [];
    if ($q !== '') {
        $term = '%' . $q . '%';
        $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR id = ?)";
        $params = [$term, $term, $term, ctype_digit($q) ? (int)$q : -1];
    }
    $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $leads]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
