<?php
/**
 * Contact Form Handler with SMTP Support for Senior Floors
 * This version uses SMTP which is more reliable on Hostinger
 * 
 * SETUP INSTRUCTIONS:
 * 1. Get your SMTP settings from Hostinger hPanel > Email Accounts
 * 2. Update the SMTP configuration below
 * 3. Rename this file to contact-form-handler.php (backup the old one first)
 */

header('Content-Type: application/json');

// ============================================
// SMTP CONFIGURATION - UPDATE THESE VALUES
// ============================================
// Get these from Hostinger hPanel > Email Accounts > Your Email Account
define('SMTP_HOST', 'smtp.hostinger.com'); // Usually smtp.hostinger.com or smtp.titan.email
define('SMTP_PORT', 587); // Usually 587 for TLS or 465 for SSL
define('SMTP_USER', 'noreply@senior-floors.com'); // Your email account username
define('SMTP_PASS', 'YOUR_EMAIL_PASSWORD'); // Your email account password
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'

// Email addresses
$to_email = 'leads@senior-floors.com';
$from_email = 'noreply@senior-floors.com';
$from_name = 'Senior Floors Website';

// ============================================
// FORM PROCESSING
// ============================================

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
if (empty($name) || strlen($name) < 2) $errors[] = 'Name is required';
if (empty($phone)) $errors[] = 'Phone number is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (empty($zipcode) || !preg_match('/^\d{5}(-\d{4})?$/', $zipcode)) $errors[] = 'Valid zip code is required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitize
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$zipcode = htmlspecialchars($zipcode, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Prepare email
$subject = 'New Lead from Senior Floors - ' . ($form_name === 'hero-form' ? 'Hero Form' : 'Contact Form');
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
$email_body .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";

// Send via SMTP using socket connection
$mail_sent = sendSMTPEmail($to_email, $subject, $email_body, $from_email, $from_name, $email);

if ($mail_sent) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! We\'ll contact you within 24 hours.'
    ]);
} else {
    // Log submission even if email fails
    $log = date('Y-m-d H:i:s') . " | $form_name | $name | $phone | $email | $zipcode\n";
    @file_put_contents(__DIR__ . '/form-submissions.log', $log, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'There was an issue sending your message. Your information has been saved. Please call us at (720) 751-9813.'
    ]);
}

/**
 * Send email via SMTP
 */
function sendSMTPEmail($to, $subject, $body, $from_email, $from_name, $reply_to) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $secure = SMTP_SECURE;
    
    // Create socket connection
    $context = stream_context_create();
    if ($secure === 'ssl') {
        $socket = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    } else {
        $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    }
    
    if (!$socket) {
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
    $response = fgets($socket, 515);
    
    // Start TLS if needed
    if ($secure === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) == '220') {
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO $host\r\n");
            $response = fgets($socket, 515);
        }
    }
    
    // Authenticate
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    fputs($socket, base64_encode($user) . "\r\n");
    $response = fgets($socket, 515);
    fputs($socket, base64_encode($pass) . "\r\n");
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) != '235') {
        fclose($socket);
        return false;
    }
    
    // Send email
    fputs($socket, "MAIL FROM: <$from_email>\r\n");
    $response = fgets($socket, 515);
    fputs($socket, "RCPT TO: <$to>\r\n");
    $response = fgets($socket, 515);
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    
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
    $response = fgets($socket, 515);
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return substr($response, 0, 3) == '250';
}
?>
