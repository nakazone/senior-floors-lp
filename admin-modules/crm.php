<?php
/**
 * CRM Module - Lead Management System
 * Lê leads do MySQL (prioridade). CSV só quando banco não está configurado ou conexão falha.
 */

// Usar exatamente o mesmo config que system.php (quando incluído por system.php, $SYSTEM_ROOT existe)
if (isset($SYSTEM_ROOT) && is_file($SYSTEM_ROOT . '/config/database.php')) {
    require_once $SYSTEM_ROOT . '/config/database.php';
} else {
    require_once __DIR__ . '/../config/database.php';
}

$LEADS_PER_PAGE = 25;

// Read leads from MySQL (priority). CSV only when DB not configured or connection failed.
$leads = [];
$data_source = '';
$db_error_message = ''; // quando banco está configurado mas falhou (não usar CSV)

// Check if we should force CSV usage (via GET parameter)
$force_csv = isset($_GET['force_csv']) && $_GET['force_csv'] === '1';
$use_database = !$force_csv;

if ($use_database && isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            $db_error_message = 'Falha ao conectar ao MySQL. Verifique DB_HOST, DB_NAME, DB_USER e DB_PASS em config/database.php.';
            error_log("CRM: " . $db_error_message);
        } else {
            // Verificar se a tabela existe antes de query
            $tables = $pdo->query("SHOW TABLES LIKE 'leads'");
            if (!$tables || $tables->rowCount() === 0) {
                $db_error_message = "Tabela 'leads' não existe. No phpMyAdmin, execute database/schema-v3-completo.sql (ou schema.sql).";
                error_log("CRM: " . $db_error_message);
            } else {
                $cols = "id, name as Name, email as Email, phone as Phone, zipcode as ZipCode, message as Message, form_type as Form, source, status, priority, created_at as Date, ip_address";
                $has_followup_col = false;
                try {
                    if ($pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0) $cols .= ", pipeline_stage_id";
                    if ($pdo->query("SHOW COLUMNS FROM leads LIKE 'owner_id'")->rowCount() > 0) $cols .= ", owner_id";
                    if ($pdo->query("SHOW COLUMNS FROM leads LIKE 'next_follow_up_at'")->rowCount() > 0) { $cols .= ", next_follow_up_at"; $has_followup_col = true; }
                } catch (Throwable $e) { /* colunas opcionais */ }
                $stmt = $pdo->query("SELECT $cols FROM leads ORDER BY created_at DESC");
                $db_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $leads = $db_leads;
                $data_source = 'MySQL Database';
                foreach ($leads as &$lead) {
                    $lead['Date'] = $lead['Date'] ?? '';
                }
                unset($lead);
            }
        }
    } catch (Throwable $e) {
        $db_error_message = 'Erro no banco: ' . $e->getMessage();
        error_log("CRM: " . $db_error_message);
    }
}

// CSV só quando banco NÃO está configurado (ou forçado por ?force_csv=1). Se banco configurado mas falhou, não usar CSV.
if (empty($leads) && empty($db_error_message)) {
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
if (!empty($db_error_message)) {
    $data_source = $db_error_message;
}

// Filtering & Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$form_filter = isset($_GET['form']) ? $_GET['form'] : '';
$stage_filter = isset($_GET['stage']) ? (int)$_GET['stage'] : 0;
$owner_filter = isset($_GET['owner']) ? (int)$_GET['owner'] : 0;
$source_filter = isset($_GET['source']) ? trim($_GET['source']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_30min = isset($_GET['filter_30min']) && $_GET['filter_30min'] === '1';
$filter_followup_today = isset($_GET['filter_followup']) && $_GET['filter_followup'] === 'today';
$thirty_min_ago_ts = time() - (30 * 60);
if (!isset($has_followup_col)) $has_followup_col = false;

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

// Filter: only leads that need to be contacted within 30 minutes
if ($filter_30min) {
    $thirty_min_ago_filter = time() - (30 * 60);
    $filtered_leads = array_filter($filtered_leads, function($l) use ($thirty_min_ago_filter) {
        $status = strtolower(trim($l['status'] ?? 'new'));
        $created = strtotime($l['Date'] ?? '0');
        return ($status === 'new' || $status === '') && $created >= $thirty_min_ago_filter;
    });
    $filtered_leads = array_values($filtered_leads);
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
$has_stage_col = !empty($pipeline_stages);
$has_owner_col = !empty($list_users);
$stage_name_by_id = [];
foreach ($pipeline_stages as $s) { $stage_name_by_id[(int)$s['id']] = $s['name']; }
$user_name_by_id = [];
foreach ($list_users as $u) { $user_name_by_id[(int)$u['id']] = $u['name']; }

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

// Leads que precisam ser contactados em até 30 minutos (status new + criados há menos de 30 min)
$contact_within_30_leads = array_filter($leads, function($l) use ($thirty_min_ago_ts) {
    $status = strtolower(trim($l['status'] ?? 'new'));
    $created = strtotime($l['Date'] ?? '0');
    return ($status === 'new' || $status === '') && $created >= $thirty_min_ago_ts;
});
$contact_within_30_count = count($contact_within_30_leads);

// Follow-up hoje: next_follow_up_at definido e <= fim de hoje, status não fechado
$followup_today_leads = [];
if ($has_followup_col) {
    $end_today = strtotime(date('Y-m-d') . ' 23:59:59');
    $closed = ['closed_won', 'closed_lost'];
    $followup_today_leads = array_filter($leads, function($l) use ($end_today, $closed) {
        $next = trim($l['next_follow_up_at'] ?? '');
        if ($next === '') return false;
        $ts = strtotime($next);
        $status = strtolower(trim($l['status'] ?? ''));
        return $ts <= $end_today && !in_array($status, $closed);
    });
}
$followup_today_count = count($followup_today_leads);

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
    .filter-group-checkbox { justify-content: flex-end; }
    .filter-group-checkbox .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #991b1b;
        cursor: pointer;
    }
    .filter-group-checkbox input[type="checkbox"] { width: auto; }
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
    .badge-30min {
        display: inline-block;
        margin-left: 6px;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        background: #fef2f2;
        color: #dc2626;
        text-transform: uppercase;
    }
    tr.lead-row-urgent {
        background: #fef2f2;
        border-left: 3px solid #dc2626;
    }
    tr.lead-row-urgent:hover {
        background: #fee2e2;
    }
    .crm-alert-30min {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        margin-bottom: 20px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        color: #991b1b;
        font-size: 14px;
    }
    .crm-alert-30min a {
        color: #dc2626;
        font-weight: 600;
        text-decoration: underline;
    }
    .crm-alert-30min a:hover {
        color: #b91c1c;
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
                &#128202; Fonte de dados:
                <?php if ($data_source === 'MySQL Database'): ?>
                    <strong><?php echo htmlspecialchars($data_source); ?></strong>
                    <span style="color: #48bb78;">&#10003; Banco de dados ativo</span>
                    | <a href="?module=crm&force_csv=1" style="color: #4299e1; text-decoration: none;">&#128256; Usar CSV</a>
                <?php elseif (!empty($db_error_message)): ?>
                    <span style="color: #dc2626;">&#9888; <?php echo htmlspecialchars($data_source); ?></span>
                    | <a href="?module=crm&force_csv=1" style="color: #4299e1; text-decoration: none;">Ver CSV (backup)</a>
                <?php else: ?>
                    <strong><?php echo htmlspecialchars($data_source); ?></strong>
                    <span style="color: #f59e0b;">&#9888; Usando CSV</span>
                    <?php if (isDatabaseConfigured()): ?>
                        | <a href="?module=crm" style="color: #4299e1; text-decoration: none;">&#128256; Usar Banco de Dados</a>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
            <?php if ($data_source === 'MySQL Database' && !empty($leads)): 
                $most_recent = $leads[0]['Date'] ?? '';
            ?>
            <p style="margin: 2px 0 0 0; font-size: 11px; color: #94a3b8;">&#128337; Último lead no banco: <strong><?php echo htmlspecialchars($most_recent); ?></strong> — se acabou de enviar e não aparece, <a href="?module=crm">limpe os filtros</a> ou confira a resposta do formulário (system_sent / database_saved).</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
$q = ['search'=>$search,'form'=>$form_filter,'stage'=>$stage_filter,'owner'=>$owner_filter,'source'=>$source_filter,'date_from'=>$date_from,'date_to'=>$date_to,'filter_30min'=>$filter_30min ? '1' : '','filter_followup'=>($filter_followup_today ? 'today' : '')];
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
    <div class="stat-card stat-card-urgent" style="border-left-color: #dc2626;">
        <div class="stat-label">Contact within 30 min</div>
        <div class="stat-value" style="color: #dc2626;"><?php echo number_format($contact_within_30_count); ?></div>
        <div class="stat-hint" style="font-size: 11px; color: #718096; margin-top: 4px;">New leads to contact ASAP</div>
    </div>
</div>

<?php if ($contact_within_30_count > 0): 
    $qs_urgent = (strpos($qs, 'filter_30min') !== false) ? $qs : ($qs ? 'filter_30min=1&' . $qs : 'filter_30min=1');
?>
<div class="crm-alert-30min" role="alert">
    <strong>&#9888; <?php echo $contact_within_30_count; ?> lead(s)</strong> precisam ser contactados em até 30 minutos. 
    <a href="?module=crm&<?php echo $qs_urgent; ?>">Ver e contactar agora</a>
</div>
<?php endif; ?>

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
            <div class="filter-group filter-group-checkbox">
                <label>&nbsp;</label>
                <label class="checkbox-label">
                    <input type="checkbox" name="filter_30min" value="1" <?php echo $filter_30min ? 'checked' : ''; ?>>
                    Contato em até 30 min (novos sem contato)
                </label>
            </div>
            <?php if ($has_followup_col): ?>
            <div class="filter-group filter-group-checkbox">
                <label>&nbsp;</label>
                <label class="checkbox-label" style="color: #065f46;">
                    <input type="checkbox" name="filter_followup" value="today" <?php echo $filter_followup_today ? 'checked' : ''; ?>>
                    Follow-up hoje
                </label>
            </div>
            <?php endif; ?>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">&#10003; Aplicar filtros</button>
            <?php if ($contact_within_30_count > 0): ?>
            <a href="?module=crm&filter_30min=1" class="btn btn-primary" style="background: #dc2626;">&#9888; Ver <?php echo $contact_within_30_count; ?> urgente(s)</a>
            <?php endif; ?>
            <?php if ($has_followup_col && $followup_today_count > 0): ?>
            <a href="?module=crm&filter_followup=today" class="btn btn-primary" style="background: #059669;">&#128197; Ver <?php echo $followup_today_count; ?> follow-up(s)</a>
            <?php endif; ?>
            <a href="?module=crm" class="btn btn-secondary">&#8634; Limpar</a>
        </div>
    </form>
</div>

<!-- Leads Table -->
<div class="leads-table-container">
    <?php if (empty($paginated_leads)): ?>
        <div class="empty-state">
            <h3>Nenhum lead na lista</h3>
            <?php if ($total_all_leads > 0): ?>
                <p>Os filtros não retornaram resultados. <a href="?module=crm">Clique aqui para limpar os filtros</a> e ver todos os <?php echo $total_all_leads; ?> leads.</p>
            <?php elseif ($data_source === 'MySQL Database'): ?>
                <p>O banco está ativo mas não há leads. Confira se os formulários estão salvando (resposta com <strong>database_saved: true</strong> no form-test-lp.html) e se é a mesma base (<a href="<?php echo (isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : 'system.php') . '?api=db-check'; ?>" target="_blank">system.php?api=db-check</a>).</p>
            <?php else: ?>
                <p>Nenhum lead encontrado. Ajuste os filtros ou tente mais tarde.</p>
            <?php endif; ?>
            <p><a href="?module=crm" class="btn btn-secondary">&#8634; Limpar filtros</a></p>
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
                <?php foreach ($paginated_leads as $lead): 
                    $lead_status = strtolower(trim($lead['status'] ?? 'new'));
                    $lead_created = strtotime($lead['Date'] ?? '0');
                    $is_urgent_30 = ($lead_status === 'new' || $lead_status === '') && $lead_created >= $thirty_min_ago_ts;
                ?>
                    <tr<?php echo $is_urgent_30 ? ' class="lead-row-urgent"' : ''; ?>>
                        <td>
                            <?php echo htmlspecialchars($lead['Date'] ?? ''); ?>
                            <?php if ($is_urgent_30): ?><span class="badge-30min" title="Contact within 30 minutes">30 min</span><?php endif; ?>
                        </td>
                        <?php if ($has_stage_col): ?>
                        <td><?php echo htmlspecialchars($stage_name_by_id[(int)($lead['pipeline_stage_id'] ?? 0)] ?? '—'); ?></td>
                        <?php endif; ?>
                        <?php if ($has_owner_col): ?>
                        <td><?php echo htmlspecialchars($user_name_by_id[(int)($lead['owner_id'] ?? 0)] ?? '—'); ?></td>
                        <?php endif; ?>
                        <?php if ($has_followup_col): ?>
                        <td><?php 
                            $next_fu = trim($lead['next_follow_up_at'] ?? '');
                            echo $next_fu ? date('d/m/Y H:i', strtotime($next_fu)) : '—'; 
                        ?></td>
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
