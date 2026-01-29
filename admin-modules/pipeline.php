<?php
/**
 * Pipeline Kanban - Senior Floors CRM
 * Visualização por estágios: Lead recebido → Contato feito → … → Fechado / Pós-venda
 */

require_once __DIR__ . '/../config/database.php';

$stages = [];
$leadsByStage = [];
$api_base = 'api/pipeline';

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $has_stages = false;
            try {
                $st = $pdo->query("SELECT id, name, slug, order_num, sla_hours FROM pipeline_stages ORDER BY order_num ASC");
                if ($st) {
                    $stages = $st->fetchAll(PDO::FETCH_ASSOC);
                    $has_stages = !empty($stages);
                }
            } catch (Exception $e) {}
            if (!$has_stages && file_exists(__DIR__ . '/../config/pipeline.php')) {
                $config = require __DIR__ . '/../config/pipeline.php';
                $i = 1;
                foreach ($config['stages'] as $slug => $name) {
                    $stages[] = ['id' => $i, 'name' => $name, 'slug' => $slug, 'order_num' => $i, 'sla_hours' => null];
                    $i++;
                }
                $has_stages = !empty($stages);
            }
            $leads = [];
            $has_pipeline_col = false;
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'");
                $has_pipeline_col = $cols && $cols->rowCount() > 0;
            } catch (Exception $e) {}
            if ($has_pipeline_col && !empty($stages)) {
                $stmt = $pdo->prepare("
                    SELECT id, name, email, phone, source, status, priority, created_at, pipeline_stage_id, owner_id, lead_score
                    FROM leads ORDER BY created_at DESC
                ");
                $stmt->execute();
                $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($stages as $s) {
                    $leadsByStage[$s['id']] = array_filter($leads, function($l) use ($s) {
                        return isset($l['pipeline_stage_id']) && (int)$l['pipeline_stage_id'] === (int)$s['id'];
                    });
                }
                $leadsByStage['_none'] = array_filter($leads, function($l) {
                    return empty($l['pipeline_stage_id']);
                });
            }
        }
    } catch (PDOException $e) {
        error_log("Pipeline module: " . $e->getMessage());
    }
}

$stage_ids = array_column($stages, 'id');
?>
<style>
.pipeline-container { padding: 20px; max-width: 100%; overflow-x: auto; }
.pipeline-title { font-size: 22px; font-weight: 700; color: #1a2036; margin-bottom: 20px; }
.pipeline-board { display: flex; gap: 16px; min-height: 400px; align-items: flex-start; }
.pipeline-column { flex: 0 0 280px; min-width: 280px; background: #f1f5f9; border-radius: 12px; padding: 12px; max-height: 80vh; overflow-y: auto; }
.pipeline-column h3 { font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0; }
.pipeline-column.count { font-size: 12px; color: #64748b; margin-left: 8px; }
.pipeline-card { background: white; border-radius: 8px; padding: 12px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); cursor: pointer; border-left: 4px solid #1a2036; }
.pipeline-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.pipeline-card a { text-decoration: none; color: inherit; }
.pipeline-card .name { font-weight: 600; color: #1a2036; margin-bottom: 4px; }
.pipeline-card .meta { font-size: 12px; color: #64748b; }
.pipeline-card .score { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; margin-top: 6px; }
.pipeline-card .score.high { background: #dcfce7; color: #166534; }
.pipeline-card .score.med { background: #fef9c3; color: #854d0e; }
.pipeline-card .score.low { background: #fee2e2; color: #991b1b; }
.pipeline-move { margin-top: 8px; }
.pipeline-move select { width: 100%; padding: 6px 8px; font-size: 12px; border: 1px solid #e2e8f0; border-radius: 6px; }
.pipeline-empty { color: #94a3b8; font-size: 13px; padding: 20px; text-align: center; }
</style>

<div class="pipeline-container">
    <h1 class="pipeline-title">Pipeline Comercial (Kanban)</h1>
    <p style="color: #64748b; margin-bottom: 20px;">Arraste ou use o dropdown em cada card para mover o lead de estágio.</p>

    <?php if (empty($stages)): ?>
        <p style="color: #e53e3e;">Execute a migration do CRM (database/migration-crm-completo.sql) para ver o pipeline. Até lá, use <a href="?module=crm">CRM - Leads</a>.</p>
    <?php else: ?>
    <div class="pipeline-board" id="pipeline-board">
        <?php foreach ($stages as $stage): 
            $leads_in_stage = $leadsByStage[$stage['id']] ?? [];
            $none_leads = $leadsByStage['_none'] ?? [];
            if ((int)$stage['id'] === 1) $leads_in_stage = array_merge($leads_in_stage, $none_leads);
            $count = count($leads_in_stage);
        ?>
        <div class="pipeline-column" data-stage-id="<?php echo (int)$stage['id']; ?>">
            <h3><?php echo htmlspecialchars($stage['name']); ?> <span class="count">(<?php echo $count; ?>)</span></h3>
            <?php foreach ($leads_in_stage as $lead): 
                $score = isset($lead['lead_score']) ? (int)$lead['lead_score'] : 0;
                $scoreClass = $score >= 60 ? 'high' : ($score >= 30 ? 'med' : 'low');
            ?>
            <div class="pipeline-card" data-lead-id="<?php echo (int)$lead['id']; ?>">
                <a href="?module=lead-detail&id=<?php echo (int)$lead['id']; ?>">
                    <div class="name"><?php echo htmlspecialchars($lead['name']); ?></div>
                    <div class="meta"><?php echo htmlspecialchars($lead['email']); ?> · <?php echo htmlspecialchars($lead['phone'] ?? ''); ?></div>
                    <div class="meta"><?php echo htmlspecialchars($lead['source'] ?? ''); ?> · <?php echo date('M j, Y', strtotime($lead['created_at'])); ?></div>
                    <?php if ($score > 0): ?><span class="score <?php echo $scoreClass; ?>">Score <?php echo $score; ?></span><?php endif; ?>
                </a>
                <div class="pipeline-move">
                    <select class="move-select" data-lead-id="<?php echo (int)$lead['id']; ?>" onchange="moveLead(<?php echo (int)$lead['id']; ?>, this.value)">
                        <option value="">Mover para...</option>
                        <?php foreach ($stages as $s): if ((int)$s['id'] !== (int)$stage['id']): ?>
                        <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if ($count === 0): ?>
            <div class="pipeline-empty">Nenhum lead</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function moveLead(leadId, stageId) {
    if (!stageId) return;
    var form = new FormData();
    form.append('lead_id', leadId);
    form.append('stage_id', stageId);
    fetch('api/pipeline/move.php', { method: 'POST', body: form })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) location.reload();
            else alert(data.message || 'Erro ao mover');
        })
        .catch(function() { alert('Erro de rede'); });
}
</script>
