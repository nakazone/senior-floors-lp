<?php
/**
 * Senior Floors System Admin Panel
 * Main entry point for all admin functions
 */

// ========== API FIRST: run BEFORE session/includes so response is always pure JSON ==========
if (isset($_GET['api']) && ($_GET['api'] === 'receive-lead' || $_GET['api'] === 'db-check')) {
    $SYSTEM_ROOT = __DIR__;
    if (!is_file(__DIR__ . '/config/database.php') && is_file(dirname(__DIR__) . '/config/database.php')) {
        $SYSTEM_ROOT = dirname(__DIR__);
    }
    require_once $SYSTEM_ROOT . '/config/database.php';
    if ($_GET['api'] === 'db-check') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        $out = [
            'config_loaded' => true,
            'database_configured' => isDatabaseConfigured(),
            'connection_ok' => false,
            'table_leads_exists' => false,
            'hint' => '',
            'api_version' => 'v2-early'
        ];
        if (!$out['database_configured']) {
            $out['hint'] = 'Edite config/database.php no servidor e substitua DB_USER/DB_PASS (não use seu_usuario/sua_senha).';
        } else {
            try {
                $pdo = getDBConnection();
                $out['connection_ok'] = ($pdo !== null);
                if ($pdo) {
                    $t = $pdo->query("SHOW TABLES LIKE 'leads'");
                    $out['table_leads_exists'] = $t && $t->rowCount() > 0;
                    if (!$out['table_leads_exists']) {
                        $out['hint'] = "Tabela 'leads' não existe. Execute no MySQL: database/schema-v3-completo.sql (ou schema.sql).";
                    }
                } else {
                    $out['hint'] = 'Falha ao conectar ao MySQL. Verifique DB_HOST, DB_NAME, DB_USER e DB_PASS em config/database.php.';
                }
            } catch (Throwable $e) {
                $out['connection_ok'] = false;
                $out['hint'] = $e->getMessage();
            }
        }
        echo json_encode($out);
        exit;
    }
    if ($_GET['api'] === 'receive-lead') {
        $api_handler = $SYSTEM_ROOT . '/api/receive-lead-handler.php';
        if (is_file($api_handler)) {
            require $api_handler;
            exit;
        }
    }
}

// API responses must be pure JSON; capture any accidental output from session/includes
if (isset($_GET['api']) && ($_GET['api'] === 'receive-lead' || $_GET['api'] === 'db-check')) {
    ob_start();
}
session_start();

// Raiz do projeto (funciona com system.php na raiz ou em subpasta como /lp/)
$SYSTEM_ROOT = __DIR__;
if (!is_file(__DIR__ . '/config/permissions.php') && is_file(dirname(__DIR__) . '/config/permissions.php')) {
    $SYSTEM_ROOT = dirname(__DIR__);
}

// ============================================
// CONFIGURATION
// ============================================
$ADMIN_TITLE = 'Senior Floors System';

// Load permissions system
require_once $SYSTEM_ROOT . '/config/permissions.php';

// Load admin users configuration
require_once $SYSTEM_ROOT . '/admin-config.php';

// ============================================
// AUTHENTICATION
// ============================================
if (!isset($_SESSION['admin_authenticated'])) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Try database authentication first
        $authenticated = false;
        $user_info = null;
        
        if (isDatabaseConfigured()) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
                $stmt->execute([$username]);
                $db_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($db_user && !empty($db_user['password_hash'])) {
                    if (password_verify($password, $db_user['password_hash'])) {
                        $authenticated = true;
                        $user_info = [
                            'id' => $db_user['id'],
                            'name' => $db_user['name'],
                            'email' => $db_user['email'],
                            'role' => $db_user['role']
                        ];
                        
                        // Update last login
                        $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
                        $update_stmt->execute([$db_user['id']]);
                    }
                }
            } catch (Exception $e) {
                error_log("Database authentication error: " . $e->getMessage());
            }
        }
        
        // Fallback to admin-config.php if database auth fails
        if (!$authenticated && function_exists('verifyAdminUser')) {
            if (verifyAdminUser($username, $password)) {
                $authenticated = true;
                $user_info = getAdminUser($username);
            }
        }
        
        if ($authenticated) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_user_id'] = $user_info['id'] ?? null;
            $_SESSION['admin_name'] = $user_info['name'] ?? $username;
            $_SESSION['admin_role'] = $user_info['role'] ?? 'admin';
            header('Location: system.php');
            exit;
        } else {
            $login_error = 'Invalid username or password';
        }
    }
    
    // Show login page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <title><?php echo htmlspecialchars($ADMIN_TITLE); ?> - Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            html { -webkit-tap-highlight-color: transparent; }
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
                min-height: 100dvh;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                padding-top: max(20px, env(safe-area-inset-top));
                padding-bottom: max(20px, env(safe-area-inset-bottom));
            }
            .login-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                padding: 40px;
                max-width: 400px;
                width: 100%;
            }
            h1 { color: #333; margin-bottom: 10px; }
            p { color: #666; margin-bottom: 30px; }
            input {
                width: 100%;
                padding: 14px 12px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
                margin-bottom: 15px;
                min-height: 48px;
            }
            input:focus {
                outline: none;
                border-color: #1a2036;
            }
            button {
                width: 100%;
                padding: 14px 12px;
                min-height: 48px;
                background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
            }
            button:hover { opacity: 0.9; }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1><?php echo htmlspecialchars($ADMIN_TITLE); ?></h1>
            <p>Enter your credentials to access the admin panel</p>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required autofocus>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: system.php');
    exit;
}

// ============================================
// API ENDPOINT - DB check (diagnóstico: por que database_saved: false?)
// ============================================
if (isset($_GET['api']) && $_GET['api'] === 'db-check') {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    $out = [
        'config_loaded' => true,
        'database_configured' => isDatabaseConfigured(),
        'connection_ok' => false,
        'table_leads_exists' => false,
        'hint' => ''
    ];
    if (!$out['database_configured']) {
        $out['hint'] = 'Edite config/database.php no servidor e substitua DB_USER/DB_PASS (não use seu_usuario/sua_senha).';
    } else {
        try {
            $pdo = getDBConnection();
            $out['connection_ok'] = ($pdo !== null);
            if ($pdo) {
                $t = $pdo->query("SHOW TABLES LIKE 'leads'");
                $out['table_leads_exists'] = $t && $t->rowCount() > 0;
                if (!$out['table_leads_exists']) {
                    $out['hint'] = "Tabela 'leads' não existe. Execute no MySQL: database/schema-v3-completo.sql (ou schema.sql).";
                }
            } else {
                $out['hint'] = 'Falha ao conectar ao MySQL. Verifique DB_HOST, DB_NAME, DB_USER e DB_PASS em config/database.php.';
            }
        } catch (Throwable $e) {
            $out['connection_ok'] = false;
            $out['hint'] = $e->getMessage();
        }
    }
    echo json_encode($out);
    exit;
}

// ============================================
// API ENDPOINT - Receive Form Submissions
// ============================================
if (isset($_GET['api']) && $_GET['api'] === 'receive-lead') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    header('Content-Type: application/json; charset=UTF-8');
    
    // Get form data (POST form-urlencoded or JSON body — send-lead.php envia form-urlencoded)
    $form_name = isset($_POST['form-name']) ? trim($_POST['form-name']) : 'contact-form';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    if (empty($name) && empty($email) && ($raw = @file_get_contents('php://input'))) {
        $json = @json_decode($raw, true);
        if (is_array($json)) {
            $form_name = isset($json['form-name']) ? trim($json['form-name']) : $form_name;
            $name = isset($json['name']) ? trim($json['name']) : '';
            $phone = isset($json['phone']) ? trim($json['phone']) : '';
            $email = isset($json['email']) ? trim($json['email']) : '';
            $zipcode = isset($json['zipcode']) ? trim($json['zipcode']) : '';
            $message = isset($json['message']) ? trim($json['message']) : $message;
        } else {
            parse_str($raw, $parsed);
            if (!empty($parsed)) {
                $form_name = isset($parsed['form-name']) ? trim($parsed['form-name']) : $form_name;
                $name = isset($parsed['name']) ? trim($parsed['name']) : '';
                $phone = isset($parsed['phone']) ? trim($parsed['phone']) : '';
                $email = isset($parsed['email']) ? trim($parsed['email']) : '';
                $zipcode = isset($parsed['zipcode']) ? trim($parsed['zipcode']) : '';
                $message = isset($parsed['message']) ? trim($parsed['message']) : $message;
            }
        }
    }
    
    // Validate
    $errors = [];
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name is required';
    }
    if (empty($phone)) {
        $errors[] = 'Phone is required';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    if (empty($zipcode) || !preg_match('/^\d{5}(-\d{4})?$/', $zipcode)) {
        $errors[] = 'Valid zip code is required';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    // Sanitize
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $zipcode = htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    $log_file = __DIR__ . '/system-api.log';
    @file_put_contents($log_file, date('Y-m-d H:i:s') . " | receive-lead POST received | name=$name | email=" . substr($email, 0, 40) . "\n", FILE_APPEND | LOCK_EX);
    
    // Save lead to database (same DB/table as CRM so it appears in the system)
    $lead_id = null;
    $db_saved = false;
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
                if ($check_table->rowCount() === 0) {
                    $db_error_reason = "Table 'leads' does not exist. Run database/schema-v3-completo.sql (or schema.sql) in MySQL.";
                } elseif ($check_table->rowCount() > 0) {
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
                            } else {
                                $owner_id = getNextOwnerRoundRobin($pdo);
                            }
                        } catch (Throwable $e) {
                            // lead-logic falhou (ex.: tabela users não existe) — segue sem owner_id
                            @file_put_contents(__DIR__ . '/system-api.log', date('Y-m-d H:i:s') . " | ⚠️ lead-logic: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
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
                        if ($lead_id && function_exists('createLeadEntryTask')) {
                            $has_tasks = $pdo->query("SHOW TABLES LIKE 'tasks'")->rowCount() > 0;
                            if ($has_tasks) {
                                createLeadEntryTask($pdo, $lead_id, $owner_id);
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $db_error_reason = $e->getMessage();
            $log_file = __DIR__ . '/system-api.log';
            @file_put_contents($log_file, date('Y-m-d H:i:s') . " | ❌ API receive-lead DB error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    // Log to system API log
    $log_file = __DIR__ . '/system-api.log';
    $log_entry = date('Y-m-d H:i:s') . " | ✅ API: Lead received" . ($db_saved ? " and saved to DB (id=$lead_id)" : " (DB not saved)") . " | Form: $form_name | Name: $name | Email: $email | Phone: $phone\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // Optional: send notification email (same DB server = PHPMailer may be in $SYSTEM_ROOT/PHPMailer)
    $email_sent = false;
    $phpmailer_path = $SYSTEM_ROOT . '/PHPMailer/PHPMailer.php';
    $smtp_config = $SYSTEM_ROOT . '/config/smtp.php';
    if (file_exists($phpmailer_path) && file_exists($smtp_config)) {
        require_once $SYSTEM_ROOT . '/PHPMailer/Exception.php';
            require_once $SYSTEM_ROOT . '/PHPMailer/PHPMailer.php';
            require_once $SYSTEM_ROOT . '/PHPMailer/SMTP.php';
            require_once $smtp_config;
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = defined('SMTP_USER') ? SMTP_USER : '';
                $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : '';
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
                $mail->CharSet = 'UTF-8';
                $mail->setFrom(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $mail->Username, defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Senior Floors');
                $mail->addAddress(defined('SMTP_TO_EMAIL') ? SMTP_TO_EMAIL : $mail->Username);
                $mail->addReplyTo($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'New Lead from Website - ' . ($form_name === 'hero-form' ? 'Hero' : 'Contact');
                $mail->Body = '<p><strong>Name:</strong> ' . $name . '</p><p><strong>Email:</strong> ' . $email . '</p><p><strong>Phone:</strong> ' . $phone . '</p><p><strong>Zip:</strong> ' . $zipcode . '</p>' . ($message ? '<p><strong>Message:</strong> ' . nl2br(htmlspecialchars($message)) . '</p>' : '');
                $mail->send();
                $email_sent = true;
            } catch (Throwable $e) {
                @file_put_contents($SYSTEM_ROOT . '/system-api.log', date('Y-m-d H:i:s') . " | ⚠️ Email: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            }
    }
    
    $resp = [
        'success' => true,
        'message' => 'Thank you! We\'ll contact you within 24 hours.',
        'timestamp' => date('Y-m-d H:i:s'),
        'lead_id' => $lead_id,
        'database_saved' => $db_saved,
        'email_sent' => $email_sent,
        'api_version' => 'receive-lead-v2',
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
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(200);
    echo json_encode($resp);
    exit;
}

// ============================================
// MODULE SYSTEM
// ============================================
$modules = [
    'dashboard' => [
        'name' => 'Dashboard',
        'icon' => '&#128202;',
        'file' => 'admin-modules/dashboard.php',
        'default' => true,
        'permission' => 'dashboard.view'
    ],
    'crm' => [
        'name' => 'CRM - Leads',
        'icon' => '&#128101;',
        'file' => 'admin-modules/crm.php',
        'permission' => 'leads.view'
    ],
    'pipeline' => [
        'name' => 'Pipeline (Kanban)',
        'icon' => '&#128203;',
        'file' => 'admin-modules/pipeline.php',
        'permission' => 'pipeline.view'
    ],
    'visits' => [
        'name' => 'Visitas e Medições',
        'icon' => '&#128197;',
        'file' => 'admin-modules/visits.php',
        'permission' => 'visits.view'
    ],
    'visit-detail' => [
        'name' => 'Detalhe Visita',
        'icon' => '&#128203;',
        'file' => 'admin-modules/visit-detail.php',
        'hidden' => true,
        'permission' => 'visits.view'
    ],
    'quotes' => [
        'name' => 'Orçamentos',
        'icon' => '&#128176;',
        'file' => 'admin-modules/quotes.php',
        'permission' => 'quotes.view'
    ],
    'quote-detail' => [
        'name' => 'Detalhe Orçamento',
        'icon' => '&#128203;',
        'file' => 'admin-modules/quote-detail.php',
        'hidden' => true,
        'permission' => 'quotes.view'
    ],
    'lead-detail' => [
        'name' => 'Lead Detail',
        'icon' => '&#128100;',
        'file' => 'admin-modules/lead-detail.php',
        'hidden' => true,
        'permission' => 'leads.view'
    ],
    'customers' => [
        'name' => 'Customers',
        'icon' => '&#127970;',
        'file' => 'admin-modules/customers.php',
        'permission' => 'customers.view'
    ],
    'customer-detail' => [
        'name' => 'Customer Detail',
        'icon' => '&#128100;',
        'file' => 'admin-modules/customer-detail.php',
        'hidden' => true,
        'permission' => 'customers.view'
    ],
    'projects' => [
        'name' => 'Projects',
        'icon' => '&#127959;',
        'file' => 'admin-modules/projects.php',
        'permission' => 'projects.view'
    ],
    'project-detail' => [
        'name' => 'Project Detail',
        'icon' => '&#128203;',
        'file' => 'admin-modules/project-detail.php',
        'hidden' => true,
        'permission' => 'projects.view'
    ],
    'coupons' => [
        'name' => 'Coupons',
        'icon' => '&#127915;',
        'file' => 'admin-modules/coupons.php',
        'permission' => 'coupons.view'
    ],
    'users' => [
        'name' => 'Users',
        'icon' => '&#128101;',
        'file' => 'admin-modules/users.php',
        'permission' => 'users.view'
    ],
    'user-detail' => [
        'name' => 'User Detail',
        'icon' => '&#128100;',
        'file' => 'admin-modules/user-detail.php',
        'hidden' => true,
        'permission' => 'users.view'
    ],
    'settings' => [
        'name' => 'Settings',
        'icon' => '⚙️',
        'file' => 'admin-modules/settings.php',
        'permission' => 'settings.view'
    ]
];

// Verifica se o usuário pode acessar um módulo (admin tem acesso a tudo; dashboard sempre visível)
function module_can_access($module_key, $modules, $session) {
    if (!isset($modules[$module_key])) return false;
    $module = $modules[$module_key];
    if ($module_key === 'dashboard') return true; // Todos os usuários logados veem o dashboard
    if (!isset($module['permission'])) return true;
    $role = trim((string)($session['admin_role'] ?? ''));
    // Admin (ou role vazio/legado) vê todos os módulos
    if ($role === 'admin' || $role === '') return true;
    $user_id = $session['admin_user_id'] ?? null;
    // Login por arquivo (admin-config) não tem user_id; tratar como admin
    if ($user_id === null) return true;
    return currentUserHasPermission($module['permission']);
}

// Get current module
$current_module = isset($_GET['module']) ? $_GET['module'] : 'dashboard';
if (!isset($modules[$current_module])) {
    $current_module = 'dashboard';
}

// Check if module file exists (usar $SYSTEM_ROOT para quando system.php está em subpasta)
$module_file = $SYSTEM_ROOT . '/' . $modules[$current_module]['file'];
if (!file_exists($module_file)) {
    $current_module = 'dashboard';
    $module_file = $SYSTEM_ROOT . '/' . $modules[$current_module]['file'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="description" content="Senior Floors CRM System - Manage leads, customers, projects, and coupons">
    <meta name="theme-color" content="#1a2036">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Senior Floors CRM">
    
    <!-- Favicon (cache-bust ?v=4 para ícone PWA no iPhone) -->
    <link rel="icon" type="image/png" sizes="32x32" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="icon" type="image/png" sizes="16x16" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="icon" type="image/png" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    
    <!-- Apple Touch Icons (iOS / Chrome no iPhone) -->
    <link rel="apple-touch-icon" sizes="57x57" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="60x60" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="72x72" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="76x76" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="114x114" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="120x120" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="144x144" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="152x152" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" sizes="180x180" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    <link rel="apple-touch-icon" href="https://www.senior-floors.com/logoSeniorFloors.png?v=6">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json?v=4">
    
    <title><?php echo htmlspecialchars($ADMIN_TITLE); ?> - <?php echo htmlspecialchars($modules[$current_module]['name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html {
            -webkit-tap-highlight-color: transparent;
            -webkit-text-size-adjust: 100%;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f7f8fc;
            color: #1a2036;
            min-height: 100dvh;
            min-height: 100vh;
        }
        .admin-header {
            background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
            color: white;
            padding: 15px 20px;
            padding-top: max(15px, env(safe-area-inset-top));
            box-shadow: 0 2px 10px rgba(26, 32, 54, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .logo-admin {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }
        .logo-admin img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }
        .logo-admin h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        h1 { font-size: 20px; font-weight: 600; }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 20px;
            padding: 20px;
        }
        .sidebar {
            width: 250px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 80px;
        }
        .sidebar-nav {
            list-style: none;
        }
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: #666;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 14px;
        }
        .sidebar-nav a:hover {
            background: #f0f2f8;
            color: #1a2036;
        }
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
            color: white;
            font-weight: 600;
        }
        .sidebar-nav a.active:hover {
            background: linear-gradient(135deg, #252b47 0%, #2a3150 100%);
        }
        /* Gold accent for highlights (matching LP) */
        .accent-gold {
            color: #d6b598;
        }
        .accent-gold-bg {
            background-color: #d6b598;
            color: white;
        }
        .main-content {
            flex: 1;
            min-width: 0;
        }
        .module-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }
        /* ========== Mobile: app-like shell ========== */
        @media (max-width: 768px) {
            html, body {
                overscroll-behavior: none;
                -webkit-overflow-scrolling: touch;
            }
            body {
                padding-bottom: env(safe-area-inset-bottom);
            }
            .admin-header {
                padding: 10px 12px;
                padding-top: max(10px, env(safe-area-inset-top));
            }
            .header-content {
                gap: 8px;
            }
            .logo-admin img {
                height: 32px;
            }
            .logo-admin h1 {
                font-size: 16px;
            }
            .header-actions span {
                font-size: 13px;
                margin-right: 8px !important;
            }
            .btn {
                padding: 8px 12px;
                font-size: 13px;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .admin-container {
                flex-direction: column;
                padding: 12px;
                padding-bottom: calc(72px + env(safe-area-inset-bottom));
            }
            .sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                height: auto;
                margin: 0;
                padding: 8px 8px calc(8px + env(safe-area-inset-bottom));
                border-radius: 16px 16px 0 0;
                box-shadow: 0 -4px 20px rgba(0,0,0,0.12);
                top: auto;
                z-index: 90;
                background: #fff;
                -webkit-overflow-scrolling: touch;
            }
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                gap: 4px;
                scrollbar-width: none;
                -ms-overflow-style: none;
                padding: 0 4px;
            }
            .sidebar-nav::-webkit-scrollbar {
                display: none;
            }
            .sidebar-nav li {
                margin-bottom: 0;
                flex-shrink: 0;
            }
            .sidebar-nav a {
                white-space: nowrap;
                flex-direction: column;
                gap: 4px;
                padding: 8px 12px;
                min-height: 44px;
                min-width: 56px;
                font-size: 11px;
                text-align: center;
                border-radius: 10px;
            }
            .sidebar-nav a span:first-child {
                font-size: 20px;
                line-height: 1;
            }
            .sidebar-nav a:hover {
                background: #f0f2f8;
            }
            .sidebar-nav a.active {
                background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
                color: white;
            }
            .module-content {
                padding: 16px;
                border-radius: 12px;
                margin-bottom: 0;
            }
            .module-content table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        /* Touch targets mínimos em mobile */
        @media (max-width: 768px) {
            .module-content a,
            .module-content button:not(.btn) {
                min-height: 44px;
                display: inline-flex;
                align-items: center;
            }
        }
    </style>
    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('Service Worker registrado com sucesso:', registration.scope);
                        
                        // Verificar atualizações periodicamente
                        setInterval(() => {
                            registration.update();
                        }, 60000); // A cada minuto
                    })
                    .catch((error) => {
                        console.log('Falha ao registrar Service Worker:', error);
                    });
            });
        }

        // Detectar se está instalado como PWA
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Pode mostrar um botão para instalar
            console.log('PWA pode ser instalado');
        });

        // Detectar quando PWA é instalado
        window.addEventListener('appinstalled', () => {
            console.log('PWA instalado com sucesso');
            deferredPrompt = null;
        });
    </script>
</head>
<body>
    <div class="admin-header">
        <div class="header-content">
            <div class="header-left">
                <a href="?module=dashboard" class="logo-admin">
                    <img src="https://www.senior-floors.com/logoSeniorFloors.png?v=6" alt="Senior Floors Logo">
                    <h1><?php echo htmlspecialchars($ADMIN_TITLE); ?></h1>
                </a>
            </div>
            <div class="header-actions">
                <span style="margin-right: 15px; opacity: 0.9;"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <a href="?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <aside class="sidebar">
            <nav>
                <ul class="sidebar-nav">
                    <?php foreach ($modules as $module_key => $module): ?>
                        <?php if ((!isset($module['hidden']) || !$module['hidden']) && module_can_access($module_key, $modules, $_SESSION)): ?>
                        <li>
                            <a href="?module=<?php echo $module_key; ?>" class="<?php echo $current_module === $module_key ? 'active' : ''; ?>">
                                <span><?php echo $module['icon']; ?></span>
                                <span><?php echo htmlspecialchars($module['name']); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="module-content">
                <?php
                // Bloquear acesso se usuário não tem permissão para este módulo
                if (!module_can_access($current_module, $modules, $_SESSION)) {
                    header('Location: ?module=dashboard&error=no_permission');
                    exit;
                }
                // Include the current module
                if (file_exists($module_file)) {
                    include $module_file;
                } else {
                    echo '<div style="text-align: center; padding: 60px 20px; color: #999;">';
                    echo '<h2>Module Not Found</h2>';
                    echo '<p>The module file could not be found.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>
