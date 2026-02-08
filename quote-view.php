<?php
/**
 * Public Quote View - Client sees quote and can Accept or Decline
 * URL: quote-view.php?token=xxx
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/libs/quotes-helper.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$quote = null;
$company_name = 'Senior Floors';
$company_logo = 'https://www.senior-floors.com/logoSeniorFloors.png?v=6';

if ($token && isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo && $pdo->query("SHOW COLUMNS FROM quotes LIKE 'public_token'")->rowCount() > 0) {
            $stmt = $pdo->prepare("SELECT q.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE q.public_token = ?");
            $stmt->execute([$token]);
            $quote = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$quote && $pdo->query("SHOW TABLES LIKE 'customers'")->rowCount() > 0) {
                $stmt = $pdo->prepare("SELECT q.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone FROM quotes q LEFT JOIN customers c ON c.id = q.customer_id WHERE q.public_token = ?");
                $stmt->execute([$token]);
                $quote = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($quote) {
                    $quote['lead_name'] = $quote['customer_name'] ?? null;
                    $quote['lead_email'] = $quote['customer_email'] ?? null;
                    $quote['lead_phone'] = $quote['customer_phone'] ?? null;
                }
            }
            if ($quote) {
                if (!in_array($quote['status'], ['sent', 'viewed', 'accepted', 'declined', 'approved', 'rejected'], true)) {
                    $quote = null;
                    $error = 'Este orçamento não está disponível para visualização.';
                } else {
                    if ($quote['status'] === 'sent') {
                        $pdo->prepare("UPDATE quotes SET status = 'viewed', viewed_at = NOW() WHERE id = ?")->execute([$quote['id']]);
                        $quote['status'] = 'viewed';
                        if ($pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0) {
                            quotes_log_activity($pdo, $quote['id'], 'viewed', 'client', 'client', null);
                        }
                    }
                    $stmt = $pdo->prepare("SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id");
                    $stmt->execute([$quote['id']]);
                    $quote['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                $error = 'Orçamento não encontrado ou link inválido.';
            }
        } else {
            $error = 'Orçamento não encontrado.';
        }
    } catch (Exception $e) {
        $error = 'Erro ao carregar o orçamento.';
    }
} elseif ($token) {
    $error = 'Orçamento não encontrado.';
}

$action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $quote) {
    $action = $_POST['action'] ?? '';
    if ($action === 'accept' && quotes_can_accept_or_decline($quote)) {
        $pdo = getDBConnection();
        $pdo->prepare("UPDATE quotes SET status = 'accepted', approved_at = NOW() WHERE id = ?")->execute([$quote['id']]);
        if ($pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0) {
            quotes_log_activity($pdo, $quote['id'], 'accepted', 'client', 'client', ['accepted_at' => date('Y-m-d H:i:s')]);
        }
        $quote['status'] = 'accepted';
        $action_message = 'success_accept';
    } elseif ($action === 'decline' && quotes_can_accept_or_decline($quote)) {
        $reason = trim($_POST['reason'] ?? '');
        $pdo = getDBConnection();
        if ($pdo->query("SHOW COLUMNS FROM quotes LIKE 'decline_reason'")->rowCount() > 0) {
            $pdo->prepare("UPDATE quotes SET status = 'declined', declined_at = NOW(), decline_reason = ? WHERE id = ?")->execute([$reason ?: null, $quote['id']]);
        } else {
            $pdo->prepare("UPDATE quotes SET status = 'declined' WHERE id = ?")->execute([$quote['id']]);
        }
        if ($pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0) {
            quotes_log_activity($pdo, $quote['id'], 'declined', 'client', 'client', ['reason' => $reason]);
        }
        $quote['status'] = 'declined';
        $action_message = 'success_decline';
    }
}

$total_display = isset($quote['total_amount']) ? number_format((float)$quote['total_amount'], 2, ',', '.') : '0,00';
$currency_symbol = 'R$';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento — <?php echo htmlspecialchars($company_name); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 24px; background: #f8fafc; color: #1a2036; line-height: 1.5; }
        .container { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
        .header { padding: 24px; background: #1a2036; color: #fff; text-align: center; }
        .header img { max-height: 48px; margin-bottom: 8px; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 24px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .meta p { margin: 0 0 8px 0; font-size: 14px; color: #64748b; }
        .meta strong { color: #1a2036; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #475569; }
        .total-row { font-size: 18px; font-weight: 700; background: #f8fafc; }
        .notes { margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 8px; font-size: 14px; color: #475569; }
        .actions { margin-top: 32px; display: flex; flex-wrap: wrap; gap: 12px; }
        .btn { display: inline-block; padding: 14px 28px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; }
        .btn-accept { background: #059669; color: #fff; }
        .btn-accept:hover { background: #047857; }
        .btn-decline { background: #fff; color: #dc2626; border: 2px solid #dc2626; }
        .btn-decline:hover { background: #fef2f2; }
        .message { padding: 16px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: #dcfce7; color: #166534; }
        .message.error { background: #fee2e2; color: #991b1b; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-viewed { background: #fef3c7; color: #92400e; }
        .status-accepted, .status-approved { background: #dcfce7; color: #166534; }
        .status-declined, .status-rejected { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="<?php echo htmlspecialchars($company_logo); ?>" alt="<?php echo htmlspecialchars($company_name); ?>">
        <h1><?php echo htmlspecialchars($company_name); ?></h1>
        <p style="margin: 8px 0 0 0; opacity: 0.9;">Orçamento</p>
    </div>
    <div class="content">
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($action_message === 'success_accept'): ?>
            <div class="message success">Obrigado! Você aceitou este orçamento. Entraremos em contato em breve.</div>
        <?php elseif ($action_message === 'success_decline'): ?>
            <div class="message success">O orçamento foi recusado. Obrigado pelo retorno.</div>
        <?php elseif ($quote): ?>
            <div class="meta">
                <div>
                    <p>Orçamento <strong><?php echo htmlspecialchars($quote['quote_number'] ?? '#' . $quote['id']); ?></strong></p>
                    <p>Cliente: <strong><?php echo htmlspecialchars($quote['lead_name'] ?? '—'); ?></strong></p>
                    <?php if (!empty($quote['issue_date'])): ?>
                        <p>Data: <strong><?php echo date('d/m/Y', strtotime($quote['issue_date'])); ?></strong></p>
                    <?php endif; ?>
                </div>
                <div style="text-align: right;">
                    <p>Status: <span class="status-badge status-<?php echo htmlspecialchars($quote['status']); ?>"><?php
                        $labels = ['draft'=>'Rascunho','sent'=>'Enviado','viewed'=>'Visualizado','accepted'=>'Aceito','approved'=>'Aprovado','declined'=>'Recusado','rejected'=>'Rejeitado','expired'=>'Expirado'];
                        echo $labels[$quote['status']] ?? $quote['status'];
                    ?></span></p>
                </div>
            </div>

            <table>
                <thead>
                <tr><th>Descrição</th><th>Qtd</th><th>Preço unit.</th><th>Total</th></tr>
                </thead>
                <tbody>
                <?php
                $has_new_items = !empty($quote['items']) && isset($quote['items'][0]['name']);
                foreach ($quote['items'] as $it):
                    $name = $it['name'] ?? $it['floor_type'] ?? 'Item';
                    $qty = $it['quantity'] ?? $it['area_sqft'] ?? 1;
                    $unit = $it['unit_price'] ?? 0;
                    $row_total = $it['total'] ?? $it['total_price'] ?? ($qty * $unit);
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($name); ?></td>
                        <td><?php echo number_format((float)$qty, 2, ',', '.'); ?></td>
                        <td><?php echo $currency_symbol . ' ' . number_format((float)$unit, 2, ',', '.'); ?></td>
                        <td><?php echo $currency_symbol . ' ' . number_format((float)$row_total, 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Total</td>
                    <td><?php echo $currency_symbol . ' ' . $total_display; ?></td>
                </tr>
                </tbody>
            </table>

            <?php if (!empty($quote['notes'])): ?>
                <div class="notes"><?php echo nl2br(htmlspecialchars($quote['notes'])); ?></div>
            <?php endif; ?>

            <?php if (quotes_can_accept_or_decline($quote)): ?>
                <div class="actions">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="btn btn-accept">Aceitar orçamento</button>
                    </form>
                    <form method="post" style="display: inline;" id="decline-form">
                        <input type="hidden" name="action" value="decline">
                        <input type="text" name="reason" placeholder="Motivo da recusa (opcional)" style="padding: 10px; margin-right: 8px; border: 2px solid #e2e8f0; border-radius: 6px; width: 220px;">
                        <button type="submit" class="btn btn-decline">Recusar</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>Use o link enviado por e-mail para visualizar o orçamento.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
