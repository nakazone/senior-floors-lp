<?php
/**
 * API Endpoint: Adicionar Observação ao Lead
 * FASE 2 - MÓDULO 04: Painel Admin (MVP)
 * 
 * Endpoint: POST /api/leads/notes.php
 * 
 * Adiciona uma observação interna a um lead
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obter dados
$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';
$created_by = isset($_POST['created_by']) ? trim($_POST['created_by']) : 'admin';

// Validação
if ($lead_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

if (empty($note)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Note is required']);
    exit;
}

// Sanitizar
$note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
$created_by = htmlspecialchars($created_by, ENT_QUOTES, 'UTF-8');

// Salvar no banco de dados
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
    
    // Verificar se o lead existe
    $stmt = $pdo->prepare("SELECT id FROM leads WHERE id = :id");
    $stmt->execute([':id' => $lead_id]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
        exit;
    }
    
    // Inserir observação
    $stmt = $pdo->prepare("
        INSERT INTO lead_notes (lead_id, note, created_by)
        VALUES (:lead_id, :note, :created_by)
    ");
    
    $stmt->execute([
        ':lead_id' => $lead_id,
        ':note' => $note,
        ':created_by' => $created_by
    ]);
    
    $note_id = $pdo->lastInsertId();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully',
        'data' => [
            'note_id' => $note_id,
            'lead_id' => $lead_id,
            'note' => $note,
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s')
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Lead note error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add note',
        'error' => $e->getMessage()
    ]);
}
