<?php
/**
 * Senior Floors System Admin Panel
 * Main entry point for all admin functions
 */

session_start();

// ============================================
// CONFIGURATION
// ============================================
$ADMIN_TITLE = 'Senior Floors System';

// Load admin users configuration
require_once __DIR__ . '/admin-config.php';

// ============================================
// AUTHENTICATION
// ============================================
if (!isset($_SESSION['admin_authenticated'])) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (verifyAdminUser($username, $password)) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_username'] = $username;
            $user_info = getAdminUser($username);
            $_SESSION['admin_name'] = $user_info['name'] ?? $username;
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($ADMIN_TITLE); ?> - Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
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
                padding: 12px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
                margin-bottom: 15px;
            }
            input:focus {
                outline: none;
                border-color: #1a2036;
            }
            button {
                width: 100%;
                padding: 12px;
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
// API ENDPOINT - Receive Form Submissions
// ============================================
if (isset($_GET['api']) && $_GET['api'] === 'receive-lead' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    
    // Get form data
    $form_name = isset($_POST['form-name']) ? trim($_POST['form-name']) : 'contact-form';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $zipcode = isset($_POST['zipcode']) ? trim($_POST['zipcode']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
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
    
    // Note: CSV is already saved by send-lead.php, so we just process/notify here
    // This endpoint receives the data and can trigger additional processing
    
    // Log to system API log
    $log_file = __DIR__ . '/system-api.log';
    $log_entry = date('Y-m-d H:i:s') . " | ✅ API: Lead received and processed | Form: $form_name | Name: $name | Email: $email | Phone: $phone\n";
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // You can add additional processing here:
    // - Send notifications
    // - Trigger webhooks
    // - Update database
    // - Send to other systems
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Lead received and processed by system',
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => [
            'form_type' => $form_name,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'zipcode' => $zipcode
        ]
    ]);
    exit;
}

// ============================================
// MODULE SYSTEM
// ============================================
$modules = [
    'dashboard' => [
        'name' => 'Dashboard',
        'icon' => '??',
        'file' => 'admin-modules/dashboard.php',
        'default' => true
    ],
    'crm' => [
        'name' => 'CRM - Leads',
        'icon' => '??',
        'file' => 'admin-modules/crm.php'
    ],
    'lead-detail' => [
        'name' => 'Lead Detail',
        'icon' => '??',
        'file' => 'admin-modules/lead-detail.php',
        'hidden' => true // Não aparece no menu, só acessível via URL
    ],
    'settings' => [
        'name' => 'Settings',
        'icon' => '??',
        'file' => 'admin-modules/settings.php'
    ]
];

// Get current module
$current_module = isset($_GET['module']) ? $_GET['module'] : 'dashboard';
if (!isset($modules[$current_module])) {
    $current_module = 'dashboard';
}

// Check if module file exists
$module_file = __DIR__ . '/' . $modules[$current_module]['file'];
if (!file_exists($module_file)) {
    $current_module = 'dashboard';
    $module_file = __DIR__ . '/' . $modules[$current_module]['file'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ADMIN_TITLE); ?> - <?php echo htmlspecialchars($modules[$current_module]['name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f7f8fc;
            color: #1a2036;
        }
        .admin-header {
            background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
            color: white;
            padding: 15px 20px;
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
        h1 { font-size: 20px; font-weight: 600; }
        .header-actions {
            display: flex;
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
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                position: static;
            }
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                gap: 10px;
            }
            .sidebar-nav li {
                margin-bottom: 0;
            }
            .sidebar-nav a {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="header-content">
            <div class="header-left">
                <h1><?php echo htmlspecialchars($ADMIN_TITLE); ?></h1>
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
                        <li>
                            <a href="?module=<?php echo $module_key; ?>" class="<?php echo $current_module === $module_key ? 'active' : ''; ?>">
                                <span><?php echo $module['icon']; ?></span>
                                <span><?php echo htmlspecialchars($module['name']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="module-content">
                <?php
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
