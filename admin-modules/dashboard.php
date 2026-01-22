<?php
/**
 * Dashboard Module - Overview statistics
 * Lê leads do MySQL (prioridade) ou CSV (fallback)
 */

require_once __DIR__ . '/../config/database.php';

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
                    name as Name,
                    email as Email,
                    phone as Phone,
                    zipcode as ZipCode,
                    message as Message,
                    form_type as Form,
                    source,
                    status,
                    created_at as Date
                FROM leads 
                ORDER BY created_at DESC
            ");
            
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data_source = 'MySQL Database';
        }
    } catch (PDOException $e) {
        error_log("Dashboard: Database error - " . $e->getMessage());
        // Fall through to CSV
    }
}

// Fallback to CSV if MySQL not available or failed
if (empty($leads)) {
    $CSV_FILE = __DIR__ . '/../leads.csv';
    
    if (file_exists($CSV_FILE)) {
        if (($handle = fopen($CSV_FILE, 'r')) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) === count($header)) {
                    $leads[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }
    }
    
    $data_source = 'CSV File';
}

// Calculate statistics
$total_leads = count($leads);
$hero_form_count = count(array_filter($leads, fn($l) => ($l['Form'] ?? '') === 'hero-form'));
$contact_form_count = count(array_filter($leads, fn($l) => ($l['Form'] ?? '') === 'contact-form'));
$today_count = count(array_filter($leads, function($l) {
    return strpos($l['Date'] ?? '', date('Y-m-d')) === 0;
}));
$week_count = count(array_filter($leads, function($l) {
    $lead_date = strtotime($l['Date'] ?? '');
    return $lead_date >= strtotime('-7 days');
}));
$month_count = count(array_filter($leads, function($l) {
    $lead_date = strtotime($l['Date'] ?? '');
    return $lead_date >= strtotime('-30 days');
}));

// FASE 3 - MÓDULO 06: Métricas de conversão por status
$status_counts = [
    'new' => 0,
    'contacted' => 0,
    'qualified' => 0,
    'proposal' => 0,
    'closed_won' => 0,
    'closed_lost' => 0
];

foreach ($leads as $lead) {
    $status = $lead['status'] ?? 'new';
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}

// Métricas de origem dos leads
$source_counts = [];
foreach ($leads as $lead) {
    $source = $lead['source'] ?? 'Unknown';
    $source_counts[$source] = ($source_counts[$source] ?? 0) + 1;
}
arsort($source_counts);

// Get recent leads (last 5)
if ($data_source === 'MySQL Database') {
    $recent_leads = array_slice($leads, 0, 5); // MySQL já vem ordenado DESC
} else {
    $recent_leads = array_slice(array_reverse($leads), 0, 5); // CSV precisa reverter
}
?>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
        color: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .stat-card.alt {
        background: linear-gradient(135deg, #252b47 0%, #2a3150 100%);
    }
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 8px;
    }
    .stat-value {
        font-size: 36px;
        font-weight: 700;
    }
    .section-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
    }
    .recent-leads {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
    }
    .lead-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 6px;
        margin-bottom: 10px;
    }
    .lead-info h4 {
        margin-bottom: 5px;
        color: #333;
    }
    .lead-info p {
        font-size: 12px;
        color: #666;
    }
    .lead-actions {
        display: flex;
        gap: 10px;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 4px;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    .btn-primary-sm {
        background: #1a2036;
        color: white;
    }
    .btn-primary-sm:hover {
        background: #252b47;
    }
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h1 style="margin: 0;">Dashboard Overview</h1>
    <?php if (!empty($data_source)): ?>
        <p style="margin: 0; font-size: 12px; color: #718096;">
            📊 Fonte: <strong><?php echo htmlspecialchars($data_source); ?></strong>
            <?php if ($data_source === 'MySQL Database'): ?>
                <span style="color: #48bb78;">✅</span>
            <?php else: ?>
                <span style="color: #f59e0b;">⚠️</span>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-label">Total Leads</div>
        <div class="stat-value"><?php echo number_format($total_leads); ?></div>
    </div>
    <div class="stat-card alt">
        <div class="stat-label">Today</div>
        <div class="stat-value"><?php echo number_format($today_count); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Last 7 Days</div>
        <div class="stat-value"><?php echo number_format($week_count); ?></div>
    </div>
    <div class="stat-card alt">
        <div class="stat-label">Last 30 Days</div>
        <div class="stat-value"><?php echo number_format($month_count); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Hero Form</div>
        <div class="stat-value"><?php echo number_format($hero_form_count); ?></div>
    </div>
    <div class="stat-card alt">
        <div class="stat-label">Contact Form</div>
        <div class="stat-value"><?php echo number_format($contact_form_count); ?></div>
    </div>
</div>

<!-- FASE 3 - MÓDULO 06: Métricas de Conversão -->
<div class="metrics-section">
    <h2 style="color: #1a2036; margin-bottom: 20px;">Métricas de Conversão</h2>
    
    <div class="metrics-grid">
        <!-- Conversão por Status -->
        <div class="metric-card">
            <h3>Leads por Status</h3>
            <?php 
            $status_labels = [
                'new' => 'Novo',
                'contacted' => 'Contatado',
                'qualified' => 'Qualificado',
                'proposal' => 'Proposta',
                'closed_won' => 'Fechado - Ganho',
                'closed_lost' => 'Fechado - Perdido'
            ];
            foreach ($status_counts as $status => $count): 
                $percentage = $total_leads > 0 ? round(($count / $total_leads) * 100, 1) : 0;
            ?>
                <div class="metric-item">
                    <span class="metric-label"><?php echo $status_labels[$status] ?? $status; ?></span>
                    <span class="metric-value">
                        <?php echo number_format($count); ?>
                        <span class="metric-percentage">(<?php echo $percentage; ?>%)</span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Origem dos Leads -->
        <div class="metric-card">
            <h3>Origem dos Leads</h3>
            <?php 
            $top_sources = array_slice($source_counts, 0, 10, true);
            foreach ($top_sources as $source => $count): 
                $percentage = $total_leads > 0 ? round(($count / $total_leads) * 100, 1) : 0;
            ?>
                <div class="metric-item">
                    <span class="metric-label"><?php echo htmlspecialchars($source); ?></span>
                    <span class="metric-value">
                        <?php echo number_format($count); ?>
                        <span class="metric-percentage">(<?php echo $percentage; ?>%)</span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="section-title">Recent Leads</div>
<div class="recent-leads">
    <?php if (empty($recent_leads)): ?>
        <div class="empty-state">
            <p>No leads yet. Leads will appear here once forms are submitted.</p>
        </div>
    <?php else: ?>
        <?php foreach ($recent_leads as $lead): ?>
            <div class="lead-item">
                <div class="lead-info">
                    <h4><?php echo htmlspecialchars($lead['Name'] ?? 'N/A'); ?></h4>
                    <p>
                        <?php echo htmlspecialchars($lead['Email'] ?? ''); ?> � 
                        <?php echo htmlspecialchars($lead['Phone'] ?? ''); ?> � 
                        <?php echo htmlspecialchars($lead['Date'] ?? ''); ?>
                    </p>
                </div>
                <div class="lead-actions">
                    <a href="tel:<?php echo htmlspecialchars($lead['Phone'] ?? ''); ?>" class="btn-sm btn-primary-sm">Call</a>
                    <a href="mailto:<?php echo htmlspecialchars($lead['Email'] ?? ''); ?>" class="btn-sm btn-primary-sm">Email</a>
                    <a href="?module=crm" class="btn-sm btn-primary-sm">View All</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- FASE 3 - MÓDULO 06: Métricas de Conversão -->
<div class="metrics-section" style="margin-top: 40px;">
    <h2 style="color: #1a2036; margin-bottom: 20px; font-size: 24px;">Métricas de Conversão</h2>
    
    <div class="metrics-grid">
        <!-- Conversão por Status -->
        <div class="metric-card">
            <h3>Leads por Status</h3>
            <?php 
            $status_labels = [
                'new' => 'Novo',
                'contacted' => 'Contatado',
                'qualified' => 'Qualificado',
                'proposal' => 'Proposta',
                'closed_won' => 'Fechado - Ganho',
                'closed_lost' => 'Fechado - Perdido'
            ];
            foreach ($status_counts as $status => $count): 
                $percentage = $total_leads > 0 ? round(($count / $total_leads) * 100, 1) : 0;
            ?>
                <div class="metric-item">
                    <span class="metric-label"><?php echo $status_labels[$status] ?? $status; ?></span>
                    <span class="metric-value">
                        <?php echo number_format($count); ?>
                        <span class="metric-percentage">(<?php echo $percentage; ?>%)</span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Origem dos Leads -->
        <div class="metric-card">
            <h3>Origem dos Leads</h3>
            <?php 
            $top_sources = array_slice($source_counts, 0, 10, true);
            if (empty($top_sources)): 
            ?>
                <p style="color: #718096; font-style: italic;">Nenhum lead ainda.</p>
            <?php else: ?>
                <?php foreach ($top_sources as $source => $count): 
                    $percentage = $total_leads > 0 ? round(($count / $total_leads) * 100, 1) : 0;
                ?>
                    <div class="metric-item">
                        <span class="metric-label"><?php echo htmlspecialchars($source); ?></span>
                        <span class="metric-value">
                            <?php echo number_format($count); ?>
                            <span class="metric-percentage">(<?php echo $percentage; ?>%)</span>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
