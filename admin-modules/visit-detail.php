<?php
/**
 * Detalhe da visita + registro de medição
 */
require_once __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$visit = null;
$measurements = [];
$error = null;

if ($id <= 0) {
    $error = 'ID inválido';
} elseif (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT v.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone FROM visits v LEFT JOIN leads l ON l.id = v.lead_id WHERE v.id = ?");
            $stmt->execute([$id]);
            $visit = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($visit) {
                $stmt = $pdo->prepare("SELECT * FROM measurements WHERE visit_id = ? ORDER BY id");
                $stmt->execute([$id]);
                $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error = 'Visita não encontrada';
            }
        }
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
} else {
    $error = 'Banco não configurado';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $visit) {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['scheduled','completed','cancelled','no_show'])) {
            $pdo->prepare("UPDATE visits SET status = ? WHERE id = ?")->execute([$status, $id]);
            header('Location: ?module=visit-detail&id=' . $id);
            exit;
        }
    }
    if ($action === 'add_measurement') {
        $area_sqft = isset($_POST['area_sqft']) ? str_replace(',', '.', $_POST['area_sqft']) : null;
        $rooms = isset($_POST['rooms']) ? trim($_POST['rooms']) : null;
        $technical_notes = isset($_POST['technical_notes']) ? trim($_POST['technical_notes']) : null;
        if ($area_sqft !== null && $area_sqft !== '') {
            $pdo->prepare("INSERT INTO measurements (visit_id, lead_id, area_sqft, rooms, technical_notes) VALUES (?, ?, ?, ?, ?)")
                ->execute([$id, $visit['lead_id'], (float)$area_sqft, $rooms, $technical_notes]);
            header('Location: ?module=visit-detail&id=' . $id);
            exit;
        }
    }
}

$status_labels = ['scheduled' => 'Agendada', 'completed' => 'Realizada', 'cancelled' => 'Cancelada', 'no_show' => 'Não compareceu'];
?>
<style>
.vd-container { padding: 20px; max-width: 900px; margin: 0 auto; }
.vd-title { font-size: 22px; font-weight: 700; color: #1a2036; margin-bottom: 20px; }
.vd-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.vd-card h2 { margin-top: 0; margin-bottom: 16px; color: #1a2036; font-size: 18px; }
.vd-row { display: flex; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
.vd-label { font-weight: 600; color: #4a5568; width: 140px; flex-shrink: 0; }
.vd-value { color: #333; }
.vd-form label { display: block; margin-bottom: 6px; font-weight: 600; }
.vd-form input, .vd-form select, .vd-form textarea { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; margin-bottom: 12px; }
.vd-btn { padding: 10px 20px; background: #1a2036; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
.vd-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.vd-table th, .vd-table td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.vd-table th { background: #f1f5f9; font-weight: 600; }
</style>

<div class="vd-container">
    <?php if ($error || !$visit): ?>
        <p style="color: #e53e3e;"><?php echo htmlspecialchars($error ?? 'Visita não encontrada'); ?></p>
        <a href="?module=visits">← Voltar para Visitas</a>
    <?php else: ?>
    <h1 class="vd-title">Visita #<?php echo (int)$visit['id']; ?></h1>
    <p><a href="?module=visits">← Voltar para Visitas</a> <?php if ($visit['lead_id']): ?>| <a href="?module=lead-detail&id=<?php echo (int)$visit['lead_id']; ?>">Ver lead</a><?php endif; ?></p>

    <div class="vd-card">
        <h2>Dados da visita</h2>
        <div class="vd-row"><span class="vd-label">Data/Hora</span><span class="vd-value"><?php echo date('d/m/Y H:i', strtotime($visit['scheduled_at'])); ?></span></div>
        <div class="vd-row"><span class="vd-label">Status</span><span class="vd-value"><?php echo $status_labels[$visit['status']] ?? $visit['status']; ?></span></div>
        <div class="vd-row"><span class="vd-label">Lead</span><span class="vd-value"><?php echo htmlspecialchars($visit['lead_name'] ?? '—'); ?> <?php if ($visit['lead_phone']): ?>(<?php echo htmlspecialchars($visit['lead_phone']); ?>)<?php endif; ?></span></div>
        <?php if (!empty($visit['address'])): ?><div class="vd-row"><span class="vd-label">Endereço</span><span class="vd-value"><?php echo htmlspecialchars($visit['address']); ?></span></div><?php endif; ?>
        <?php if (!empty($visit['notes'])): ?><div class="vd-row"><span class="vd-label">Observações</span><span class="vd-value"><?php echo nl2br(htmlspecialchars($visit['notes'])); ?></span></div><?php endif; ?>

        <form method="post" style="margin-top: 16px;">
            <input type="hidden" name="action" value="update_status">
            <label>Alterar status</label>
            <select name="status">
                <?php foreach ($status_labels as $k => $l): ?>
                    <option value="<?php echo $k; ?>" <?php echo ($visit['status'] ?? '') === $k ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="vd-btn">Atualizar</button>
        </form>
    </div>

    <div class="vd-card">
        <h2>Registrar medição</h2>
        <form method="post" class="vd-form">
            <input type="hidden" name="action" value="add_measurement">
            <label>Metragem (sqft)</label>
            <input type="text" name="area_sqft" placeholder="Ex: 450.50" required>
            <label>Cômodos / áreas</label>
            <input type="text" name="rooms" placeholder="Ex: Sala, 2 quartos, cozinha">
            <label>Observações técnicas</label>
            <textarea name="technical_notes" rows="3" placeholder="Subpiso, desníveis, etc."></textarea>
            <button type="submit" class="vd-btn">Salvar medição</button>
        </form>
    </div>

    <?php if (!empty($measurements)): ?>
    <div class="vd-card">
        <h2>Medições (<?php echo count($measurements); ?>)</h2>
        <table class="vd-table">
            <thead><tr><th>Metragem (sqft)</th><th>Cômodos</th><th>Observações</th></tr></thead>
            <tbody>
                <?php foreach ($measurements as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['area_sqft'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($m['rooms'] ?? '—'); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($m['technical_notes'] ?? '')); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
