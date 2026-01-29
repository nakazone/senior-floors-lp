<?php
/**
 * Módulo Visitas e Medições - Senior Floors CRM
 */
require_once __DIR__ . '/../config/database.php';

$visits = [];
$users = [];
$has_visits = false;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $has_visits = $pdo->query("SHOW TABLES LIKE 'visits'")->rowCount() > 0;
            if ($has_visits) {
                $lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : null;
                $sql = "SELECT v.*, l.name as lead_name, l.email as lead_email, l.phone as lead_phone FROM visits v LEFT JOIN leads l ON l.id = v.lead_id WHERE 1=1";
                $params = [];
                if ($lead_id) { $sql .= " AND v.lead_id = ?"; $params[] = $lead_id; }
                $sql .= " ORDER BY v.scheduled_at DESC";
                $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
                if ($params) $stmt->execute($params);
                $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $ul = $pdo->query("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name");
            if ($ul) $users = $ul->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Visits module: " . $e->getMessage());
    }
}

$status_labels = ['scheduled' => 'Agendada', 'completed' => 'Realizada', 'cancelled' => 'Cancelada', 'no_show' => 'Não compareceu'];
?>
<style>
.v-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.v-title { font-size: 22px; font-weight: 700; color: #1a2036; margin-bottom: 20px; }
.v-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.06); }
.v-table th, .v-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.v-table th { background: #1a2036; color: #fff; font-weight: 600; }
.v-table tr:hover { background: #f8fafc; }
.v-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.v-badge.scheduled { background: #dbeafe; color: #1d4ed8; }
.v-badge.completed { background: #dcfce7; color: #166534; }
.v-badge.cancelled { background: #fee2e2; color: #991b1b; }
.v-badge.no_show { background: #fef3c7; color: #92400e; }
.v-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.v-card h3 { margin-top: 0; margin-bottom: 16px; color: #1a2036; font-size: 18px; }
.v-form label { display: block; margin-bottom: 6px; font-weight: 600; color: #4a5568; }
.v-form input, .v-form select, .v-form textarea { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; margin-bottom: 12px; }
.v-form input:focus, .v-form select:focus, .v-form textarea:focus { outline: none; border-color: #1a2036; }
.v-btn { padding: 10px 20px; background: linear-gradient(135deg, #1a2036 0%, #252b47 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
.v-btn:hover { opacity: 0.9; }
.v-empty { color: #64748b; padding: 40px; text-align: center; }
</style>

<div class="v-container">
    <h1 class="v-title">Visitas e medições</h1>
    <p style="color: #64748b; margin-bottom: 20px;">Agende visitas, registre medições e vincule ao lead/projeto.</p>

    <?php if (!$has_visits): ?>
        <p class="v-empty">Execute a migration do CRM (database/migration-crm-completo.sql) para usar visitas.</p>
    <?php else: ?>

    <div class="v-card">
        <h3>Nova visita</h3>
        <form class="v-form" method="post" action="?module=visits">
            <input type="hidden" name="action" value="create_visit">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label>Lead (ID)</label>
                    <input type="number" name="lead_id" placeholder="ID do lead" min="1">
                </div>
                <div>
                    <label>Data e hora</label>
                    <input type="datetime-local" name="scheduled_at" required>
                </div>
                <div>
                    <label>Vendedor</label>
                    <select name="seller_id">
                        <option value="">—</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Técnico / Medidor</label>
                    <select name="technician_id">
                        <option value="">—</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label>Endereço</label>
                <input type="text" name="address" placeholder="Endereço da visita">
            </div>
            <div>
                <label>Observações</label>
                <textarea name="notes" rows="2" placeholder="Checklist, observações"></textarea>
            </div>
            <button type="submit" class="v-btn">Agendar visita</button>
        </form>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_visit') {
        $lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
        $scheduled_at = isset($_POST['scheduled_at']) ? trim($_POST['scheduled_at']) : '';
        if ($scheduled_at && $lead_id) {
            $stmt = $pdo->prepare("INSERT INTO visits (lead_id, scheduled_at, seller_id, technician_id, address, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'scheduled')");
            $stmt->execute([
                $lead_id,
                date('Y-m-d H:i:s', strtotime($scheduled_at)),
                !empty($_POST['seller_id']) ? (int)$_POST['seller_id'] : null,
                !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null,
                !empty($_POST['address']) ? $_POST['address'] : null,
                !empty($_POST['notes']) ? $_POST['notes'] : null
            ]);
            header('Location: ?module=visits');
            exit;
        }
    }
    ?>

    <div class="v-card">
        <h3>Lista de visitas</h3>
        <?php if (empty($visits)): ?>
            <p class="v-empty">Nenhuma visita cadastrada.</p>
        <?php else: ?>
        <table class="v-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Lead</th>
                    <th>Data/Hora</th>
                    <th>Status</th>
                    <th>Endereço</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visits as $v): ?>
                <tr>
                    <td><?php echo (int)$v['id']; ?></td>
                    <td>
                        <?php if (!empty($v['lead_id'])): ?>
                            <a href="?module=lead-detail&id=<?php echo (int)$v['lead_id']; ?>"><?php echo htmlspecialchars($v['lead_name'] ?? 'Lead #'.$v['lead_id']); ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($v['scheduled_at'])); ?></td>
                    <td><span class="v-badge <?php echo htmlspecialchars($v['status'] ?? 'scheduled'); ?>"><?php echo $status_labels[$v['status'] ?? 'scheduled'] ?? $v['status']; ?></span></td>
                    <td><?php echo htmlspecialchars(mb_substr($v['address'] ?? '', 0, 40)); ?><?php echo mb_strlen($v['address'] ?? '') > 40 ? '…' : ''; ?></td>
                    <td><a href="?module=visit-detail&id=<?php echo (int)$v['id']; ?>">Ver / Medição</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
