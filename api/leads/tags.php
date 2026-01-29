<?php
/**
 * API Endpoint: Gerenciar Tags do Lead
 * FASE 2 - MÓDULO 05: Tags e Qualificação
 * 
 * Endpoint: POST /api/leads/tags.php
 * 
 * Adiciona ou remove tags de um lead
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/tags.php';

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obter dados
$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : ''; // 'add' or 'remove'
$tag = isset($_POST['tag']) ? trim($_POST['tag']) : '';

// Validação
if ($lead_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

if (empty($action) || !in_array($action, ['add', 'remove'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action. Use "add" or "remove"']);
    exit;
}

if (empty($tag)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tag is required']);
    exit;
}

// Validar tag
if (!isValidTag($tag)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid tag']);
    exit;
}

// Gerenciar tag no banco de dados
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
    
    if ($action === 'add') {
        // Adicionar tag (ignorar se já existir devido ao UNIQUE constraint)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO lead_tags (lead_id, tag_name)
                VALUES (:lead_id, :tag)
            ");
            $stmt->execute([
                ':lead_id' => $lead_id,
                ':tag' => $tag
            ]);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Tag added successfully',
                'data' => [
                    'lead_id' => $lead_id,
                    'tag' => $tag,
                    'tag_label' => getTagLabel($tag)
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            // Tag já existe (UNIQUE constraint)
            if ($e->getCode() == 23000) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Tag already exists',
                    'data' => [
                        'lead_id' => $lead_id,
                        'tag' => $tag,
                        'tag_label' => getTagLabel($tag)
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw $e;
            }
        }
    } else {
        // Remover tag
        $stmt = $pdo->prepare("
            DELETE FROM lead_tags
            WHERE lead_id = :lead_id AND tag_name = :tag
        ");
        $stmt->execute([
            ':lead_id' => $lead_id,
            ':tag' => $tag
        ]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Tag removed successfully',
                'data' => [
                    'lead_id' => $lead_id,
                    'tag' => $tag
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Tag not found on this lead']);
        }
    }
    
} catch (Exception $e) {
    error_log("Lead tag error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to manage tag',
        'error' => $e->getMessage()
    ]);
}
