<?php
/**
 * API Endpoint: Obter Lead por ID
 * FASE 2 - MÓDULO 04: Painel Admin (MVP)
 * 
 * Endpoint: GET /api/leads/get.php?id=123
 * 
 * Retorna os dados completos de um lead, incluindo observações
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

// Obter ID do lead
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lead_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

// Buscar no banco de dados
if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('Failed to connect to database');
    }
    
    // Buscar lead
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id");
    $stmt->execute([':id' => $lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
        exit;
    }
    
    // Buscar observações
    $stmt = $pdo->prepare("
        SELECT id, note, created_by, created_at
        FROM lead_notes
        WHERE lead_id = :lead_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([':lead_id' => $lead_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar tags (coluna pode ser tag ou tag_name conforme o schema)
    $tag_col = 'tag_name';
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM lead_tags LIKE 'tag_name'");
        if (!$chk || $chk->rowCount() === 0) $tag_col = 'tag';
    } catch (Exception $e) { $tag_col = 'tag'; }
    $tag_select = $tag_col === 'tag_name' ? 'id, tag_name, created_at' : 'id, tag AS tag_name, created_at';
    $stmt = $pdo->prepare("
        SELECT $tag_select
        FROM lead_tags
        WHERE lead_id = :lead_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([':lead_id' => $lead_id]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'lead' => $lead,
            'notes' => $notes,
            'tags' => $tags
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Get lead error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get lead',
        'error' => $e->getMessage()
    ]);
}
