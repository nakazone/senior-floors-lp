<?php
/**
 * CRM Module - Lead Management System
 * Lê leads do MySQL (prioridade) ou CSV (fallback)
 */

require_once __DIR__ . '/../config/database.php';

$LEADS_PER_PAGE = 25;

// Read leads from MySQL (if configured) or CSV (fallback)
$leads = [];
$data_source = '';

// Try MySQL first
if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        if ($pdo) {
            $stmt = $pdo->query("
                SELECT 
                    id,
                    name as Name,
                    email as Email,
                    phone as Phone,
                    zipcode as ZipCode,
                    message as Message,
                    form_type as Form,
                    source,
                    status,
                    priority,
                    created_at as Date,
                    ip_address
                FROM leads 
                ORDER BY created_at DESC
            ");
            
            $db_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Only use database if it has leads
            if (!empty($db_leads)) {
                $leads = $db_leads;
                
                // Format date to match CSV format
                foreach ($leads as &$lead) {
                    $lead['Date'] = $lead['Date'];
                }
                
                $data_source = 'MySQL Database';
            } else {
                // Database is configured but empty, fall through to CSV
                error_log("CRM: Database configured but empty, using CSV fallback");
            }
        }
    } catch (PDOException $e) {
        error_log("CRM: Database error - " . $e->getMessage());
        // Fall through to CSV
    }
}

// Fallback to CSV if MySQL not available or failed
if (empty($leads)) {
    // Use same logic as send-lead.php to ensure same path
    // CRM is in public_html/admin-modules/, so dirname(__DIR__) should be public_html/
    $csv_dir = null;
    
    // Try DOCUMENT_ROOT first (most reliable)
    if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $csv_dir = $_SERVER['DOCUMENT_ROOT'];
    } else {
        // Fallback: use parent directory (should be public_html)
        $csv_dir = dirname(__DIR__);
    }
    
    $CSV_FILE = $csv_dir . '/leads.csv';
    
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
    
    // Reverse to show newest first (CSV)
    $leads = array_reverse($leads);
    $data_source = 'CSV File';
}

// Filtering & Search
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

$filtered_leads = array_values($filtered_leads);

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_leads = count($filtered_leads);
$total_pages = ceil($total_leads / $LEADS_PER_PAGE);
$offset = ($page - 1) * $LEADS_PER_PAGE;
$paginated_leads = array_slice($filtered_leads, $offset, $LEADS_PER_PAGE);

// Statistics
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

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="senior-floors-leads-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($filtered_leads)) {
        fputcsv($output, array_keys($filtered_leads[0]));
        foreach ($filtered_leads as $lead) {
            fputcsv($output, $lead);
        }
    }
    
    fclose($output);
    exit;
}
?>
<style>
    .crm-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .crm-header h1 {
        margin: 0;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: #f7f8fc;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #1a2036;
    }
    .stat-label {
        color: #718096;
        font-size: 12px;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-weight: 600;
    }
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #1a2036;
    }
    .filters {
        background: #f7f8fc;
        padding: 20px;
        border-radius: 8px;
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
        color: #4a5568;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .filter-group input,
    .filter-group select {
        padding: 8px;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
    }
    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #1a2036;
    }
    .filter-actions {
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
        background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
        color: white;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #252b47 0%, #2a3150 100%);
    }
    .btn-secondary {
        background: #718096;
        color: white;
    }
    .btn-secondary:hover {
        background: #4a5568;
    }
    .leads-table-container {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th {
        background: #f7f8fc;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
        color: #4a5568;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
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
        color: #1a2036;
        text-decoration: none;
    }
    .link:hover {
        color: #252b47;
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
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        text-decoration: none;
        color: #1a2036;
    }
    .pagination a:hover {
        background: #f0f2f8;
    }
    .pagination .current {
        background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
        color: white;
        border-color: #1a2036;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
</style>

<div class="crm-header">
    <div>
        <h1>CRM - Lead Management</h1>
        <?php if (!empty($data_source)): ?>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #718096;">
                📊 Fonte de dados: <strong><?php echo htmlspecialchars($data_source); ?></strong>
                <?php if ($data_source === 'MySQL Database'): ?>
                    <span style="color: #48bb78;">✅ Banco de dados ativo</span>
                <?php else: ?>
                    <span style="color: #f59e0b;">⚠️ Usando CSV (banco não configurado)</span>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <a href="?module=crm&export=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?>" class="btn btn-primary">?? Export CSV</a>
</div>

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
        <input type="hidden" name="module" value="crm">
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
            <a href="?module=crm" class="btn btn-secondary">?? Reset</a>
        </div>
    </form>
</div>

<!-- Leads Table -->
<div class="leads-table-container">
    <?php if (empty($paginated_leads)): ?>
        <div class="empty-state">
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
                    <th>Actions</th>
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
                        <td>
                            <?php 
                            // Se tiver ID (MySQL), criar link para detalhe
                            $lead_id = $lead['id'] ?? null;
                            if ($lead_id && $data_source === 'MySQL Database'): 
                            ?>
                                <a href="?module=lead-detail&id=<?php echo $lead_id; ?>" class="link" style="font-weight: 600;">
                                    <?php echo htmlspecialchars($lead['Name'] ?? ''); ?>
                                </a>
                            <?php else: ?>
                                <strong><?php echo htmlspecialchars($lead['Name'] ?? ''); ?></strong>
                            <?php endif; ?>
                        </td>
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
                        <td>
                            <?php if ($lead_id && $data_source === 'MySQL Database'): ?>
                                <a href="?module=lead-detail&id=<?php echo $lead_id; ?>" class="link" style="font-size: 12px;">Ver Detalhes</a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?module=crm&page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>">? Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?module=crm&page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?module=crm&page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $form_filter ? '&form=' . urlencode($form_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?>">Next ?</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
