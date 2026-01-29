<?php
/**
 * Motor de automações e follow-ups (executar via cron a cada 5–15 min)
 * Ex: * /15 * * * * php /path/to/cron-workflows.php
 */
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('Run from CLI or add ?run=1 with secret');
}

require_once __DIR__ . '/config/database.php';

if (!isDatabaseConfigured()) {
    exit(0);
}

$pdo = getDBConnection();
if (!$pdo) exit(0);

$log = function ($msg) {
    $line = date('Y-m-d H:i:s') . " [workflows] " . $msg . "\n";
    @file_put_contents(__DIR__ . '/workflows.log', $line, FILE_APPEND | LOCK_EX);
};

try {
    // Follow-ups agendados (scheduled_at <= now e sent_at IS NULL)
    $has_scheduled = $pdo->query("SHOW TABLES LIKE 'scheduled_followups'")->rowCount() > 0;
    if ($has_scheduled) {
        $stmt = $pdo->prepare("SELECT * FROM scheduled_followups WHERE sent_at IS NULL AND scheduled_at <= NOW() ORDER BY scheduled_at ASC LIMIT 50");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $pdo->prepare("UPDATE scheduled_followups SET sent_at = NOW() WHERE id = ?")->execute([$row['id']]);
            $log("Follow-up #{$row['id']} marked sent (lead_id={$row['lead_id']}, channel={$row['channel']}). Implementar envio real (email/WhatsApp) conforme config.");
        }
    }

    // Workflows por inatividade (ex: lead sem atividade em X dias)
    $has_workflows = $pdo->query("SHOW TABLES LIKE 'workflows'")->rowCount() > 0;
    if ($has_workflows) {
        $stmt = $pdo->query("SELECT * FROM workflows WHERE is_active = 1 AND trigger_type = 'inactivity'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config = json_decode($row['trigger_config'] ?? '{}', true);
            $days = (int)($config['days'] ?? 7);
            $stage_id = (int)($config['stage_id'] ?? 0);
            $has_stages = $pdo->query("SHOW COLUMNS FROM leads LIKE 'last_activity_at'")->rowCount() > 0;
            if (!$has_stages) continue;
            $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $sel = $pdo->prepare("SELECT id FROM leads WHERE (last_activity_at IS NULL OR last_activity_at < ?) AND pipeline_stage_id = ? AND status NOT IN ('closed_won','closed_lost') LIMIT 20");
            $sel->execute([$since, $stage_id]);
            while ($lead = $sel->fetch(PDO::FETCH_ASSOC)) {
                $log("Inactivity workflow #{$row['id']} triggered for lead_id={$lead['id']}. Implementar ações (actions JSON).");
            }
        }
    }

    $log("Run completed.");
} catch (Exception $e) {
    $log("Error: " . $e->getMessage());
}
