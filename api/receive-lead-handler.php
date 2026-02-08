<?php
/**
 * Receive-lead API handler — runs BEFORE session/includes (called from system.php top).
 * Expects: $SYSTEM_ROOT set, config/database.php loaded (isDatabaseConfigured, getDBConnection).
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed', 'api_version' => 'receive-lead-v2-early']);
    exit;
}
header('Content-Type: application/json; charset=UTF-8');

// Fonte única do body: em requisições cURL (send-lead → system) muitos servidores NÃO preenchem $_POST.
// Ler sempre php://input para application/x-www-form-urlencoded e usar como fonte principal.
$post = [];
$ct = isset($_SERVER['CONTENT_TYPE']) ? (string) $_SERVER['CONTENT_TYPE'] : '';
$raw = @file_get_contents('php://input');
if (!empty($raw)) {
    if (strpos($ct, 'application/json') !== false) {
        $dec = @json_decode($raw, true);
        if (is_array($dec)) $post = $dec;
    } else {
        parse_str($raw, $parsed);
        if (!empty($parsed)) $post = $parsed;
    }
}
if (empty($post) || (empty($post['name']) && empty($post['email']))) {
    $post = $_POST;
}

$form_name = isset($post['form-name']) ? trim($post['form-name']) : 'contact-form';
$name = isset($post['name']) ? trim($post['name']) : '';
$phone = isset($post['phone']) ? trim($post['phone']) : '';
$email = isset($post['email']) ? trim($post['email']) : '';
$zipcode = isset($post['zipcode']) ? trim($post['zipcode']) : '';
$message = isset($post['message']) ? trim($post['message']) : '';

$errors = [];
if (empty($name) || strlen($name) < 2) $errors[] = 'Name is required';
if (empty($phone)) $errors[] = 'Phone is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
$zip_clean = preg_replace('/\D/', '', $zipcode ?? '');
if (empty($zip_clean) || strlen($zip_clean) < 5) $errors[] = 'Valid 5-digit US zip code is required';
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors, 'api_version' => 'receive-lead-v2-early']);
    exit;
}

$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$zipcode = substr(preg_replace('/\D/', '', $zipcode ?? ''), 0, 5);
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

$log_file = $SYSTEM_ROOT . '/system-api.log';
@file_put_contents($log_file, date('Y-m-d H:i:s') . " | receive-lead POST received (v2-early) | name=$name | email=" . substr($email, 0, 40) . "\n", FILE_APPEND | LOCK_EX);

$lead_id = null;
$db_saved = false;
$inserted_new = null;
$db_error_reason = null;
if (!isDatabaseConfigured()) {
    $db_error_reason = 'Database not configured (config/database.php missing or placeholders not replaced)';
} else {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            $db_error_reason = 'Could not connect to database (getDBConnection returned null)';
        } else {
            $check_table = $pdo->query("SHOW TABLES LIKE 'leads'");
            if (!$check_table || $check_table->rowCount() === 0) {
                $db_error_reason = "Table 'leads' does not exist. Run database/schema-v3-completo.sql in MySQL.";
            } else {
                $source = ($form_name === 'hero-form') ? 'LP-Hero' : 'LP-Contact';
                $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
                $owner_id = null;
                $is_dup = false;
                $existing_id = null;
                $lead_logic = $SYSTEM_ROOT . '/config/lead-logic.php';
                if (file_exists($lead_logic)) {
                    try {
                        require_once $lead_logic;
                        $dup = checkDuplicateLead($pdo, $email, preg_replace('/\D/', '', $phone), null);
                        if ($dup['is_duplicate']) {
                            $is_dup = true;
                            $existing_id = $dup['existing_id'];
                            $lead_id = $existing_id;
                            $db_saved = true;
                            $inserted_new = false;
                        } else {
                            $owner_id = getNextOwnerRoundRobin($pdo);
                        }
                    } catch (Throwable $e) {
                        @file_put_contents($log_file, date('Y-m-d H:i:s') . " | ⚠️ lead-logic: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
                    }
                }
                if (!$is_dup) {
                    $cols = "name, email, phone, zipcode, message, source, form_type, status, priority, ip_address";
                    $place = ":name, :email, :phone, :zipcode, :message, :source, :form_type, 'new', 'medium', :ip_address";
                    $params = [
                        ':name' => $name, ':email' => $email, ':phone' => $phone, ':zipcode' => $zipcode,
                        ':message' => $message, ':source' => $source, ':form_type' => $form_name, ':ip_address' => $ip_address
                    ];
                    if ($owner_id !== null) {
                        try {
                            $pdo->query("SELECT owner_id FROM leads LIMIT 1");
                            $cols .= ", owner_id";
                            $place .= ", :owner_id";
                            $params[':owner_id'] = $owner_id;
                        } catch (Throwable $e) {}
                    }
                    try {
                        $pdo->query("SELECT pipeline_stage_id FROM leads LIMIT 1");
                        $cols .= ", pipeline_stage_id";
                        $place .= ", 1";
                    } catch (Throwable $e) {}
                    $stmt = $pdo->prepare("INSERT INTO leads ($cols) VALUES ($place)");
                    $stmt->execute($params);
                    $lead_id = (int) $pdo->lastInsertId();
                    $db_saved = true;
                    $inserted_new = true;
                    if ($lead_id && function_exists('createLeadEntryTask')) {
                        $has_tasks = $pdo->query("SHOW TABLES LIKE 'tasks'")->rowCount() > 0;
                        if ($has_tasks) createLeadEntryTask($pdo, $lead_id, $owner_id);
                    }
                }
            }
        }
    } catch (Throwable $e) {
        $db_error_reason = $e->getMessage();
        @file_put_contents($log_file, date('Y-m-d H:i:s') . " | ❌ API receive-lead DB error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    }
}

@file_put_contents($log_file, date('Y-m-d H:i:s') . " | ✅ API: Lead received" . ($db_saved ? " and saved to DB (id=$lead_id)" : " (DB not saved)") . " | Form: $form_name\n", FILE_APPEND | LOCK_EX);

$resp = [
    'success' => true,
    'message' => 'Thank you! We\'ll contact you within 24 hours.',
    'timestamp' => date('Y-m-d H:i:s'),
    'lead_id' => $lead_id,
    'database_saved' => $db_saved,
    'inserted_new' => $inserted_new,
    'email_sent' => false,
    'api_version' => 'receive-lead-v2-early',
    'data' => [
        'form_type' => $form_name,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'zipcode' => $zipcode
    ]
];
if (!$db_saved) {
    $resp['db_error'] = $db_error_reason ?: 'Unknown (check system-api.log on panel server)';
}
http_response_code(200);
echo json_encode($resp);
exit;
