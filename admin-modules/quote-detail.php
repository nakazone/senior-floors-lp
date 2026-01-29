<?php
/**
 * Detalhe do orçamento + alterar status
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/pipeline.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quote = null;
$pipeline_config = file_exists(__DIR__ . '/../config/pipeline.php') ? require __DIR__ . '/../config/pipeline.php' : [];
$quote_status_labels = $pipeline_config['quote_status'] ?? ['draft' => 'Rascunho', 'sent' => 'Enviado', 'viewed' => 'Visualizado', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado'];

if ($id > 0 && isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT q.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE q.id = ?");
            $stmt->execute([$id]);
            $quote = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($quote) {
                $stmt = $pdo->prepare("SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id");
                $stmt->execute([$id]);
                $quote['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $quote && isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['status'])) {
    $status = $_POST['status'];
    if (in_array($status, ['draft','sent','viewed','approved','rejected'])) {
        $up = "status = ?";
        $params = [$status, $id];
        if ($status === 'sent') $up .= ", sent_at = NOW()";
        if ($status === 'viewed') $up .= ", viewed_at = NOW()";
        if ($status === 'approved') $up .= ", approved_at = NOW()";
        $pdo->prepare("UPDATE quotes SET $up WHERE id = ?")->execute($params);
        header('Location: ?module=quote-detail&id=' . $id);
        exit;
    }
}
?>
<style>
.qd-container { padding: 20px; max-width: 800px; margin: 0 auto; }
.qd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.qd-card h2 { margin-top: 0; margin-bottom: 16px; color: #1a2036; font-size: 18px; }
.qd-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
.qd-label { font-weight: 600; color: #4a5568; width: 140px; flex-shrink: 0; }
.qd-value { color: #333; }
.qd-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.qd-table th, .qd-table td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.qd-table th { background: #f1f5f9; font-weight: 600; }
.qd-btn { padding: 10px 20px; background: #1a2036; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
</style>

<div class="qd-container">
    <?php if (!$quote): ?>
        <p style="color: #e53e3e;">Orçamento não encontrado.</p>
        <a href="?module=quotes">← Voltar</a>
    <?php else: ?>
    <h1 class="qd-title">Orçamento #<?php echo (int)$quote['id']; ?></h1>
    <p><a href="?module=quotes">← Voltar</a> <?php if ($quote['lead_id']): ?>| <a href="?module=lead-detail&id=<?php echo (int)$quote['lead_id']; ?>">Ver lead</a><?php endif; ?></p>

    <div class="qd-card">
        <h2>Resumo</h2>
        <div class="qd-row"><span class="qd-label">Lead</span><span class="qd-value"><?php echo htmlspecialchars($quote['lead_name'] ?? '—'); ?> (<?php echo htmlspecialchars($quote['lead_email'] ?? ''); ?>)</span></div>
        <div class="qd-row"><span class="qd-label">Total</span><span class="qd-value">$<?php echo number_format((float)$quote['total_amount'], 2); ?></span></div>
        <div class="qd-row"><span class="qd-label">Materiais</span><span class="qd-value">$<?php echo number_format((float)$quote['materials_amount'], 2); ?></span></div>
        <div class="qd-row"><span class="qd-label">Mão de obra</span><span class="qd-value">$<?php echo number_format((float)$quote['labor_amount'], 2); ?></span></div>
        <div class="qd-row"><span class="qd-label">Status</span><span class="qd-value"><?php echo $quote_status_labels[$quote['status']] ?? $quote['status']; ?></span></div>

        <form method="post" style="margin-top: 16px;">
            <input type="hidden" name="action" value="update_status">
            <label>Alterar status</label>
            <select name="status">
                <?php foreach ($quote_status_labels as $k => $l): ?>
                    <option value="<?php echo $k; ?>" <?php echo ($quote['status'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="qd-btn">Atualizar</button>
        </form>
    </div>

    <?php if (!empty($quote['items'])): ?>
    <div class="qd-card">
        <h2>Itens</h2>
        <table class="qd-table">
            <thead><tr><th>Tipo</th><th>Metragem (sqft)</th><th>Preço unit.</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($quote['items'] as $it): ?>
                <tr>
                    <td><?php echo htmlspecialchars($it['floor_type'] ?? '—'); ?></td>
                    <td><?php echo number_format((float)($it['area_sqft'] ?? 0), 2); ?></td>
                    <td>$<?php echo number_format((float)($it['unit_price'] ?? 0), 2); ?></td>
                    <td>$<?php echo number_format((float)($it['total_price'] ?? 0), 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
