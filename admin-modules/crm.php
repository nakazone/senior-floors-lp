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

// Check if we should force CSV usage (via GET parameter or config)
$force_csv = isset($_GET['force_csv']) && $_GET['force_csv'] === '1';
$use_database = !$force_csv; // Only use database if not forcing CSV

// Try MySQL first (unless CSV is forced)
if ($use_database && isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        if ($pdo) {
            $cols = "id, name as Name, email as Email, phone as Phone, zipcode as ZipCode, message as Message, form_type as Form, source, status, priority, created_at as Date, ip_address";
            if ($pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0) $cols .= ", pipeline_stage_id";
            if ($pdo->query("SHOW COLUMNS FROM leads LIKE 'owner_id'")->rowCount() > 0) $cols .= ", owner_id";
            $stmt = $pdo->query("SELECT $cols FROM leads ORDER BY created_at DESC");
            
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
$stage_filter = isset($_GET['stage']) ? (int)$_GET['stage'] : 0;
$owner_filter = isset($_GET['owner']) ? (int)$_GET['owner'] : 0;
$source_filter = isset($_GET['source']) ? trim($_GET['source']) : '';
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

if ($stage_filter > 0) {
    $filtered_leads = array_filter($filtered_leads, function($lead) use ($stage_filter) {
        return (int)($lead['pipeline_stage_id'] ?? 0) === $stage_filter;
    });
}

if ($owner_filter > 0) {
    $filtered_leads = array_filter($filtered_leads, function($lead) use ($owner_filter) {
        return (int)($lead['owner_id'] ?? 0) === $owner_filter;
    });
}

if ($source_filter !== '') {
    $filtered_leads = array_filter($filtered_leads, function($lead) use ($source_filter) {
        return ($lead['source'] ?? '') === $source_filter;
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

// Listas para filtros (estágio, responsável, fonte)
$pipeline_stages = [];
$list_users = [];
$sources_list = [];
if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo && $pdo->query("SHOW TABLES LIKE 'pipeline_stages'")->rowCount() > 0) {
            $pipeline_stages = $pdo->query("SELECT id, name FROM pipeline_stages ORDER BY order_num ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($pdo && $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0) {
            $list_users = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($pdo && !empty($leads)) {
            $sources_list = array_unique(array_filter(array_column($leads, 'source')));
            sort($sources_list);
        }
    } catch (Exception $e) {}
}

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
    .badge-manual {
        background: #d1fae5;
        color: #047857;
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
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff;
        border-radius: 12px;
        max-width: 480px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .modal-box h2 { margin: 0 0 20px 0; font-size: 20px; color: #1a2036; }
    .modal-box .form-row { margin-bottom: 16px; }
    .modal-box label { display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 6px; }
    .modal-box input, .modal-box select, .modal-box textarea {
        width: 100%; padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; box-sizing: border-box;
    }
    .modal-box input:focus, .modal-box select:focus, .modal-box textarea:focus {
        outline: none; border-color: #1a2036;
    }
    .modal-box .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
    .modal-box .modal-error { color: #dc2626; font-size: 13px; margin-top: 8px; }
    .modal-box .modal-success { color: #16a34a; font-size: 13px; margin-top: 8px; }
</style>

<div class="crm-header">
    <div>
        <h1>CRM - Lead Management</h1>
        <?php if (!empty($data_source)): ?>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #718096;">
                &#128202; Fonte de dados: <strong><?php echo htmlspecialchars($data_source); ?></strong>
                <?php if ($data_source === 'MySQL Database'): ?>
                    <span style="color: #48bb78;">&#10003; Banco de dados ativo</span>
                    | <a href="?module=crm&force_csv=1" style="color: #4299e1; text-decoration: none;">&#128256; Usar CSV</a>
                <?php else: ?>
                    <span style="color: #f59e0b;">&#9888; Usando CSV</span>
                    <?php if (isDatabaseConfigured()): ?>
                        | <a href="?module=crm" style="color: #4299e1; text-decoration: none;">&#128256; Usar Banco de Dados</a>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
$q = ['search'=>$search,'form'=>$form_filter,'stage'=>$stage_filter,'owner'=>$owner_filter,'source'=>$source_filter,'date_from'=>$date_from,'date_to'=>$date_to];
$qs = http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== 0));
?>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <?php if (isDatabaseConfigured()): ?>
        <button type="button" class="btn btn-primary" id="btn-new-lead" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">&#10133; Novo lead (manual)</button>
        <?php endif; ?>
        <a href="?module=crm&export=1&<?php echo $qs; ?>" class="btn btn-primary">&#128229; Export CSV</a>
    </div>
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
                    <option value="manual" <?php echo $form_filter === 'manual' ? 'selected' : ''; ?>>Manual</option>
                </select>
            </div>
            <?php if (!empty($pipeline_stages)): ?>
            <div class="filter-group">
                <label>Estágio (Pipeline)</label>
                <select name="stage">
                    <option value="">Todos</option>
                    <?php foreach ($pipeline_stages as $s): ?>
                    <option value="<?php echo (int)$s['id']; ?>" <?php echo $stage_filter === (int)$s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!empty($list_users)): ?>
            <div class="filter-group">
                <label>Responsável</label>
                <select name="owner">
                    <option value="">Todos</option>
                    <?php foreach ($list_users as $u): ?>
                    <option value="<?php echo (int)$u['id']; ?>" <?php echo $owner_filter === (int)$u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!empty($sources_list)): ?>
            <div class="filter-group">
                <label>Fonte</label>
                <select name="source">
                    <option value="">Todas</option>
                    <?php foreach ($sources_list as $src): ?>
                    <option value="<?php echo htmlspecialchars($src); ?>" <?php echo $source_filter === $src ? 'selected' : ''; ?>><?php echo htmlspecialchars($src); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
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
            <button type="submit" class="btn btn-primary">&#10003; Aplicar filtros</button>
            <a href="?module=crm" class="btn btn-secondary">&#8634; Limpar</a>
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
                    <?php if ($has_stage_col): ?><th>Estágio</th><?php endif; ?>
                    <?php if ($has_owner_col): ?><th>Responsável</th><?php endif; ?>
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
                        <?php if ($has_stage_col): ?>
                        <td><?php echo htmlspecialchars($stage_name_by_id[(int)($lead['pipeline_stage_id'] ?? 0)] ?? '—'); ?></td>
                        <?php endif; ?>
                        <?php if ($has_owner_col): ?>
                        <td><?php echo htmlspecialchars($user_name_by_id[(int)($lead['owner_id'] ?? 0)] ?? '—'); ?></td>
                        <?php endif; ?>
                        <td>
                            <?php $form_type = $lead['Form'] ?? ''; ?>
                            <span class="badge <?php echo $form_type === 'hero-form' ? 'badge-hero' : ($form_type === 'manual' ? 'badge-manual' : 'badge-contact'); ?>">
                                <?php echo htmlspecialchars($form_type ?: '—'); ?>
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
                    <a href="?module=crm&page=<?php echo $page - 1; ?>&<?php echo $qs; ?>">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?module=crm&page=<?php echo $i; ?>&<?php echo $qs; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?module=crm&page=<?php echo $page + 1; ?>&<?php echo $qs; ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal: Novo lead manual -->
<div class="modal-overlay" id="modal-new-lead" role="dialog" aria-labelledby="modal-new-lead-title">
    <div class="modal-box">
        <h2 id="modal-new-lead-title">Novo lead (manual)</h2>
        <p style="margin: 0 0 16px 0; font-size: 13px; color: #718096;">Para leads que chegaram por telefone, indicação, evento ou outra forma.</p>
        <form id="form-new-lead">
            <div class="form-row">
                <label for="manual-name">Nome *</label>
                <input type="text" id="manual-name" name="name" required minlength="2" placeholder="Nome completo">
            </div>
            <div class="form-row">
                <label for="manual-phone">Telefone *</label>
                <input type="tel" id="manual-phone" name="phone" required placeholder="(00) 00000-0000">
            </div>
            <div class="form-row">
                <label for="manual-email">E-mail (opcional)</label>
                <input type="email" id="manual-email" name="email" placeholder="email@exemplo.com">
            </div>
            <div class="form-row">
                <label for="manual-zipcode">CEP (opcional)</label>
                <input type="text" id="manual-zipcode" name="zipcode" placeholder="00000-000">
            </div>
            <div class="form-row">
                <label for="manual-source">Fonte / Como chegou</label>
                <select id="manual-source" name="source">
                    <option value="Manual">Manual</option>
                    <option value="Telefone">Telefone</option>
                    <option value="Indicação">Indicação</option>
                    <option value="Evento">Evento</option>
                    <option value="Site">Site</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <?php if (!empty($list_users)): ?>
            <div class="form-row">
                <label for="manual-owner">Responsável</label>
                <select id="manual-owner" name="owner_id">
                    <option value="">Distribuição automática</option>
                    <?php foreach ($list_users as $u): ?>
                    <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-row">
                <label for="manual-message">Observações</label>
                <textarea id="manual-message" name="message" rows="3" placeholder="Anotações sobre o lead..."></textarea>
            </div>
            <div id="form-new-lead-error" class="modal-error" style="display: none;"></div>
            <div id="form-new-lead-success" class="modal-success" style="display: none;"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="btn-modal-cancel">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btn-modal-submit">Criar lead</button>
            </div>
        </form>
    </div>
</div>
<script>
(function() {
    var modal = document.getElementById('modal-new-lead');
    var btnOpen = document.getElementById('btn-new-lead');
    var btnCancel = document.getElementById('btn-modal-cancel');
    var form = document.getElementById('form-new-lead');
    var errEl = document.getElementById('form-new-lead-error');
    var successEl = document.getElementById('form-new-lead-success');
    if (!modal || !btnOpen || !form) return;
    function showError(msg) {
        errEl.textContent = msg || '';
        errEl.style.display = msg ? 'block' : 'none';
        successEl.style.display = 'none';
    }
    function showSuccess(msg) {
        successEl.textContent = msg || '';
        successEl.style.display = msg ? 'block' : 'none';
        errEl.style.display = 'none';
    }
    btnOpen.addEventListener('click', function() {
        showError('');
        showSuccess('');
        form.reset();
        modal.classList.add('open');
    });
    btnCancel.addEventListener('click', function() {
        modal.classList.remove('open');
    });
    modal.addEventListener('click', function(e) {
        if (e.target === modal) modal.classList.remove('open');
    });
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showError('');
        showSuccess('');
        var submitBtn = document.getElementById('btn-modal-submit');
        submitBtn.disabled = true;
        var fd = new FormData(form);
        fetch('api/leads/create-manual.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json().then(function(j) { return { ok: r.ok, status: r.status, json: j }; }); })
            .then(function(res) {
                if (res.json.success) {
                    showSuccess(res.json.message || 'Lead criado.');
                    var leadId = res.json.data && res.json.data.lead_id;
                    if (leadId) {
                        setTimeout(function() {
                            window.location.href = '?module=lead-detail&id=' + leadId;
                        }, 800);
                    } else {
                        setTimeout(function() { window.location.reload(); }, 1200);
                    }
                } else {
                    var msg = (res.json.errors && res.json.errors.length) ? res.json.errors.join(' ') : (res.json.message || 'Erro ao criar lead.');
                    showError(msg);
                    submitBtn.disabled = false;
                }
            })
            .catch(function() {
                showError('Erro de conexão. Tente novamente.');
                submitBtn.disabled = false;
            });
    });
})();
</script>
