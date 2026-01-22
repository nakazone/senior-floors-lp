<?php
/**
 * API Endpoint: Criar Lead
 * FASE 1 - MÓDULO 01: Central de Leads
 * 
 * Endpoint: POST /api/leads/create.php
 * 
 * Recebe dados do formulário da Landing Page,
 * valida, sanitiza e salva no banco de dados MySQL.
 * Mantém compatibilidade com CSV (backup).
 */

// Headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configurações
require_once __DIR__ . '/../../config/database.php';

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obter dados do formulário
$form_name = isset($_POST['form-name']) ? trim($_POST['form-name']) : 'contact-form';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validação
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name is required and must be at least 2 characters';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}

if (empty($zipcode) || !preg_match('/^\d{5}(-\d{4})?$/', $zipcode)) {
    $errors[] = 'Valid zip code is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Sanitizar dados
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$zipcode = htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

// Determinar source baseado no form-type
$source = ($form_name === 'hero-form') ? 'LP-Hero' : 'LP-Contact';

// Salvar no banco de dados (se configurado)
$db_saved = false;
$db_error = null;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        if ($pdo) {
            $stmt = $pdo->prepare("
                INSERT INTO leads (name, email, phone, zipcode, message, source, form_type, status, priority, ip_address)
                VALUES (:name, :email, :phone, :zipcode, :message, :source, :form_type, 'new', 'medium', :ip_address)
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':zipcode' => $zipcode,
                ':message' => $message,
                ':source' => $source,
                ':form_type' => $form_name,
                ':ip_address' => $ip_address
            ]);
            
            $db_saved = true;
            $lead_id = $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
        error_log("Database error: " . $db_error);
    }
}

// ============================================
// TELEGRAM NOTIFICATION (FASE 1 - MÓDULO 02)
// ============================================
// Enviar notificação via Telegram se o lead foi salvo com sucesso
$telegram_sent = false;
if ($db_saved) {
    $telegram_lib = __DIR__ . '/../../libs/telegram-notifier.php';
    if (file_exists($telegram_lib)) {
        require_once $telegram_lib;
        
        $lead_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'zipcode' => $zipcode,
            'message' => $message,
            'source' => $source,
            'form_type' => $form_name,
            'ip_address' => $ip_address
        ];
        
        $telegram_result = sendTelegramNotification($lead_data);
        $telegram_sent = $telegram_result['success'];
        
        // Log do resultado
        if ($telegram_sent) {
            $log_entry = date('Y-m-d H:i:s') . " | ✅ Telegram notification sent\n";
        } else {
            $log_entry = date('Y-m-d H:i:s') . " | ⚠️ Telegram notification failed: " . ($telegram_result['error'] ?? 'Unknown error') . "\n";
        }
        @file_put_contents(__DIR__ . '/../../telegram-notifications.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Salvar também no CSV (backup/compatibilidade)
$csv_saved = false;
$log_dir = dirname(__DIR__, 2); // Voltar para public_html
$csv_file = $log_dir . '/leads.csv';

$csv_line = [
    date('Y-m-d H:i:s'),
    $form_name,
    $name,
    $phone,
    $email,
    $zipcode,
    str_replace(["\r\n", "\n", "\r"], ' ', $message)
];

if (!file_exists($csv_file)) {
    $header = "Date,Form,Name,Phone,Email,ZipCode,Message\n";
    @file_put_contents($csv_file, $header, LOCK_EX);
}

$csv_data = '"' . implode('","', array_map(function($field) {
    return str_replace('"', '""', $field);
}, $csv_line)) . "\"\n";

$csv_saved = @file_put_contents($csv_file, $csv_data, FILE_APPEND | LOCK_EX);

// Log
$log_entry = date('Y-m-d H:i:s') . " | API Lead Created | Name: $name | Email: $email | DB: " . ($db_saved ? 'OK' : 'FAIL') . " | CSV: " . ($csv_saved ? 'OK' : 'FAIL') . "\n";
@file_put_contents($log_dir . '/api-leads.log', $log_entry, FILE_APPEND | LOCK_EX);

// Resposta
if ($db_saved || $csv_saved) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Lead created successfully',
        'data' => [
            'lead_id' => $db_saved ? $lead_id : null,
            'saved_to_db' => $db_saved,
            'saved_to_csv' => (bool)$csv_saved,
            'telegram_sent' => $telegram_sent
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save lead',
        'error' => $db_error ?? 'Unknown error'
    ]);
}
