<?php
/**
 * Dashboard Module - Overview statistics
 */

// Read leads for statistics
$CSV_FILE = __DIR__ . '/../leads.csv';
$leads = [];

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

// Get recent leads (last 5)
$recent_leads = array_slice(array_reverse($leads), 0, 5);
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

<h1 style="margin-bottom: 30px;">Dashboard Overview</h1>

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
