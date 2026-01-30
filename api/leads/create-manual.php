<?php
/**
 * API: Criar lead manualmente (painel admin)
 * Para leads que chegaram por telefone, indicação, evento, etc.
 *
 * POST /api/leads/create-manual.php
 * Requer sessão admin. Campos: name, phone, email (opcional), zipcode (opcional), message (opcional), source (opcional), owner_id (opcional)
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../config/database.php';

if (file_exists(__DIR__ . '/../../config/permissions.php')) {
    require_once __DIR__ . '/../../config/permissions.php';
}

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'] ?? null;
$admin_role = trim((string)($_SESSION['admin_role'] ?? ''));
$has_permission = ($admin_role === 'admin' || $admin_user_id === null);
if (!$has_permission && function_exists('hasPermission') && $admin_user_id) {
    $has_permission = hasPermission($admin_user_id, 'leads.create');
}
if (!$has_permission) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$source = isset($_POST['source']) ? trim($_POST['source']) : 'Manual';
$owner_id = isset($_POST['owner_id']) ? (int) $_POST['owner_id'] : null;

$errors = [];
if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Nome é obrigatório (mínimo 2 caracteres).';
}
if (empty($phone) || strlen(preg_replace('/\D/', '', $phone)) < 8) {
    $errors[] = 'Telefone é obrigatório (mínimo 8 dígitos).';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail inválido (deixe em branco se não tiver).';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email_raw = $email;
$email = $email !== '' ? filter_var($email, FILTER_SANITIZE_EMAIL) : null;
$zipcode = $zipcode !== '' ? htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8') : null;
$message = $message !== '' ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '';
$source = $source !== '' ? htmlspecialchars($source, ENT_QUOTES, 'UTF-8') : 'Manual';
// Coluna email é NOT NULL: se não informado, usar placeholder único
if ($email === null || $email === '') {
    $email = 'manual-' . time() . '-' . substr(md5($phone), 0, 8) . '@lead.local';
}

$db_saved = false;
$lead_id = null;
$is_duplicate = false;
$existing_lead_id = null;
$db_error = null;

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Banco de dados não configurado.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new PDOException('No connection');
    }

    require_once __DIR__ . '/../../config/lead-logic.php';

    $phone_normalized = preg_replace('/\D/', '', $phone);
    $dup = checkDuplicateLead($pdo, $email_raw !== '' ? $email : '', $phone_normalized, null);
    if ($dup['is_duplicate']) {
        $is_duplicate = true;
        $existing_lead_id = $dup['existing_id'];
        echo json_encode([
            'success' => true,
            'message' => 'Lead já cadastrado com este e-mail ou telefone. Redirecionando para o registro existente.',
            'duplicate' => true,
            'data' => ['lead_id' => $existing_lead_id],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    if ($owner_id === 0) {
        $owner_id = null;
    }
    if ($owner_id === null && function_exists('getNextOwnerRoundRobin')) {
        $owner_id = getNextOwnerRoundRobin($pdo);
    }

    $pipeline_stage_id = 1;
    $has_stages = false;
    try {
        $chk = $pdo->query("SELECT 1 FROM pipeline_stages LIMIT 1");
        $has_stages = $chk && $chk->rowCount() > 0;
        if ($has_stages) {
            $first = $pdo->query("SELECT id FROM pipeline_stages ORDER BY order_num ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($first) {
                $pipeline_stage_id = (int) $first['id'];
            }
        }
    } catch (Exception $e) {}

    $columns = "name, email, phone, zipcode, message, source, form_type, status, priority, owner_id";
    $placeholders = ":name, :email, :phone, :zipcode, :message, :source, 'manual', 'new', 'medium', :owner_id";
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':zipcode' => $zipcode ?? '',
        ':message' => $message,
        ':source' => $source,
        ':owner_id' => $owner_id
    ];

    $has_pipeline = false;
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'");
        $has_pipeline = $chk && $chk->rowCount() > 0;
    } catch (Exception $e) {}
    if ($has_pipeline && $has_stages) {
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
            if ($has_tasks) {
                createLeadEntryTask($pdo, $lead_id, $owner_id);
            }
        } catch (Exception $e) {}
    }
} catch (PDOException $e) {
    $db_error = $e->getMessage();
    error_log("API leads/create-manual: " . $db_error);
}

if ($db_saved) {
    echo json_encode([
        'success' => true,
        'message' => 'Lead criado com sucesso.',
        'data' => ['lead_id' => $lead_id],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Falha ao salvar o lead.',
        'error' => $db_error ?? 'Unknown error'
    ]);
}
