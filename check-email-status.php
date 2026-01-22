<?php
/**
 * Check Email Status - View email sending logs
 * 
 * SECURITY: Add password protection before uploading
 */

// Simple password protection (change this!)
$password = 'change-this-password-123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

session_start();
if (!isset($_SESSION['email_status_authenticated'])) {
    if (isset($_POST['password'])) {
        if (password_verify($_POST['password'], $password_hash)) {
            $_SESSION['email_status_authenticated'] = true;
        } else {
            $error = 'Invalid password';
        }
    }
    
    if (!isset($_SESSION['email_status_authenticated'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Email Status - Login</title>
            <style>
                body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
                input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
                button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
                .error { color: red; }
            </style>
        </head>
        <body>
            <h2>Email Status - Login</h2>
            <?php if (isset($error)) echo '<p class="error">' . $error . '</p>'; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required>
                <button type="submit">Login</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: check-email-status.php');
    exit;
}

// Read email status log
$log_file = __DIR__ . '/email-status.log';
$log_content = '';

if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    // Reverse to show newest first
    $lines = explode("\n", $log_content);
    $lines = array_reverse($lines);
    $log_content = implode("\n", $lines);
} else {
    $log_content = 'No email status log found yet.';
}

// Count stats
$sent_count = substr_count($log_content, '✅ Email sent successfully');
$failed_count = substr_count($log_content, '❌');
$auth_failed = substr_count($log_content, 'SMTP Authentication FAILED');
$conn_failed = substr_count($log_content, 'SMTP Connection FAILED');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Status - Senior Floors</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { padding: 15px; border-radius: 5px; flex: 1; }
        .stat-success { background: #d4edda; color: #155724; }
        .stat-error { background: #f8d7da; color: #721c24; }
        .stat-warning { background: #fff3cd; color: #856404; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <h1>Email Status - Senior Floors</h1>
    
    <div class="stats">
        <div class="stat-box stat-success">
            <strong>✅ Sent Successfully:</strong> <?php echo $sent_count; ?>
        </div>
        <div class="stat-box stat-error">
            <strong>❌ Failed:</strong> <?php echo $failed_count; ?>
        </div>
        <div class="stat-box stat-warning">
            <strong>🔐 Auth Failed:</strong> <?php echo $auth_failed; ?>
        </div>
        <div class="stat-box stat-warning">
            <strong>🔌 Connection Failed:</strong> <?php echo $conn_failed; ?>
        </div>
    </div>
    
    <p>
        <a href="?refresh=1">Refresh</a> | 
        <a href="?clear=1" onclick="return confirm('Clear log?')">Clear Log</a> | 
        <a href="?logout=1">Logout</a>
    </p>
    
    <?php if (isset($_GET['clear'])): ?>
        <?php
        @file_put_contents($log_file, '');
        header('Location: check-email-status.php');
        exit;
        ?>
    <?php endif; ?>
    
    <h2>Email Status Log</h2>
    <pre><?php echo htmlspecialchars($log_content); ?></pre>
    
    <h2>Common Issues & Solutions</h2>
    <ul>
        <li><strong>✅ Email sent successfully</strong> but not received?
            <ul>
                <li>Check spam/junk folder in Gmail</li>
                <li>Check "All Mail" folder</li>
                <li>Check Gmail filters (Settings > Filters)</li>
                <li>Wait a few minutes - emails can be delayed</li>
                <li>Check if email address <code>leads@senior-floors.com</code> exists</li>
            </ul>
        </li>
        <li><strong>❌ SMTP Authentication FAILED</strong>?
            <ul>
                <li>App Password is incorrect</li>
                <li>2-Step Verification not enabled</li>
                <li>Using regular password instead of App Password</li>
            </ul>
        </li>
        <li><strong>🔌 SMTP Connection FAILED</strong>?
            <ul>
                <li>Server firewall blocking port 587</li>
                <li>Try port 465 with SSL instead</li>
                <li>Network connectivity issues</li>
            </ul>
        </li>
    </ul>
</body>
</html>
