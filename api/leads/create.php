<?php
/**
 * API Endpoint: Criar Lead
 * CRM Senior Floors - Captura com validação, duplicados, distribuição e tarefa automática
 * 
 * POST /api/leads/create.php
 * Campos: name, email, phone, zipcode, message, form-name,
 *   address, property_type, service_type, main_interest, source (opcional)
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obter dados (campos obrigatórios + opcionais)
$form_name = isset($_POST['form-name']) ? trim($_POST['form-name']) : 'contact-form';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : null;
$property_type = isset($_POST['property_type']) ? trim($_POST['property_type']) : null;
$service_type = isset($_POST['service_type']) ? trim($_POST['service_type']) : null;
$main_interest = isset($_POST['main_interest']) ? trim($_POST['main_interest']) : null;
$source_override = isset($_POST['source']) ? trim($_POST['source']) : null;

$errors = [];
if (empty($name) || strlen($name) < 2) $errors[] = 'Name is required and must be at least 2 characters';
if (empty($phone)) $errors[] = 'Phone number is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email address is required';
if (empty($zipcode) || !preg_match('/^\d{5}(-\d{4})?$/', $zipcode)) $errors[] = 'Valid zip code is required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$zipcode = htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8');
$address = $address ? htmlspecialchars($address, ENT_QUOTES, 'UTF-8') : null;
$property_type = $property_type && in_array($property_type, ['casa','apartamento','comercial']) ? $property_type : null;
$service_type = $service_type ? htmlspecialchars($service_type, ENT_QUOTES, 'UTF-8') : null;
$main_interest = $main_interest ? htmlspecialchars($main_interest, ENT_QUOTES, 'UTF-8') : null;
$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

$source = $source_override ?: (($form_name === 'hero-form') ? 'LP-Hero' : 'LP-Contact');

$db_saved = false;
$lead_id = null;
$is_duplicate = false;
$existing_lead_id = null;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) throw new PDOException('No connection');

        require_once __DIR__ . '/../../config/lead-logic.php';

        $dup = checkDuplicateLead($pdo, $email, preg_replace('/\D/', '', $phone), null);
        if ($dup['is_duplicate']) {
            $is_duplicate = true;
            $existing_lead_id = $dup['existing_id'];
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Lead já cadastrado (duplicado). Registro existente retornado.',
                'duplicate' => true,
                'data' => ['lead_id' => $existing_lead_id, 'saved_to_db' => false, 'saved_to_csv' => false],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        $owner_id = getNextOwnerRoundRobin($pdo);
        $pipeline_stage_id = 1;

        $columns = "name, email, phone, zipcode, message, source, form_type, status, priority, ip_address, owner_id";
        $placeholders = ":name, :email, :phone, :zipcode, :message, :source, :form_type, 'new', 'medium', :ip_address, :owner_id";
        $params = [
            ':name' => $name, ':email' => $email, ':phone' => $phone, ':zipcode' => $zipcode,
            ':message' => $message, ':source' => $source, ':form_type' => $form_name, ':ip_address' => $ip_address,
            ':owner_id' => $owner_id
        ];

        if ($address !== null) { $columns .= ", address"; $placeholders .= ", :address"; $params[':address'] = $address; }
        if ($property_type !== null) { $columns .= ", property_type"; $placeholders .= ", :property_type"; $params[':property_type'] = $property_type; }
        if ($service_type !== null) { $columns .= ", service_type"; $placeholders .= ", :service_type"; $params[':service_type'] = $service_type; }
        if ($main_interest !== null) { $columns .= ", main_interest"; $placeholders .= ", :main_interest"; $params[':main_interest'] = $main_interest; }

        $has_stages = false;
        try {
            $chk = $pdo->query("SELECT 1 FROM pipeline_stages LIMIT 1");
            $has_stages = $chk && $chk->rowCount() > 0;
        } catch (Exception $e) {}
        if ($has_stages) {
            $columns .= ", pipeline_stage_id";
            $placeholders .= ", :pipeline_stage_id";
            $params[':pipeline_stage_id'] = $pipeline_stage_id;
        }

        $stmt = $pdo->prepare("INSERT INTO leads ($columns) VALUES ($placeholders)");
        $stmt->execute($params);
        $lead_id = (int) $pdo->lastInsertId();
        $db_saved = true;

        if ($lead_id && function_exists('createLeadEntryTask')) {
            try {
                $has_tasks = $pdo->query("SHOW TABLES LIKE 'tasks'")->rowCount() > 0;
                if ($has_tasks) createLeadEntryTask($pdo, $lead_id, $owner_id);
            } catch (Exception $e) { /* ignore */ }
        }
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
        error_log("API leads/create: " . $db_error);
    }
}

$csv_saved = false;
$log_dir = dirname(__DIR__, 2);
$csv_file = $log_dir . '/leads.csv';
if (!$is_duplicate) {
    $csv_line = [date('Y-m-d H:i:s'), $form_name, $name, $phone, $email, $zipcode, str_replace(["\r\n","\n","\r"], ' ', $message)];
    if (!file_exists($csv_file)) @file_put_contents($csv_file, "Date,Form,Name,Phone,Email,ZipCode,Message\n", LOCK_EX);
    $csv_data = '"' . implode('","', array_map(function($f) { return str_replace('"', '""', $f); }, $csv_line)) . "\"\n";
    $csv_saved = @file_put_contents($csv_file, $csv_data, FILE_APPEND | LOCK_EX) !== false;
}

$telegram_sent = false;
if ($db_saved && file_exists(__DIR__ . '/../../libs/telegram-notifier.php')) {
    require_once __DIR__ . '/../../libs/telegram-notifier.php';
    $telegram_sent = sendTelegramNotification([
        'name' => $name, 'email' => $email, 'phone' => $phone, 'zipcode' => $zipcode,
        'message' => $message, 'source' => $source, 'form_type' => $form_name, 'ip_address' => $ip_address
    ])['success'] ?? false;
}

if ($db_saved || $csv_saved) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Lead created successfully',
        'data' => [
            'lead_id' => $lead_id,
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
