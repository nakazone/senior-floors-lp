<?php
/**
 * Senior Floors CRM - Lead Management System
 * 
 * SECURITY: Change the password below!
 */

session_start();

// ============================================
// CONFIGURATION
// ============================================
$CRM_PASSWORD = 'senior-floors-2024'; // CHANGE THIS PASSWORD!
$CSV_FILE = __DIR__ . '/leads.csv';
$LEADS_PER_PAGE = 25;

// ============================================
// AUTHENTICATION
// ============================================
if (!isset($_SESSION['crm_authenticated'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === $CRM_PASSWORD) {
            $_SESSION['crm_authenticated'] = true;
            header('Location: crm.php');
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
        <title>Senior Floors CRM - Login</title>
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
            <h1>Senior Floors CRM</h1>
            <p>Enter password to access the lead management system</p>
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
    header('Location: crm.php');
    exit;
}

// ============================================
// READ LEADS FROM CSV
// ============================================
$leads = [];
if (file_exists($CSV_FILE)) {
    if (($handle = fopen($CSV_FILE, 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) === count($header)) {
                $lead = array_combine($header, $data);
                $leads[] = $lead;
            }
        }
        fclose($handle);
    }
}

// Reverse to show newest first
$leads = array_reverse($leads);

// ============================================
// FILTERING & SEARCH
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$form_filter = isset($_GET['form']) ? $_GET['form'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$filtered_leads = $leads;

if ($search) {
    $filtered_leads = array_filter($filtered_leads, function($lead) use ($search) {
        return stripos($lead['Name'] ?? '', $search) !== false ||
               stripos($lead['Email'] ?? '', $search) !== false ||
               stripos($lead['Phone'] ?? '', $search) !== false ||
               stripos($lead['ZipCode'] ?? '', $search) !== false ||
               stripos($lead['Message'] ?? '', $search) !== false;
    });
}

if ($form_filter) {
    $filtered_leads = array_filter($filtered_leads, function($lead) use ($form_filter) {
        return ($lead['Form'] ?? '') === $form_filter;
    });
}

if ($date_from) {
    $filtered_leads = array_filter($filtered_leads, function($lead) use ($date_from) {
        return ($lead['Date'] ?? '') >= $date_from;
    });
}

if ($date_to) {
    $filtered_leads = array_filter($filtered_leads, function($lead) use ($date_to) {
        return ($lead['Date'] ?? '') <= $date_to . ' 23:59:59';
    });
}

$filtered_leads = array_values($filtered_leads); // Re-index array

// ============================================
// PAGINATION
// ============================================
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_leads = count($filtered_leads);
$total_pages = ceil($total_leads / $LEADS_PER_PAGE);
$offset = ($page - 1) * $LEADS_PER_PAGE;
$paginated_leads = array_slice($filtered_leads, $offset, $LEADS_PER_PAGE);

// ============================================
// STATISTICS
// ============================================
$total_all_leads = count($leads);
$hero_form_count = count(array_filter($leads, fn($l) => ($l['Form'] ?? '') === 'hero-form'));
$contact_form_count = count(array_filter($leads, fn($l) => ($l['Form'] ?? '') === 'contact-form'));
$today_count = count(array_filter($leads, function($l) {
    return strpos($l['Date'] ?? '', date('Y-m-d')) === 0;
}));
$week_count = count(array_filter($leads, function($l) {
    $lead_date = strtotime($l['Date'] ?? '');
    return $lead_date >= strtotime('-7 days');
}));

// ============================================
// EXPORT CSV
// ============================================
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="senior-floors-leads-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    if (!empty($filtered_leads)) {
        fputcsv($output, array_keys($filtered_leads[0]));
        
        // Write data
        foreach ($filtered_leads as $lead) {
            fputcsv($output, $lead);
        }
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Floors CRM - Lead Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        h1 { font-size: 24px; }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        .btn-primary {
            background: white;
            color: #667eea;
        }
        .btn-primary:hover {
            background: #f0f0f0;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        .leads-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            border-bottom: 2px solid #e0e0e0;
            position: sticky;
            top: 0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-hero {
            background: #e3f2fd;
            color: #1976d2;
        }
        .badge-contact {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .link {
            color: #667eea;
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
        }
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background: #f8f9fa;
        }
        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .filters-grid {
                grid-template-columns: 1fr;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>?? Senior Floors CRM</h1>
            <div class="header-actions">
                <a href="?export=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?>" class="btn btn-primary">?? Export CSV</a>
                <a href="?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Leads</div>
                <div class="stat-value"><?php echo number_format($total_all_leads); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Today</div>
                <div class="stat-value"><?php echo number_format($today_count); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Last 7 Days</div>
                <div class="stat-value"><?php echo number_format($week_count); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Hero Form</div>
                <div class="stat-value"><?php echo number_format($hero_form_count); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Contact Form</div>
                <div class="stat-value"><?php echo number_format($contact_form_count); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Filtered Results</div>
                <div class="stat-value"><?php echo number_format($total_leads); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Name, email, phone, zipcode..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Form Type</label>
                        <select name="form">
                            <option value="">All Forms</option>
                            <option value="hero-form" <?php echo $form_filter === 'hero-form' ? 'selected' : ''; ?>>Hero Form</option>
                            <option value="contact-form" <?php echo $form_filter === 'contact-form' ? 'selected' : ''; ?>>Contact Form</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">?? Apply Filters</button>
                    <a href="crm.php" class="btn btn-secondary">?? Reset</a>
                </div>
            </form>
        </div>

        <!-- Leads Table -->
        <div class="leads-table-container">
            <?php if (empty($paginated_leads)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3>No leads found</h3>
                    <p>Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Form</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Zip Code</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated_leads as $lead): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lead['Date'] ?? ''); ?></td>
                                <td>
                                    <span class="badge <?php echo ($lead['Form'] ?? '') === 'hero-form' ? 'badge-hero' : 'badge-contact'; ?>">
                                        <?php echo htmlspecialchars($lead['Form'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($lead['Name'] ?? ''); ?></strong></td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($lead['Phone'] ?? ''); ?>" class="link">
                                        <?php echo htmlspecialchars($lead['Phone'] ?? ''); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($lead['Email'] ?? ''); ?>" class="link">
                                        <?php echo htmlspecialchars($lead['Email'] ?? ''); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($lead['ZipCode'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(substr($lead['Message'] ?? '', 0, 50)); ?><?php echo strlen($lead['Message'] ?? '') > 50 ? '...' : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>">? Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>">Next ?</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
