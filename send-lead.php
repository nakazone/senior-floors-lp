<?php
/**
 * Send Lead Handler using PHPMailer (Manual Installation)
 * 
 * This version uses PHPMailer without Composer, perfect for Hostinger
 * 
 * SETUP INSTRUCTIONS:
 * 1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
 * 2. Extract and upload the PHPMailer folder to your server
 * 3. Update SMTP_PASS below with your Google App Password
 * 4. Make sure the PHPMailer folder is in the same directory as this file
 */

// Check if PHPMailer is available
$phpmailer_available = false;
if (file_exists(__DIR__ . '/PHPMailer/Exception.php') && 
    file_exists(__DIR__ . '/PHPMailer/PHPMailer.php') && 
    file_exists(__DIR__ . '/PHPMailer/SMTP.php')) {
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    $phpmailer_available = true;
}

// Set response headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$form_name = isset($_POST['form-name']) ? trim($_POST['form-name']) : 'contact-form';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
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
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitize inputs
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$zipcode = htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// ============================================
// SMTP CONFIGURATION
// ============================================
// IMPORTANT: Replace with your Google App Password
define('SMTP_USER', 'contact@senior-floors.com'); // Your Google Workspace email
define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE'); // Google App Password (16 characters, no spaces)
define('SMTP_FROM_EMAIL', 'contact@senior-floors.com');
define('SMTP_FROM_NAME', 'Senior Floors Website');
define('SMTP_TO_EMAIL', 'leads@senior-floors.com'); // Destination email

// ============================================
// SYSTEM.PHP API CONFIGURATION
// ============================================
// If auto-detection doesn't work, manually set your system.php URL here:
// Example: 'https://yourdomain.com/system.php?api=receive-lead'
// Leave empty to use auto-detection
define('SYSTEM_API_URL', ''); // Set your full URL here if needed

// ============================================
// SAVE TO DATABASE (FASE 1 - MÓDULO 01)
// ============================================
// Try to save to MySQL database first (if configured)
$db_saved = false;
$db_error = null;
$lead_id = null;

// Check if database config exists and is configured
$db_config_file = dirname(__DIR__) . '/config/database.php';
if (file_exists($db_config_file)) {
    require_once $db_config_file;
    
    if (isDatabaseConfigured()) {
        try {
            $pdo = getDBConnection();
            
            if ($pdo) {
                $stmt = $pdo->prepare("
                    INSERT INTO leads (name, email, phone, zipcode, message, source, form_type, status, priority, ip_address)
                    VALUES (:name, :email, :phone, :zipcode, :message, :source, :form_type, 'new', 'medium', :ip_address)
                ");
                
                $source = ($form_name === 'hero-form') ? 'LP-Hero' : 'LP-Contact';
                $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
                
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
            error_log("Database error in send-lead.php: " . $db_error);
        }
    }
}

// ============================================
// SAVE TO CSV (backup/compatibilidade)
// ============================================
// Always save to CSV (backup/compatibilidade)
// Save to public_html/leads.csv (same location CRM reads from)
// IMPORTANT: Handle nested public_html structure (public_html/public_html/)
// Use DOCUMENT_ROOT as it always points to the correct web root
$log_dir = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);

// If DOCUMENT_ROOT is not set, try to find public_html
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    $current_dir = __DIR__;
    // If we're in a nested public_html, go up until we find the web root
    while ($current_dir !== '/' && $current_dir !== '') {
        if (basename($current_dir) === 'public_html' && is_dir($current_dir)) {
            $log_dir = $current_dir;
            break;
        }
        $current_dir = dirname($current_dir);
    }
}

$log_file = $log_dir . '/leads.csv';
$csv_saved = false;

$csv_line = [
    date('Y-m-d H:i:s'),
    $form_name,
    $name,
    $phone,
    $email,
    $zipcode,
    str_replace(["\r\n", "\n", "\r"], ' ', $message)
];

if (!file_exists($log_file)) {
    $header = "Date,Form,Name,Phone,Email,ZipCode,Message\n";
    $header_written = @file_put_contents($log_file, $header, LOCK_EX);
    if ($header_written === false) {
        error_log("Failed to create CSV file: $log_file");
    }
}

$csv_data = '"' . implode('","', array_map(function($field) {
    return str_replace('"', '""', $field);
}, $csv_line)) . "\"\n";

$csv_written = @file_put_contents($log_file, $csv_data, FILE_APPEND | LOCK_EX);
if ($csv_written !== false) {
    $csv_saved = true;
} else {
    error_log("Failed to write to CSV file: $log_file");
}

// Also save to text log
$text_log = date('Y-m-d H:i:s') . " | Form: $form_name | Name: $name | Phone: $phone | Email: $email | Zip: $zipcode\n";
@file_put_contents($log_dir . '/form-submissions.log', $text_log, FILE_APPEND | LOCK_EX);

// ============================================
// TELEGRAM NOTIFICATION (FASE 1 - MÓDULO 02)
// ============================================
// Enviar notificação via Telegram se o lead foi salvo com sucesso
$telegram_sent = false;
if ($db_saved || $csv_saved) {
    $telegram_lib = dirname(__DIR__) . '/libs/telegram-notifier.php';
    if (file_exists($telegram_lib)) {
        require_once $telegram_lib;
        
        $lead_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'zipcode' => $zipcode,
            'message' => $message,
            'source' => ($form_name === 'hero-form') ? 'LP-Hero' : 'LP-Contact',
            'form_type' => $form_name,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown'
        ];
        
        $telegram_result = sendTelegramNotification($lead_data);
        $telegram_sent = $telegram_result['success'];
        
        // Log do resultado
        if ($telegram_sent) {
            $log_entry = date('Y-m-d H:i:s') . " | ✅ Telegram notification sent\n";
        } else {
            $log_entry = date('Y-m-d H:i:s') . " | ⚠️ Telegram notification failed: " . ($telegram_result['error'] ?? 'Unknown error') . "\n";
        }
        @file_put_contents(dirname(__DIR__) . '/telegram-notifications.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Prepare email content
$subject = 'New Lead from Senior Floors Website - ' . ($form_name === 'hero-form' ? 'Hero Form' : 'Contact Form');

$email_body_html = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .field { margin: 15px 0; }
        .label { font-weight: bold; color: #555; }
        .value { margin-top: 5px; padding: 10px; background: #f5f5f5; border-left: 3px solid #007bff; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>New Lead Submission from Senior Floors Website</h2>
        
        <div class='field'>
            <div class='label'>Form Type:</div>
            <div class='value'>" . ($form_name === 'hero-form' ? 'Hero Form' : 'Contact Form') . "</div>
        </div>
        
        <div class='field'>
            <div class='label'>Name:</div>
            <div class='value'>{$name}</div>
        </div>
        
        <div class='field'>
            <div class='label'>Phone:</div>
            <div class='value'>{$phone}</div>
        </div>
        
        <div class='field'>
            <div class='label'>Email:</div>
            <div class='value'>{$email}</div>
        </div>
        
        <div class='field'>
            <div class='label'>Zip Code:</div>
            <div class='value'>{$zipcode}</div>
        </div>";

if (!empty($message)) {
    $email_body_html .= "
        <div class='field'>
            <div class='label'>Message:</div>
            <div class='value'>" . nl2br($message) . "</div>
        </div>";
}

$email_body_html .= "
        <div class='footer'>
            <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>IP Address:</strong> " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown') . "</p>
        </div>
    </div>
</body>
</html>";

$email_body_text = "New lead submission from Senior Floors website\n\n";
$email_body_text .= "Form Type: " . ($form_name === 'hero-form' ? 'Hero Form' : 'Contact Form') . "\n";
$email_body_text .= "Name: $name\n";
$email_body_text .= "Phone: $phone\n";
$email_body_text .= "Email: $email\n";
$email_body_text .= "Zip Code: $zipcode\n";
if (!empty($message)) {
    $email_body_text .= "Message: $message\n";
}
$email_body_text .= "\n---\n";
$email_body_text .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
$email_body_text .= "IP Address: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown') . "\n";

// Send email using PHPMailer (if available)
$mail_sent = false;
$error_message = '';

if ($phpmailer_available) {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Check if App Password is configured
        if (SMTP_PASS === 'YOUR_APP_PASSWORD_HERE' || empty(SMTP_PASS)) {
            throw new \PHPMailer\PHPMailer\Exception('SMTP password not configured. Please set Google App Password in send-lead.php');
        }
    
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Enable verbose debug output (optional - disable in production)
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = function($str, $level) {
    //     file_put_contents(__DIR__ . '/smtp-debug.log', $str, FILE_APPEND);
    // };
    
    // Sender and Recipient
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_TO_EMAIL);
    $mail->addReplyTo($email, $name);
    
    // Email Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $email_body_html;
    $mail->AltBody = $email_body_text;
    
    // Send email
    $mail->send();
    $mail_sent = true;
    
    // Log success (internal email)
    $success_log = date('Y-m-d H:i:s') . " | ✅ Internal email sent successfully using PHPMailer\n";
    $success_log .= "   To: " . SMTP_TO_EMAIL . "\n";
    $success_log .= "   From: " . SMTP_FROM_EMAIL . "\n";
    $success_log .= "   Subject: $subject\n";
    @file_put_contents($log_dir . '/email-status.log', $success_log, FILE_APPEND | LOCK_EX);
    
    // ============================================
    // EMAIL CONFIRMATION TO CLIENT (FASE 1 - MÓDULO 03)
    // ============================================
    // Enviar email de confirmação ao cliente/lead
    if ($db_saved || $csv_saved) {
        $confirmation_template = dirname(__DIR__) . '/templates/email-confirmation.php';
        if (file_exists($confirmation_template)) {
            require_once $confirmation_template;
            
            try {
                $client_mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                
                // SMTP Configuration (same as internal email)
                $client_mail->isSMTP();
                $client_mail->Host = 'smtp.gmail.com';
                $client_mail->SMTPAuth = true;
                $client_mail->Username = SMTP_USER;
                $client_mail->Password = SMTP_PASS;
                $client_mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $client_mail->Port = 587;
                $client_mail->CharSet = 'UTF-8';
                
                // Sender and Recipient (CLIENT EMAIL)
                $client_mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $client_mail->addAddress($email, $name); // Send to the lead
                $client_mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                
                // Email Content (Confirmation)
                $client_mail->isHTML(true);
                $client_mail->Subject = 'Thank You for Contacting Senior Floors';
                $client_mail->Body = getEmailConfirmationTemplate($name, $email, $phone, $zipcode, $message);
                $client_mail->AltBody = getEmailConfirmationText($name, $email, $phone, $zipcode, $message);
                
                // Send confirmation email to client
                $client_mail->send();
                
                // Log success
                $confirmation_log = date('Y-m-d H:i:s') . " | ✅ Confirmation email sent to client\n";
                $confirmation_log .= "   To: $email ($name)\n";
                @file_put_contents($log_dir . '/email-status.log', $confirmation_log, FILE_APPEND | LOCK_EX);
                
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                // Log error but don't fail the form submission
                $confirmation_error = date('Y-m-d H:i:s') . " | ⚠️ Failed to send confirmation email to client\n";
                $confirmation_error .= "   To: $email\n";
                $confirmation_error .= "   Error: " . $e->getMessage() . "\n";
                @file_put_contents($log_dir . '/email-status.log', $confirmation_error, FILE_APPEND | LOCK_EX);
            }
        }
    }
    
} catch (\PHPMailer\PHPMailer\Exception $e) {
    $mail_sent = false;
    $error_message = $mail->ErrorInfo;
    
    // Log error
    $error_log = date('Y-m-d H:i:s') . " | ❌ PHPMailer Error\n";
    $error_log .= "   Error: {$error_message}\n";
    $error_log .= "   To: " . SMTP_TO_EMAIL . "\n";
    $error_log .= "   From: " . SMTP_FROM_EMAIL . "\n";
    @file_put_contents($log_dir . '/email-status.log', $error_log, FILE_APPEND | LOCK_EX);
    }
} else {
    // PHPMailer not available - log this but don't fail
    $error_log = date('Y-m-d H:i:s') . " | ⚠️ PHPMailer not installed. Email not sent. Lead saved to CSV.\n";
    $error_log .= "   To install: Download PHPMailer from https://github.com/PHPMailer/PHPMailer\n";
    $error_log .= "   Extract to: " . __DIR__ . "/PHPMailer/\n";
    @file_put_contents($log_dir . '/email-status.log', $error_log, FILE_APPEND | LOCK_EX);
}

// ============================================
// SEND TO SYSTEM.PHP API ENDPOINT
// ============================================
$system_sent = false;
$system_error = '';

// Get the base URL for system.php
// Use manual URL if configured, otherwise auto-detect
if (!empty(SYSTEM_API_URL)) {
    $system_api_url = SYSTEM_API_URL;
} else {
    // system.php is in public_html, send-lead.php is in public_html/lp
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']); // This will be /lp or /public_html/lp
    
    // Go up one directory level to reach public_html where system.php is located
    // If script is in /lp, go to root. If in /public_html/lp, go to /public_html
    $path_parts = array_filter(explode('/', trim($script_path, '/')));
    if (!empty($path_parts) && end($path_parts) === 'lp') {
        array_pop($path_parts); // Remove 'lp' from path
    }
    $base_path = !empty($path_parts) ? '/' . implode('/', $path_parts) : '';
    $system_api_url = $protocol . $host . $base_path . '/system.php?api=receive-lead';
}

// Log the URL being used for debugging
$debug_log = date('Y-m-d H:i:s') . " | System API URL: $system_api_url | Script Path: " . (isset($script_path) ? $script_path : 'N/A') . " | Base Path: " . (isset($base_path) ? $base_path : 'N/A') . "\n";
@file_put_contents($log_dir . '/system-integration.log', $debug_log, FILE_APPEND | LOCK_EX);

// Send to system.php API endpoint
$system_data = [
    'form-name' => $form_name,
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'zipcode' => $zipcode,
    'message' => $message
];

$ch = curl_init($system_api_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($system_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For Hostinger compatibility
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // For Hostinger compatibility

$system_response = curl_exec($ch);
$system_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($system_http_code >= 200 && $system_http_code < 300) {
    $system_sent = true;
    $system_log = date('Y-m-d H:i:s') . " | ✅ Lead sent to system.php API successfully\n";
    $system_log .= "   Response: " . substr($system_response, 0, 200) . "\n";
    @file_put_contents($log_dir . '/system-integration.log', $system_log, FILE_APPEND | LOCK_EX);
} else {
    $system_error = 'System API failed: HTTP ' . $system_http_code;
    if ($curl_error) {
        $system_error .= ' | cURL Error: ' . $curl_error;
    }
    if ($system_response) {
        $system_error .= ' | Response: ' . substr($system_response, 0, 200);
    }
    $system_log = date('Y-m-d H:i:s') . " | ⚠️ System API: {$system_error}\n";
    $system_log .= "   URL attempted: $system_api_url\n";
    @file_put_contents($log_dir . '/system-integration.log', $system_log, FILE_APPEND | LOCK_EX);
}

// ============================================
// ADDITIONAL EXTERNAL SYSTEM INTEGRATIONS
// ============================================
// Add other system integrations here if needed

// Option: Webhook/API Endpoint
// Uncomment and configure your webhook URL:
/*
$webhook_url = 'https://your-system.com/api/leads';
$webhook_data = [
    'form_type' => $form_name,
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'zipcode' => $zipcode,
    'message' => $message,
    'timestamp' => date('Y-m-d H:i:s'),
    'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown'
];

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhook_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$webhook_response = curl_exec($ch);
$webhook_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($webhook_http_code >= 200 && $webhook_http_code < 300) {
    $system_sent = true;
} else {
    $system_error = 'Webhook failed: HTTP ' . $webhook_http_code;
}
*/

// Option 2: Database Integration (MySQL example)
// Uncomment and configure your database:
/*
try {
    $db_host = 'localhost';
    $db_name = 'your_database';
    $db_user = 'your_username';
    $db_pass = 'your_password';
    
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("INSERT INTO leads (form_type, name, phone, email, zipcode, message, created_at, ip_address) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->execute([$form_name, $name, $phone, $email, $zipcode, $message, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown']);
    
    $system_sent = true;
} catch (PDOException $e) {
    $system_error = 'Database error: ' . $e->getMessage();
}
*/

// Option 3: Third-party CRM API (Example: HubSpot)
// Uncomment and configure:
/*
$hubspot_api_key = 'YOUR_HUBSPOT_API_KEY';
$hubspot_url = 'https://api.hubapi.com/crm/v3/objects/contacts';

$contact_data = [
    'properties' => [
        'firstname' => explode(' ', $name)[0],
        'lastname' => (count(explode(' ', $name)) > 1) ? end(explode(' ', $name)) : '',
        'email' => $email,
        'phone' => $phone,
        'zip' => $zipcode,
        'message' => $message
    ]
];

$ch = curl_init($hubspot_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($contact_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $hubspot_api_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$hubspot_response = curl_exec($ch);
$hubspot_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($hubspot_http_code >= 200 && $hubspot_http_code < 300) {
    $system_sent = true;
} else {
    $system_error = 'HubSpot API failed: HTTP ' . $hubspot_http_code;
}
*/

// Log system integration status
if ($system_sent) {
    $system_log = date('Y-m-d H:i:s') . " | ✅ Lead sent to external system successfully\n";
    @file_put_contents($log_dir . '/system-integration.log', $system_log, FILE_APPEND | LOCK_EX);
} elseif ($system_error) {
    $system_log = date('Y-m-d H:i:s') . " | ⚠️ System integration: {$system_error}\n";
    @file_put_contents($log_dir . '/system-integration.log', $system_log, FILE_APPEND | LOCK_EX);
}

// Always return success to user (lead is saved in CSV and/or DB)
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Thank you! We\'ll contact you within 24 hours.',
    'email_sent' => $mail_sent,
    'system_sent' => $system_sent,
    'database_saved' => $db_saved,
    'csv_saved' => $csv_saved,
    'telegram_sent' => $telegram_sent,
    'lead_id' => $lead_id,
    'timestamp' => date('Y-m-d H:i:s')
]);
exit;
?>
