<?php
/**
 * Quote Detail - Builder + Preview + Activity (Invoice2go-style)
 * Editable when status = draft; Send generates public link; Activity timeline
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/quotes-helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quote = null;
$quote_config = file_exists(__DIR__ . '/../config/quotes.php') ? require __DIR__ . '/../config/quotes.php' : [];
$quote_status_labels = isset($quote_config['quote_status']) ? $quote_config['quote_status'] : ['draft'=>'Rascunho','sent'=>'Enviado','viewed'=>'Visualizado','accepted'=>'Aceito','declined'=>'Recusado','expired'=>'Expirado','approved'=>'Aprovado','rejected'=>'Rejeitado'];
$item_types = isset($quote_config['item_type']) ? $quote_config['item_type'] : ['material'=>'Material','labor'=>'Mão de obra','service'=>'Serviço'];
$discount_types = isset($quote_config['discount_type']) ? $quote_config['discount_type'] : ['percentage'=>'Percentual (%)','fixed'=>'Valor fixo'];

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
                $has_log = $pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0;
                $quote['activity_log'] = [];
                if ($has_log) {
                    $stmt = $pdo->prepare("SELECT * FROM quote_activity_log WHERE quote_id = ? ORDER BY created_at DESC LIMIT 30");
                    $stmt->execute([$id]);
                    $quote['activity_log'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
    } catch (PDOException $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $quote && isset($_POST['action'])) {
    $action = $_POST['action'];
    $pdo = getDBConnection();
    if ($action === 'update_status' && isset($_POST['status'])) {
        $status = $_POST['status'];
        if (in_array($status, ['draft','sent','viewed','approved','rejected','accepted','declined','expired'])) {
            $pdo->prepare("UPDATE quotes SET status = ? WHERE id = ?")->execute([$status, $id]);
            if ($status === 'sent') $pdo->prepare("UPDATE quotes SET sent_at = NOW() WHERE id = ?")->execute([$id]);
            if ($status === 'viewed') $pdo->prepare("UPDATE quotes SET viewed_at = NOW() WHERE id = ?")->execute([$id]);
            if ($status === 'accepted' || $status === 'approved') $pdo->prepare("UPDATE quotes SET approved_at = NOW() WHERE id = ?")->execute([$id]);
            header('Location: ?module=quote-detail&id=' . $id);
            exit;
        }
    }
    if ($action === 'send_quote' && $quote['status'] === 'draft' && $pdo->query("SHOW COLUMNS FROM quotes LIKE 'public_token'")->rowCount() > 0) {
        $token = $quote['public_token'] ?: quotes_generate_public_token();
        $pdo->prepare("UPDATE quotes SET status = 'sent', sent_at = NOW(), public_token = COALESCE(NULLIF(public_token,''), ?) WHERE id = ?")->execute([$token, $id]);
        if ($pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0) {
            quotes_log_activity($pdo, $id, 'sent', null, 'user', ['token_set' => true]);
        }
        header('Location: ?module=quote-detail&id=' . $id . '&sent=1');
        exit;
    }
    if ($action === 'update_quote' && quotes_can_edit($quote['status']) && $pdo) {
        $issue_date = isset($_POST['issue_date']) ? trim($_POST['issue_date']) : null;
        $expiration_date = isset($_POST['expiration_date']) ? trim($_POST['expiration_date']) : null;
        $discount_type = isset($_POST['discount_type']) && $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percentage';
        $discount_value = isset($_POST['discount_value']) ? (float)str_replace(',', '.', $_POST['discount_value']) : 0;
        $tax_total = isset($_POST['tax_total']) ? (float)str_replace(',', '.', $_POST['tax_total']) : 0;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
        $internal_notes = isset($_POST['internal_notes']) ? trim($_POST['internal_notes']) : null;
        $currency = isset($_POST['currency']) ? trim($_POST['currency']) : 'USD';
        $has_new = $pdo->query("SHOW COLUMNS FROM quotes LIKE 'quote_number'")->rowCount() > 0;
        if ($has_new) {
            $pdo->prepare("UPDATE quotes SET issue_date = ?, expiration_date = ?, discount_type = ?, discount_value = ?, tax_total = ?, notes = ?, internal_notes = ?, currency = ? WHERE id = ?")
                ->execute([$issue_date ?: null, $expiration_date ?: null, $discount_type, $discount_value, $tax_total, $notes, $internal_notes, $currency, $id]);
        }
        header('Location: ?module=quote-detail&id=' . $id);
        exit;
    }
}

$public_link = null;
if ($quote && !empty($quote['public_token'])) {
    $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $public_link = $base . '/quote-view.php?token=' . urlencode($quote['public_token']);
}
$can_edit = $quote && quotes_can_edit($quote['status']);
?>
<style>
.qd-container { padding: 20px; max-width: 1000px; margin: 0 auto; }
.qd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.qd-card h2 { margin-top: 0; margin-bottom: 16px; color: #1a2036; font-size: 18px; }
.qd-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
.qd-label { font-weight: 600; color: #4a5568; width: 140px; flex-shrink: 0; }
.qd-value { color: #333; }
.qd-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.qd-table th, .qd-table td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.qd-table th { background: #f1f5f9; font-weight: 600; }
.qd-btn { padding: 10px 20px; background: #1a2036; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
.qd-btn.success { background: #059669; }
.qd-btn.small { padding: 6px 12px; font-size: 13px; }
.qd-message { padding: 12px; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 16px; }
.qd-timeline { list-style: none; padding: 0; margin: 0; }
.qd-timeline li { padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
.qd-timeline .time { color: #64748b; font-size: 12px; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
</style>

<div class="qd-container">
    <?php if (!$quote): ?>
        <p style="color: #e53e3e;">Orçamento não encontrado.</p>
        <a href="?module=quotes" class="qd-btn">← Voltar</a>
    <?php else: ?>
    <h1 style="margin-bottom: 8px;">Orçamento <?php echo htmlspecialchars($quote['quote_number'] ?? '#' . $quote['id']); ?></h1>
    <p style="margin-bottom: 20px;"><a href="?module=quotes" class="qd-btn small">← Voltar</a>
        <?php if ($quote['lead_id']): ?> | <a href="?module=lead-detail&id=<?php echo (int)$quote['lead_id']; ?>">Ver lead</a><?php endif; ?></p>

    <?php if (!empty($_GET['sent'])): ?>
        <div class="qd-message">Orçamento enviado. Link para o cliente: <a href="<?php echo htmlspecialchars($public_link ?? '#'); ?>" target="_blank" rel="noopener">Abrir link</a> — Copie e envie por e-mail.</div>
    <?php endif; ?>

    <?php if ($public_link): ?>
        <div class="qd-card">
            <h2>Link para o cliente</h2>
            <p style="word-break: break-all; font-size: 14px;"><a href="<?php echo htmlspecialchars($public_link); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($public_link); ?></a></p>
            <p style="font-size: 13px; color: #64748b;">O cliente pode visualizar, aceitar ou recusar o orçamento por este link.</p>
        </div>
    <?php endif; ?>

    <div class="qd-card">
        <h2>Resumo</h2>
        <div class="qd-row"><span class="qd-label">Cliente / Lead</span><span class="qd-value"><?php echo htmlspecialchars($quote['lead_name'] ?? '—'); ?> (<?php echo htmlspecialchars($quote['lead_email'] ?? ''); ?>)</span></div>
        <div class="qd-row"><span class="qd-label">Total</span><span class="qd-value">R$ <?php echo number_format((float)($quote['total_amount'] ?? 0), 2, ',', '.'); ?></span></div>
        <div class="qd-row"><span class="qd-label">Status</span><span class="qd-value"><?php echo $quote_status_labels[$quote['status'] ?? 'draft'] ?? $quote['status']; ?></span></div>
        <?php if ($can_edit && $quote['status'] === 'draft'): ?>
        <form method="post" style="margin-top: 16px;">
            <input type="hidden" name="action" value="send_quote">
            <button type="submit" class="qd-btn success">Enviar orçamento ao cliente (gerar link)</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($can_edit && $pdo->query("SHOW COLUMNS FROM quotes LIKE 'issue_date'")->rowCount() > 0): ?>
    <div class="qd-card">
        <h2>Editar orçamento (datas, desconto, impostos, notas)</h2>
        <form method="post">
            <input type="hidden" name="action" value="update_quote">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Data de emissão</label>
                    <input type="date" name="issue_date" value="<?php echo htmlspecialchars($quote['issue_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="form-group">
                    <label>Data de validade</label>
                    <input type="date" name="expiration_date" value="<?php echo htmlspecialchars($quote['expiration_date'] ?? ''); ?>">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Tipo de desconto</label>
                    <select name="discount_type">
                        <?php foreach ($discount_types as $k => $l): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($quote['discount_type'] ?? 'percentage') === $k ? 'selected' : ''; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Valor do desconto</label>
                    <input type="text" name="discount_value" value="<?php echo htmlspecialchars($quote['discount_value'] ?? '0'); ?>">
                </div>
                <div class="form-group">
                    <label>Impostos (total)</label>
                    <input type="text" name="tax_total" value="<?php echo htmlspecialchars($quote['tax_total'] ?? '0'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Notas (visível ao cliente)</label>
                <textarea name="notes" rows="2"><?php echo htmlspecialchars($quote['notes'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Notas internas</label>
                <textarea name="internal_notes" rows="2"><?php echo htmlspecialchars($quote['internal_notes'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Moeda</label>
                <input type="text" name="currency" value="<?php echo htmlspecialchars($quote['currency'] ?? 'USD'); ?>" style="max-width: 80px;">
            </div>
            <button type="submit" class="qd-btn">Salvar alterações</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (!empty($quote['items'])): ?>
    <div class="qd-card">
        <h2>Itens do orçamento</h2>
        <table class="qd-table">
            <thead>
            <tr>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Qtd</th>
                <th>Preço unit.</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($quote['items'] as $it): ?>
            <tr>
                <td><?php echo $item_types[$it['type'] ?? 'material'] ?? ($it['type'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($it['name'] ?? $it['floor_type'] ?? '—'); ?></td>
                <td><?php echo number_format((float)($it['quantity'] ?? $it['area_sqft'] ?? 0), 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format((float)($it['unit_price'] ?? $it['unit_price'] ?? 0), 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format((float)($it['total'] ?? $it['total_price'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: 700; background: #f8fafc;">
                <td colspan="4" style="text-align: right;">Total</td>
                <td>R$ <?php echo number_format((float)($quote['total_amount'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($quote['activity_log'])): ?>
    <div class="qd-card">
        <h2>Atividade</h2>
        <ul class="qd-timeline">
            <?php foreach ($quote['activity_log'] as $log): ?>
            <li>
                <span class="time"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></span>
                — <?php echo htmlspecialchars($log['action']); ?> (<?php echo htmlspecialchars($log['performed_by_type'] ?? 'user'); ?>)
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
