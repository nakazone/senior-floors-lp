<?php
/**
 * Contact Form Handler for Senior Floors Landing Page
 * Sends form submissions to leads@senior-floors.com (Google Workspace)
 * 
 * This version uses Google Workspace SMTP
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// Log that handler was accessed
$log_dir = __DIR__;
$access_log = date('Y-m-d H:i:s') . " | 📥 Form handler accessed\n";
$access_log .= "   Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$access_log .= "   Script: " . $_SERVER['SCRIPT_NAME'] . "\n";
$access_log .= "   POST keys: " . implode(', ', array_keys($_POST)) . "\n";
$access_log .= "   POST data: " . print_r($_POST, true) . "\n";
@file_put_contents($log_dir . '/email-status.log', $access_log, FILE_APPEND | LOCK_EX);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $method_error = date('Y-m-d H:i:s') . " | ❌ Wrong method: " . $_SERVER['REQUEST_METHOD'] . " (expected POST)\n";
    @file_put_contents($log_dir . '/email-status.log', $method_error, FILE_APPEND | LOCK_EX);
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

// Log received data
$data_log = date('Y-m-d H:i:s') . " | 📋 Form data received\n";
$data_log .= "   Form: $form_name\n";
$data_log .= "   Name: $name\n";
$data_log .= "   Phone: $phone\n";
$data_log .= "   Email: $email\n";
$data_log .= "   Zipcode: $zipcode\n";
@file_put_contents($log_dir . '/email-status.log', $data_log, FILE_APPEND | LOCK_EX);

// Validate
$errors = [];
$validation_log = date('Y-m-d H:i:s') . " | ✅ Validation check\n";
$validation_log .= "   Name: '" . $name . "' (length: " . strlen($name) . ")\n";
$validation_log .= "   Phone: '" . $phone . "'\n";
$validation_log .= "   Email: '" . $email . "' (valid: " . (filter_var($email, FILTER_VALIDATE_EMAIL) ? 'YES' : 'NO') . ")\n";
$validation_log .= "   Zipcode: '" . $zipcode . "' (valid: " . (preg_match('/^\d{5}(-\d{4})?$/', $zipcode) ? 'YES' : 'NO') . ")\n";

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name is required';
    $validation_log .= "   ❌ Name validation failed\n";
}
if (empty($phone)) {
    $errors[] = 'Phone is required';
    $validation_log .= "   ❌ Phone validation failed\n";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
    $validation_log .= "   ❌ Email validation failed\n";
}
if (empty($zipcode) || !preg_match('/^\d{5}(-\d{4})?$/', $zipcode)) {
    $errors[] = 'Valid zip code is required';
    $validation_log .= "   ❌ Zipcode validation failed\n";
}

if (!empty($errors)) {
    $validation_log .= "   ❌ VALIDATION FAILED - Returning error\n";
    $validation_log .= "   Errors: " . implode(', ', $errors) . "\n";
    @file_put_contents($log_dir . '/email-status.log', $validation_log, FILE_APPEND | LOCK_EX);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// IMPORTANT: Save to CSV BEFORE trying to send email
// This ensures leads are saved even if email fails

$validation_log .= "   ✅ All validations passed\n";
@file_put_contents($log_dir . '/email-status.log', $validation_log, FILE_APPEND | LOCK_EX);

// Sanitize
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$zipcode = htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Email configuration
$to_email = 'leads@senior-floors.com'; // Destination (Google Workspace)
$from_email = 'contact@senior-floors.com'; // Sender (Google Workspace - use this account's App Password)
$from_name = 'Senior Floors Website';

$subject = 'New Lead from Senior Floors Website - ' . ($form_name === 'hero-form' ? 'Hero Form' : 'Contact Form');

$email_body = "New lead submission from Senior Floors website\n\n";
$email_body .= "Form Type: " . ($form_name === 'hero-form' ? 'Hero Form' : 'Contact Form') . "\n";
$email_body .= "Name: $name\n";
$email_body .= "Phone: $phone\n";
$email_body .= "Email: $email\n";
$email_body .= "Zip Code: $zipcode\n";
if (!empty($message)) {
    $email_body .= "Message: $message\n";
}
$email_body .= "\n---\n";
$email_body .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
$email_body .= "IP Address: " . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown') . "\n";

// ============================================
// GOOGLE WORKSPACE SMTP CONFIGURATION
// ============================================
// IMPORTANT: You need to create an "App Password" in Google Workspace
// 1. Go to your Google Account settings
// 2. Security > 2-Step Verification > App passwords
// 3. Create an app password for "Mail"
// 4. Use that password below (NOT your regular Google password)

// Google Workspace SMTP Configuration
// Use contact@senior-floors.com account to send emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contact@senior-floors.com'); // Google Workspace email (sender)
define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE'); // App Password for contact@senior-floors.com
define('SMTP_SECURE', 'tls');

// Always save to CSV first (even if email fails)
$log_dir = __DIR__;

// Test if directory is writable
if (!is_writable($log_dir)) {
    // Try to create log file anyway, but log the issue
    $perm_error = date('Y-m-d H:i:s') . " | ⚠️ WARNING: Directory not writable: $log_dir\n";
    $perm_error .= "   Trying to write anyway...\n";
    @file_put_contents($log_dir . '/email-status.log', $perm_error, FILE_APPEND | LOCK_EX);
}

$log_file = $log_dir . '/leads.csv';
$csv_line = [
    date('Y-m-d H:i:s'),
    $form_name,
    $name,
    $phone,
    $email,
    $zipcode,
    str_replace(["\r\n", "\n", "\r"], ' ', $message)
];

// Log CSV save attempt
$csv_log = date('Y-m-d H:i:s') . " | 💾 Attempting to save to CSV\n";
$csv_log .= "   File: $log_file\n";
$csv_log .= "   File exists: " . (file_exists($log_file) ? 'YES' : 'NO') . "\n";
$csv_log .= "   File writable: " . (is_writable($log_file) ? 'YES' : (file_exists($log_file) ? 'NO' : 'N/A (will create)')) . "\n";
@file_put_contents($log_dir . '/email-status.log', $csv_log, FILE_APPEND | LOCK_EX);

if (!file_exists($log_file)) {
    $header = "Date,Form,Name,Phone,Email,ZipCode,Message\n";
    $header_result = @file_put_contents($log_file, $header, LOCK_EX);
    $csv_log = date('Y-m-d H:i:s') . " | 📝 Created CSV header. Result: " . ($header_result !== false ? 'SUCCESS' : 'FAILED') . "\n";
    @file_put_contents($log_dir . '/email-status.log', $csv_log, FILE_APPEND | LOCK_EX);
}

$csv_data = '"' . implode('","', array_map(function($field) {
    return str_replace('"', '""', $field);
}, $csv_line)) . "\"\n";

$csv_result = @file_put_contents($log_file, $csv_data, FILE_APPEND | LOCK_EX);
$csv_log = date('Y-m-d H:i:s') . " | 💾 CSV save result: " . ($csv_result !== false ? 'SUCCESS (' . $csv_result . ' bytes)' : 'FAILED') . "\n";
$csv_log .= "   Data: " . trim($csv_data) . "\n";
@file_put_contents($log_dir . '/email-status.log', $csv_log, FILE_APPEND | LOCK_EX);

// Also save to text log
$text_log = date('Y-m-d H:i:s') . " | Form: $form_name | Name: $name | Phone: $phone | Email: $email | Zip: $zipcode\n";
@file_put_contents($log_dir . '/form-submissions.log', $text_log, FILE_APPEND | LOCK_EX);

// Log that we're attempting to send email
$attempt_log = date('Y-m-d H:i:s') . " | 📧 Attempting to send email\n";
$attempt_log .= "   From: $from_email\n";
$attempt_log .= "   To: $to_email\n";
$attempt_log .= "   Subject: $subject\n";
$attempt_log .= "   Form: $form_name\n";
@file_put_contents($log_dir . '/email-status.log', $attempt_log, FILE_APPEND | LOCK_EX);

// Try to send email via Google Workspace SMTP
try {
    $mail_sent = sendGoogleSMTPEmail($to_email, $subject, $email_body, $from_email, $from_name, $email);
} catch (Exception $e) {
    $error_log = date('Y-m-d H:i:s') . " | ❌ Exception in sendGoogleSMTPEmail: " . $e->getMessage() . "\n";
    @file_put_contents($log_dir . '/email-status.log', $error_log, FILE_APPEND | LOCK_EX);
    $mail_sent = false;
} catch (Error $e) {
    $error_log = date('Y-m-d H:i:s') . " | ❌ Fatal Error in sendGoogleSMTPEmail: " . $e->getMessage() . "\n";
    @file_put_contents($log_dir . '/email-status.log', $error_log, FILE_APPEND | LOCK_EX);
    $mail_sent = false;
}

// Log detailed email status
$email_status = $mail_sent ? 'Sent' : 'Failed';
$status_log = date('Y-m-d H:i:s') . " | Email Status: $email_status | From: $from_email | To: $to_email | Subject: $subject\n";
if (!$mail_sent) {
    $status_log .= "  ⚠️ Email failed to send - check email-status.log for details\n";
}
@file_put_contents($log_dir . '/email-status.log', $status_log, FILE_APPEND | LOCK_EX);

// Log final status
$final_log = date('Y-m-d H:i:s') . " | ✅ Form processing complete\n";
$final_log .= "   CSV saved: " . (isset($csv_result) && $csv_result !== false ? 'YES' : 'NO') . "\n";
$final_log .= "   Email sent: " . ($mail_sent ? 'YES' : 'NO') . "\n";
$final_log .= "   Returning success response to user\n";
@file_put_contents($log_dir . '/email-status.log', $final_log, FILE_APPEND | LOCK_EX);

// Always return success (lead is saved in CSV)
http_response_code(200);
$response = [
    'success' => true,
    'message' => 'Thank you! We\'ll contact you within 24 hours.',
    'timestamp' => date('Y-m-d H:i:s')
];

// Log the response being sent
$response_log = date('Y-m-d H:i:s') . " | 📤 Sending response: " . json_encode($response) . "\n";
@file_put_contents($log_dir . '/email-status.log', $response_log, FILE_APPEND | LOCK_EX);

echo json_encode($response);
exit;

/**
 * Send email via Google Workspace SMTP
 */
function sendGoogleSMTPEmail($to, $subject, $body, $from_email, $from_name, $reply_to) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $secure = SMTP_SECURE;
    
    // Check if password is configured
    if ($pass === 'YOUR_APP_PASSWORD_HERE' || empty($pass)) {
        // Log that SMTP is not configured
        $config_error = date('Y-m-d H:i:s') . " | ❌ SMTP NOT CONFIGURED\n";
        $config_error .= "   User: $user\n";
        $config_error .= "   Password: " . (empty($pass) ? 'EMPTY' : 'NOT SET') . "\n";
        $config_error .= "   Action: Please set Google App Password in contact-form-handler.php\n";
        @file_put_contents(__DIR__ . '/email-status.log', $config_error, FILE_APPEND | LOCK_EX);
        return false;
    }
    
    // Create socket connection
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    
    if (!$socket) {
        $error_log = date('Y-m-d H:i:s') . " | ❌ SMTP Connection FAILED\n";
        $error_log .= "   Host: $host:$port\n";
        $error_log .= "   Error: $errstr ($errno)\n";
        $error_log .= "   Check: Server firewall, port blocking, or network issues\n";
        @file_put_contents(__DIR__ . '/email-status.log', $error_log, FILE_APPEND | LOCK_EX);
        return false;
    }
    
    // Read server greeting
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '220') {
        fclose($socket);
        return false;
    }
    
    // Send EHLO
    fputs($socket, "EHLO $host\r\n");
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // Start TLS
    if ($secure === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) == '220') {
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            fputs($socket, "EHLO $host\r\n");
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) == ' ') break;
            }
        }
    }
    
    // Authenticate
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($user) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($pass) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '235') {
        $auth_error = date('Y-m-d H:i:s') . " | ❌ SMTP Authentication FAILED\n";
        $auth_error .= "   User: $user\n";
        $auth_error .= "   Response: $response\n";
        $auth_error .= "   Check: App Password is correct and 2FA is enabled\n";
        @file_put_contents(__DIR__ . '/email-status.log', $auth_error, FILE_APPEND | LOCK_EX);
        fclose($socket);
        return false;
    }
    
    // Send email
    fputs($socket, "MAIL FROM: <$from_email>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, "RCPT TO: <$to>\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return false;
    }
    
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '354') {
        fclose($socket);
        return false;
    }
    
    // Email headers and body
    $email_data = "From: $from_name <$from_email>\r\n";
    $email_data .= "To: <$to>\r\n";
    $email_data .= "Reply-To: $reply_to\r\n";
    $email_data .= "Subject: $subject\r\n";
    $email_data .= "MIME-Version: 1.0\r\n";
    $email_data .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email_data .= "\r\n";
    $email_data .= $body;
    $email_data .= "\r\n.\r\n";
    
    fputs($socket, $email_data);
    
    // Read full response (may be multiple lines)
    $response = '';
    $line = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        // SMTP response ends with space after code (e.g., "250 OK" or "250 2.0.0 OK")
        if (substr($line, 3, 1) == ' ') {
            break;
        }
    }
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    $success = substr($response, 0, 3) == '250';
    
    // Detailed logging with full SMTP conversation
    $log_entry = date('Y-m-d H:i:s') . " | ";
    if ($success) {
        $log_entry .= "✅ SMTP accepted email for delivery to $to\n";
        $log_entry .= "   From: $from_email\n";
        $log_entry .= "   To: $to\n";
        $log_entry .= "   Subject: $subject\n";
        $log_entry .= "   Full SMTP Response: " . trim($response) . "\n";
        $log_entry .= "   ⚠️ NOTE: If email not received, check:\n";
        $log_entry .= "      - Gmail filters and spam folder\n";
        $log_entry .= "      - Google Workspace admin settings\n";
        $log_entry .= "      - Email address $to exists and is active\n";
    } else {
        $log_entry .= "❌ Email send FAILED to $to\n";
        $log_entry .= "   From: $from_email\n";
        $log_entry .= "   Subject: $subject\n";
        $log_entry .= "   Full SMTP Response: " . trim($response) . "\n";
        $log_entry .= "   Error: Server rejected the email\n";
    }
    @file_put_contents(__DIR__ . '/email-status.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    return $success;
}
?>
