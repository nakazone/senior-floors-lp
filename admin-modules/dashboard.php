<?php
/**
 * Dashboard Module - Visual overview (charts + KPIs)
 * Lê leads do MySQL (prioridade) ou CSV (fallback)
 */

require_once __DIR__ . '/../config/database.php';

$leads = [];
$data_source = '';

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->query("
                SELECT name as Name, email as Email, phone as Phone, zipcode as ZipCode,
                    message as Message, form_type as Form, source, status, created_at as Date
                FROM leads ORDER BY created_at DESC
            ");
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $data_source = 'MySQL Database';
        }
    } catch (PDOException $e) {
        error_log("Dashboard: " . $e->getMessage());
    }
}

if (empty($leads)) {
    $CSV_FILE = __DIR__ . '/../leads.csv';
    if (file_exists($CSV_FILE) && ($handle = fopen($CSV_FILE, 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) === count($header)) $leads[] = array_combine($header, $data);
        }
        fclose($handle);
    }
    $data_source = 'CSV File';
}

$total_leads = count($leads);
$today_count = count(array_filter($leads, fn($l) => strpos($l['Date'] ?? '', date('Y-m-d')) === 0));
$week_count = count(array_filter($leads, fn($l) => (strtotime($l['Date'] ?? '') >= strtotime('-7 days'))));
$month_count = count(array_filter($leads, fn($l) => (strtotime($l['Date'] ?? '') >= strtotime('-30 days'))));

$status_counts = ['new' => 0, 'contacted' => 0, 'qualified' => 0, 'proposal' => 0, 'closed_won' => 0, 'closed_lost' => 0];
foreach ($leads as $lead) {
    $s = $lead['status'] ?? 'new';
    if (isset($status_counts[$s])) $status_counts[$s]++;
}

$source_counts = [];
foreach ($leads as $lead) {
    $src = $lead['source'] ?? 'Unknown';
    $source_counts[$src] = ($source_counts[$src] ?? 0) + 1;
}
arsort($source_counts);

// Leads por dia (últimos 30 dias) para gráfico
$leads_by_day = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $leads_by_day[$d] = 0;
}
foreach ($leads as $lead) {
    $d = substr($lead['Date'] ?? '', 0, 10);
    if (isset($leads_by_day[$d])) $leads_by_day[$d]++;
}

$ticket_medio = null;
$receita_realizada = null;
$performance_vendedor = [];
if ($data_source === 'MySQL Database' && isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo && $pdo->query("SHOW TABLES LIKE 'contracts'")->rowCount() > 0) {
            $r = $pdo->query("SELECT COUNT(*) as n, COALESCE(SUM(closed_amount), 0) as total FROM contracts");
            $row = $r->fetch(PDO::FETCH_ASSOC);
            $receita_realizada = (float)($row['total'] ?? 0);
            $n_contracts = (int)($row['n'] ?? 0);
            $ticket_medio = $n_contracts > 0 ? $receita_realizada / $n_contracts : 0;
        }
        if ($pdo && $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0 && $pdo->query("SHOW COLUMNS FROM leads LIKE 'owner_id'")->rowCount() > 0) {
            $closed_won_col = $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0
                ? "(SELECT COUNT(*) FROM leads l WHERE l.owner_id = u.id AND l.pipeline_stage_id = (SELECT id FROM pipeline_stages WHERE slug = 'closed_won' LIMIT 1))"
                : "(SELECT COUNT(*) FROM leads l WHERE l.owner_id = u.id AND l.status = 'closed_won')";
            $stmt = $pdo->query("
                SELECT u.id, u.name,
                    (SELECT COUNT(*) FROM leads l WHERE l.owner_id = u.id) as total_leads,
                    $closed_won_col as closed_won
                FROM users u WHERE u.is_active = 1 AND u.role IN ('admin','sales_rep','project_manager')
            ");
            if ($stmt) $performance_vendedor = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $receita_projetada = null;
        if ($pdo && $pdo->query("SHOW TABLES LIKE 'quotes'")->rowCount() > 0) {
            $rq = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM quotes WHERE status IN ('sent','viewed','approved')");
            if ($rq) { $row = $rq->fetch(PDO::FETCH_ASSOC); $receita_projetada = (float)($row['total'] ?? 0); }
        }
        $followup_leads = [];
        if ($pdo) {
            $has_last = $pdo->query("SHOW COLUMNS FROM leads LIKE 'last_activity_at'")->rowCount() > 0;
            $has_stage = $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0;
            $has_stages_table = $pdo->query("SHOW TABLES LIKE 'pipeline_stages'")->rowCount() > 0;
            $sql = "SELECT id, name, email, phone, created_at" . ($has_last ? ", last_activity_at" : "") . " FROM leads WHERE 1=1";
            if ($has_stage && $has_stages_table)
                $sql .= " AND (pipeline_stage_id IS NULL OR pipeline_stage_id NOT IN (SELECT id FROM pipeline_stages WHERE slug IN ('closed_won','closed_lost')))";
            $sql .= " ORDER BY " . ($has_last ? "COALESCE(last_activity_at, created_at) ASC, " : "") . "created_at ASC LIMIT 10";
            try {
                $stmt = $pdo->query($sql);
                if ($stmt) $followup_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }
    } catch (Exception $e) {}
}

$status_labels = ['new' => 'Novo', 'contacted' => 'Contatado', 'qualified' => 'Qualificado', 'proposal' => 'Proposta', 'closed_won' => 'Ganho', 'closed_lost' => 'Perdido'];
$status_colors = ['#6366f1', '#8b5cf6', '#06b6d4', '#f59e0b', '#10b981', '#ef4444'];
$chart_status_labels = json_encode(array_values($status_labels));
$chart_status_data = json_encode(array_values($status_counts));
$chart_status_colors = json_encode($status_colors);

$chart_source_labels = json_encode(array_slice(array_keys($source_counts), 0, 8));
$chart_source_data = json_encode(array_slice(array_values($source_counts), 0, 8));
$chart_source_colors = json_encode(['#1a2036', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#64748b']);

$chart_days_labels = json_encode(array_map(function($d) { return date('d/m', strtotime($d)); }, array_keys($leads_by_day)));
$chart_days_data = json_encode(array_values($leads_by_day));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
.dash-clean { padding: 0; }
.dash-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .dash-kpis { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .dash-kpis { grid-template-columns: 1fr; gap: 12px; margin-bottom: 20px; } }
.dash-kpi {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
}
.dash-kpi-value { font-size: 28px; font-weight: 700; color: #1a2036; line-height: 1.2; }
.dash-kpi-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 4px; }
.dash-charts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .dash-charts { grid-template-columns: 1fr; } }
.dash-chart-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
}
.dash-chart-title { font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 16px; }
.dash-chart-wrap { position: relative; height: 220px; }
.dash-chart-full { grid-column: 1 / -1; }
.dash-chart-full .dash-chart-wrap { height: 200px; }
.dash-cta {
    text-align: center;
    margin-top: 24px;
}
.dash-cta a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
}
.dash-cta a:hover { opacity: 0.95; }
.dash-revenue { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
@media (max-width: 600px) { .dash-revenue { grid-template-columns: 1fr; } }
.dash-revenue .dash-kpi { background: linear-gradient(135deg, #1a2036 0%, #252b47 100%); color: #fff; border: none; }
.dash-revenue .dash-kpi-value { color: #fff; }
.dash-revenue .dash-kpi-label { color: rgba(255,255,255,0.8); }
</style>

<div class="dash-clean">
    <?php if (!empty($_GET['error']) && $_GET['error'] === 'no_permission'): ?>
    <div style="background: #fef3c7; color: #92400e; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
        Você não tem permissão para acessar essa página. Entre em contato com o administrador.
    </div>
    <?php endif; ?>
    <div class="dash-kpis">
        <div class="dash-kpi">
            <div class="dash-kpi-value"><?php echo number_format($total_leads); ?></div>
            <div class="dash-kpi-label">Total leads</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-value"><?php echo number_format($today_count); ?></div>
            <div class="dash-kpi-label">Hoje</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-value"><?php echo number_format($week_count); ?></div>
            <div class="dash-kpi-label">Últimos 7 dias</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-value"><?php echo number_format($month_count); ?></div>
            <div class="dash-kpi-label">Últimos 30 dias</div>
        </div>
    </div>

    <div class="dash-charts">
        <div class="dash-chart-card">
            <div class="dash-chart-title">Status dos leads</div>
            <div class="dash-chart-wrap">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
        <div class="dash-chart-card">
            <div class="dash-chart-title">Origem</div>
            <div class="dash-chart-wrap">
                <canvas id="chartSource"></canvas>
            </div>
        </div>
    </div>

    <div class="dash-chart-card dash-chart-full">
        <div class="dash-chart-title">Leads nos últimos 30 dias</div>
        <div class="dash-chart-wrap">
            <canvas id="chartDays"></canvas>
        </div>
    </div>

    <?php if ($ticket_medio !== null && $receita_realizada !== null): ?>
    <div class="dash-revenue">
        <div class="dash-kpi">
            <div class="dash-kpi-value">$<?php echo number_format($ticket_medio, 0); ?></div>
            <div class="dash-kpi-label">Ticket médio</div>
        </div>
        <div class="dash-kpi">
            <div class="dash-kpi-value">$<?php echo number_format($receita_realizada, 0); ?></div>
            <div class="dash-kpi-label">Receita realizada</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($receita_projetada) && $receita_projetada > 0): ?>
    <div class="dash-chart-card" style="margin-bottom: 24px;">
        <div class="dash-chart-title">Receita projetada (propostas enviadas/aprovadas)</div>
        <div class="dash-kpi-value" style="font-size: 24px; color: #10b981;">$<?php echo number_format($receita_projetada, 0); ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($followup_today_leads)): ?>
    <div class="dash-chart-card" style="margin-bottom: 24px; border-left: 4px solid #059669;">
        <div class="dash-chart-title">&#128197; Follow-up hoje</div>
        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
            <?php foreach (array_slice($followup_today_leads, 0, 5) as $fl): ?>
            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
                <a href="?module=lead-detail&id=<?php echo (int)$fl['id']; ?>" style="color: #1a2036; text-decoration: none; font-weight: 500;"><?php echo htmlspecialchars($fl['name'] ?? 'N/A'); ?></a>
                <span style="color: #64748b;"> — <?php echo !empty($fl['next_follow_up_at']) ? date('d/m H:i', strtotime($fl['next_follow_up_at'])) : ''; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <p style="margin: 12px 0 0; font-size: 12px;"><a href="?module=crm&filter_followup=today" style="color: #059669;">Ver follow-ups no CRM →</a></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($followup_leads)): ?>
    <div class="dash-chart-card" style="margin-bottom: 24px;">
        <div class="dash-chart-title">&#9888; Leads em aberto (contatar)</div>
        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
            <?php foreach (array_slice($followup_leads, 0, 5) as $fl): ?>
            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
                <a href="?module=lead-detail&id=<?php echo (int)$fl['id']; ?>" style="color: #1a2036; text-decoration: none; font-weight: 500;"><?php echo htmlspecialchars($fl['name'] ?? 'N/A'); ?></a>
                <span style="color: #64748b;"> — <?php echo htmlspecialchars($fl['phone'] ?? $fl['email'] ?? ''); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <p style="margin: 12px 0 0; font-size: 12px;"><a href="?module=crm" style="color: #1a2036;">Ver todos os leads →</a></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($performance_vendedor)): ?>
    <div class="dash-chart-card" style="margin-bottom: 24px;">
        <div class="dash-chart-title">Performance por vendedor</div>
        <div class="dash-chart-wrap" style="height: 180px;">
            <canvas id="chartVendedor"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <div class="dash-cta">
        <a href="?module=crm">Ver todos os leads →</a>
    </div>
</div>

<script>
(function() {
    const fontFamily = "'Inter', -apple-system, BlinkMacSystemFont, sans-serif";
    const gridColor = '#e2e8f0';
    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = fontFamily;
    Chart.defaults.font.size = 11;

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: <?php echo $chart_status_labels; ?>,
            datasets: [{ data: <?php echo $chart_status_data; ?>, backgroundColor: <?php echo $chart_status_colors; ?>, borderWidth: 0 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: { legend: { position: 'right' } }
        }
    });

    new Chart(document.getElementById('chartSource'), {
        type: 'doughnut',
        data: {
            labels: <?php echo $chart_source_labels; ?>,
            datasets: [{ data: <?php echo $chart_source_data; ?>, backgroundColor: <?php echo $chart_source_colors; ?>, borderWidth: 0 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: { legend: { position: 'right' } }
        }
    });

    new Chart(document.getElementById('chartDays'), {
        type: 'line',
        data: {
            labels: <?php echo $chart_days_labels; ?>,
            datasets: [{
                data: <?php echo $chart_days_data; ?>,
                borderColor: '#1a2036',
                backgroundColor: 'rgba(26, 32, 54, 0.08)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });

    <?php if (!empty($performance_vendedor)): ?>
    new Chart(document.getElementById('chartVendedor'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($performance_vendedor, 'name')); ?>,
            datasets: [
                { label: 'Leads', data: <?php echo json_encode(array_column($performance_vendedor, 'total_leads')); ?>, backgroundColor: '#6366f1' },
                { label: 'Fechados', data: <?php echo json_encode(array_column($performance_vendedor, 'closed_won')); ?>, backgroundColor: '#10b981' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor } },
                y: { grid: { display: false } }
            },
            plugins: { legend: { position: 'top' } }
        }
    });
    <?php endif; ?>
})();
</script>
