<?php
/**
 * Senior Floors Admin Panel
 * Main entry point for all admin functions
 */

session_start();

// ============================================
// CONFIGURATION
// ============================================
$ADMIN_PASSWORD = 'senior-floors-2024'; // CHANGE THIS PASSWORD!
$ADMIN_TITLE = 'Senior Floors Admin Panel';

// ============================================
// AUTHENTICATION
// ============================================
if (!isset($_SESSION['admin_authenticated'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === $ADMIN_PASSWORD) {
            $_SESSION['admin_authenticated'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $login_error = 'Invalid password';
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
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                border-color: #667eea;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            <p>Enter password to access the admin panel</p>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Password" required autofocus>
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
    header('Location: admin.php');
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            background: #f8f9fa;
            color: #667eea;
        }
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar-nav a.active:hover {
            opacity: 0.9;
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
