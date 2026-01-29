<?php
/**
 * Módulo Orçamentos - Senior Floors CRM
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pipeline.php';

$quotes = [];
$has_quotes = false;
$pipeline_config = file_exists(__DIR__ . '/../config/pipeline.php') ? require __DIR__ . '/../config/pipeline.php' : [];
$service_types = $pipeline_config['service_types'] ?? ['vinyl' => 'Vinyl', 'hardwood' => 'Hardwood', 'tile' => 'Tile', 'carpet' => 'Carpet', 'refinishing' => 'Refinishing', 'laminate' => 'Laminate', 'other' => 'Outro'];
$quote_status_labels = $pipeline_config['quote_status'] ?? ['draft' => 'Rascunho', 'sent' => 'Enviado', 'viewed' => 'Visualizado', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado'];

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $has_quotes = $pdo->query("SHOW TABLES LIKE 'quotes'")->rowCount() > 0;
            if ($has_quotes) {
                $lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : null;
                $sql = "SELECT q.*, l.name as lead_name FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE 1=1";
                $params = [];
                if ($lead_id) { $sql .= " AND q.lead_id = ?"; $params[] = $lead_id; }
                $sql .= " ORDER BY q.created_at DESC";
                $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
                if ($params) $stmt->execute($params);
                $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {
        error_log("Quotes module: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_quote' && $has_quotes && $pdo) {
    $lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
    $floor_type = isset($_POST['floor_type']) ? trim($_POST['floor_type']) : 'other';
    $area_sqft = isset($_POST['area_sqft']) ? str_replace(',', '.', $_POST['area_sqft']) : 0;
    $unit_price = isset($_POST['unit_price']) ? str_replace(',', '.', $_POST['unit_price']) : 0;
    $margin_percent = isset($_POST['margin_percent']) ? (float)str_replace(',', '.', $_POST['margin_percent']) : 15;
    if ($lead_id && $area_sqft > 0 && $unit_price >= 0) {
        $materials = (float)$area_sqft * (float)$unit_price;
        $labor = $materials * ($margin_percent / 100);
        $total = $materials + $labor;
        $stmt = $pdo->prepare("INSERT INTO quotes (lead_id, version, total_amount, labor_amount, materials_amount, margin_percent, status) VALUES (?, 1, ?, ?, ?, ?, 'draft')");
        $stmt->execute([$lead_id, $total, $labor, $materials, $margin_percent]);
        $quote_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO quote_items (quote_id, floor_type, area_sqft, unit_price, total_price) VALUES (?, ?, ?, ?, ?)")
            ->execute([$quote_id, $floor_type, (float)$area_sqft, (float)$unit_price, $materials]);
        header('Location: ?module=quotes');
        exit;
    }
}
?>
<style>
.q-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.q-title { font-size: 22px; font-weight: 700; color: #1a2036; margin-bottom: 20px; }
.q-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.q-card h3 { margin-top: 0; margin-bottom: 16px; color: #1a2036; font-size: 18px; }
.q-table { width: 100%; border-collapse: collapse; }
.q-table th, .q-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.q-table th { background: #1a2036; color: #fff; font-weight: 600; }
.q-table tr:hover { background: #f8fafc; }
.q-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.q-badge.draft { background: #e2e8f0; color: #475569; }
.q-badge.sent { background: #dbeafe; color: #1d4ed8; }
.q-badge.viewed { background: #fef3c7; color: #92400e; }
.q-badge.approved { background: #dcfce7; color: #166534; }
.q-badge.rejected { background: #fee2e2; color: #991b1b; }
.q-form label { display: block; margin-bottom: 6px; font-weight: 600; }
.q-form input, .q-form select { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; margin-bottom: 12px; }
.q-btn { padding: 10px 20px; background: #1a2036; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
</style>

<div class="q-container">
    <h1 class="q-title">Orçamentos</h1>
    <p style="color: #64748b; margin-bottom: 20px;">Crie e gerencie orçamentos por lead/projeto. Cálculo: metragem × preço unitário + margem.</p>

    <?php if (!$has_quotes): ?>
        <p style="color: #64748b;">Execute a migration do CRM para usar orçamentos.</p>
    <?php else: ?>

    <div class="q-card">
        <h3>Novo orçamento</h3>
        <form class="q-form" method="post" style="max-width: 500px;">
            <input type="hidden" name="action" value="create_quote">
            <label>Lead (ID)</label>
            <input type="number" name="lead_id" placeholder="ID do lead" min="1" required>
            <label>Tipo de piso</label>
            <select name="floor_type">
                <?php foreach ($service_types as $k => $v): ?>
                    <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
                <?php endforeach; ?>
            </select>
            <label>Metragem (sqft)</label>
            <input type="text" name="area_sqft" placeholder="Ex: 450" required>
            <label>Preço unitário ($/sqft)</label>
            <input type="text" name="unit_price" placeholder="Ex: 5.50" required>
            <label>Margem (%)</label>
            <input type="text" name="margin_percent" value="15" placeholder="15">
            <button type="submit" class="q-btn">Criar orçamento</button>
        </form>
    </div>

    <div class="q-card">
        <h3>Lista de orçamentos</h3>
        <?php if (empty($quotes)): ?>
            <p style="color: #64748b;">Nenhum orçamento cadastrado.</p>
        <?php else: ?>
        <table class="q-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Lead</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $q): ?>
                <tr>
                    <td><?php echo (int)$q['id']; ?></td>
                    <td><a href="?module=lead-detail&id=<?php echo (int)$q['lead_id']; ?>"><?php echo htmlspecialchars($q['lead_name'] ?? 'Lead #'.$q['lead_id']); ?></a></td>
                    <td>$<?php echo number_format((float)($q['total_amount'] ?? 0), 2); ?></td>
                    <td><span class="q-badge <?php echo htmlspecialchars($q['status'] ?? 'draft'); ?>"><?php echo $quote_status_labels[$q['status'] ?? 'draft'] ?? $q['status']; ?></span></td>
                    <td><?php echo date('d/m/Y', strtotime($q['created_at'])); ?></td>
                    <td><a href="?module=quote-detail&id=<?php echo (int)$q['id']; ?>">Ver</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
