<?php
/**
 * Módulo Orçamentos (Invoice2go-style) - Lista + filtros
 */
require_once __DIR__ . '/../config/database.php';

$quotes = [];
$has_quotes = false;
$pdo = null;
$pipeline_config = file_exists(__DIR__ . '/../config/pipeline.php') ? require __DIR__ . '/../config/pipeline.php' : [];
$quote_config = file_exists(__DIR__ . '/../config/quotes.php') ? require __DIR__ . '/../config/quotes.php' : [];
$quote_status_labels = isset($quote_config['quote_status']) ? $quote_config['quote_status'] : ($pipeline_config['quote_status'] ?? ['draft' => 'Rascunho', 'sent' => 'Enviado', 'viewed' => 'Visualizado', 'approved' => 'Aprovado', 'rejected' => 'Rejeitado']);
$service_types = $pipeline_config['service_types'] ?? ['vinyl' => 'Vinyl', 'hardwood' => 'Hardwood', 'tile' => 'Tile', 'carpet' => 'Carpet', 'refinishing' => 'Refinishing', 'laminate' => 'Laminate', 'other' => 'Outro'];

$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : null;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $has_quotes = $pdo->query("SHOW TABLES LIKE 'quotes'")->rowCount() > 0;
            if ($has_quotes) {
                $sql = "SELECT q.*, l.name as lead_name, l.email as lead_email FROM quotes q LEFT JOIN leads l ON l.id = q.lead_id WHERE 1=1";
                $params = [];
                if ($lead_id) { $sql .= " AND q.lead_id = ?"; $params[] = $lead_id; }
                if ($filter_status !== '') { $sql .= " AND q.status = ?"; $params[] = $filter_status; }
                if ($filter_search !== '') {
                    $search = '%' . $filter_search . '%';
                    if ($pdo->query("SHOW COLUMNS FROM quotes LIKE 'quote_number'")->rowCount() > 0) {
                        $sql .= " AND (q.quote_number LIKE ? OR l.name LIKE ?)";
                        $params[] = $search;
                        $params[] = $search;
                    } else {
                        $sql .= " AND l.name LIKE ?";
                        $params[] = $search;
                    }
                }
                if ($filter_date_from !== '') { $sql .= " AND q.created_at >= ?"; $params[] = $filter_date_from . ' 00:00:00'; }
                if ($filter_date_to !== '') { $sql .= " AND q.created_at <= ?"; $params[] = $filter_date_to . ' 23:59:59'; }
                $sql .= " ORDER BY q.created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {
        error_log("Quotes module: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_quote' && $has_quotes && $pdo) {
    $lead_id_post = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
    $floor_type = isset($_POST['floor_type']) ? trim($_POST['floor_type']) : 'other';
    $area_sqft = isset($_POST['area_sqft']) ? str_replace(',', '.', $_POST['area_sqft']) : 0;
    $unit_price = isset($_POST['unit_price']) ? str_replace(',', '.', $_POST['unit_price']) : 0;
    $margin_percent = isset($_POST['margin_percent']) ? (float)str_replace(',', '.', $_POST['margin_percent']) : 15;
    if ($lead_id_post && $area_sqft > 0 && $unit_price >= 0) {
        $materials = (float)$area_sqft * (float)$unit_price;
        $labor = $materials * ($margin_percent / 100);
        $total = $materials + $labor;
        $stmt = $pdo->prepare("INSERT INTO quotes (lead_id, version, total_amount, labor_amount, materials_amount, margin_percent, status) VALUES (?, 1, ?, ?, ?, ?, 'draft')");
        $stmt->execute([$lead_id_post, $total, $labor, $materials, $margin_percent]);
        $quote_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO quote_items (quote_id, floor_type, area_sqft, unit_price, total_price) VALUES (?, ?, ?, ?, ?)")
            ->execute([$quote_id, $floor_type, (float)$area_sqft, (float)$unit_price, $materials]);
        header('Location: ?module=quote-detail&id=' . $quote_id);
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
.q-badge.approved, .q-badge.accepted { background: #dcfce7; color: #166534; }
.q-badge.rejected, .q-badge.declined { background: #fee2e2; color: #991b1b; }
.q-badge.expired { background: #f1f5f9; color: #64748b; }
.q-form label { display: block; margin-bottom: 6px; font-weight: 600; }
.q-form input, .q-form select { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; margin-bottom: 12px; }
#lead-select { max-width: 100%; }
.q-btn { padding: 10px 20px; background: #1a2036; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
.q-filters { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 20px; }
.q-filters label { font-size: 12px; font-weight: 600; color: #475569; }
.q-filters input, .q-filters select { padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; }
</style>

<div class="q-container">
    <h1 class="q-title">Orçamentos</h1>
    <p style="color: #64748b; margin-bottom: 20px;">Crie e gerencie orçamentos. Filtros por status, cliente ou número. Abra um orçamento para editar itens, desconto e enviar ao cliente.</p>

    <?php if (!$has_quotes): ?>
        <p style="color: #64748b;">Execute a migration do CRM (<strong>database/migration-crm-completo.sql</strong>) e <strong>database/migration-quotes-invoice2go.sql</strong> para usar orçamentos.</p>
    <?php else: ?>

    <div class="q-card">
        <h3>Novo orçamento (rápido)</h3>
        <form class="q-form" method="post" style="max-width: 560px;" id="form-new-quote">
            <input type="hidden" name="action" value="create_quote">
            <label>Lead</label>
            <?php if (empty($recent_leads)): ?>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Nenhum lead cadastrado. Digite o ID do lead (ex.: do CRM).</p>
                <input type="number" name="lead_id" placeholder="ID do lead" min="1" required style="margin-top: 0;">
            <?php else: ?>
                <input type="text" id="lead-filter" placeholder="Filtrar lista: digite nome, email ou telefone" style="margin-bottom: 8px; padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; width: 100%;">
                <select name="lead_id" id="lead-select" required style="margin-bottom: 8px;">
                    <option value="">— Selecione o lead —</option>
                    <?php foreach ($recent_leads as $l): ?>
                        <option value="<?php echo (int)$l['id']; ?>" data-search="<?php echo htmlspecialchars(strtolower(($l['name'] ?? '') . ' ' . ($l['email'] ?? '') . ' ' . ($l['phone'] ?? '') . ' ' . $l['id'])); ?>">
                            <?php echo htmlspecialchars($l['name'] ?? '—'); ?> — <?php echo htmlspecialchars($l['email'] ?? ''); ?> — #<?php echo (int)$l['id']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 12px;">Ou busque: <input type="text" id="lead-search" placeholder="Nome, email ou telefone..." style="width: 200px; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 4px;"> <span id="lead-search-status"></span></p>
                <div id="lead-search-results" style="display: none; max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 12px; background: #fff;"></div>
            <?php endif; ?>
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
            <button type="submit" class="q-btn">Criar e editar orçamento</button>
        </form>
    </div>

    <div class="q-card">
        <h3>Lista de orçamentos</h3>
        <form method="get" class="q-filters">
            <input type="hidden" name="module" value="quotes">
            <?php if ($lead_id): ?><input type="hidden" name="lead_id" value="<?php echo $lead_id; ?>"><?php endif; ?>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="">— Todos —</option>
                    <?php foreach ($quote_status_labels as $k => $l): ?>
                        <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $filter_status === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Buscar (número ou cliente)</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Ex: Q-2024 ou nome">
            </div>
            <div>
                <label>Data de</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            <div>
                <label>Data até</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            <div>
                <button type="submit" class="q-btn">Filtrar</button>
                <a href="?module=quotes<?php echo $lead_id ? '&lead_id='.$lead_id : ''; ?>" style="margin-left: 8px; color: #64748b;">Limpar</a>
            </div>
        </form>
        <?php if (empty($quotes)): ?>
            <p style="color: #64748b;">Nenhum orçamento encontrado.</p>
        <?php else: ?>
        <table class="q-table">
            <thead>
                <tr>
                    <th>Nº / ID</th>
                    <th>Cliente / Lead</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $q): ?>
                <tr>
                    <td><?php echo htmlspecialchars($q['quote_number'] ?? '#' . $q['id']); ?></td>
                    <td>
                        <?php if (!empty($q['lead_id'])): ?>
                            <a href="?module=lead-detail&id=<?php echo (int)$q['lead_id']; ?>"><?php echo htmlspecialchars($q['lead_name'] ?? 'Lead #'.$q['lead_id']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($q['lead_name'] ?? '—'); ?>
                        <?php endif; ?>
                    </td>
                    <td>R$ <?php echo number_format((float)($q['total_amount'] ?? 0), 2, ',', '.'); ?></td>
                    <td><span class="q-badge <?php echo htmlspecialchars($q['status'] ?? 'draft'); ?>"><?php echo $quote_status_labels[$q['status'] ?? 'draft'] ?? $q['status']; ?></span></td>
                    <td><?php echo date('d/m/Y', strtotime($q['created_at'])); ?></td>
                    <td><a href="?module=quote-detail&id=<?php echo (int)$q['id']; ?>" class="q-btn" style="padding: 6px 12px; font-size: 13px;">Ver / Editar</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($recent_leads)): ?>
<script>
(function() {
    var leadFilter = document.getElementById('lead-filter');
    var leadSelect = document.getElementById('lead-select');
    var leadSearch = document.getElementById('lead-search');
    var leadSearchResults = document.getElementById('lead-search-results');
    var leadSearchStatus = document.getElementById('lead-search-status');
    if (!leadSelect) return;

    if (leadFilter) {
        leadFilter.addEventListener('input', function() {
            var v = this.value.toLowerCase().trim();
            var opts = leadSelect.querySelectorAll('option[data-search]');
            for (var i = 0; i < opts.length; i++) {
                opts[i].style.display = opts[i].getAttribute('data-search').indexOf(v) >= 0 ? '' : 'none';
            }
        });
    }

    if (leadSearch && leadSearchResults) {
        var searchTimer;
        leadSearch.addEventListener('input', function() {
            var q = this.value.trim();
            leadSearchStatus.textContent = '';
            leadSearchResults.style.display = 'none';
            leadSearchResults.innerHTML = '';
            if (q.length < 2) return;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                leadSearchStatus.textContent = 'Buscando...';
                var path = window.location.pathname;
                var base = path.substring(0, path.lastIndexOf('/') + 1);
                var apiUrl = base + 'api/leads/search.php?q=' + encodeURIComponent(q) + '&limit=25';
                fetch(apiUrl).then(function(r) { return r.json(); }).then(function(res) {
                    leadSearchStatus.textContent = '';
                    if (!res.success || !res.data || res.data.length === 0) {
                        leadSearchStatus.textContent = 'Nenhum lead encontrado.';
                        return;
                    }
                    leadSearchResults.innerHTML = '';
                    res.data.forEach(function(l) {
                        var label = (l.name || '—') + ' — ' + (l.email || '') + ' — #' + l.id;
                        var div = document.createElement('div');
                        div.style.cssText = 'padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0;';
                        div.textContent = label;
                        div.onmouseover = function() { this.style.background = '#f1f5f9'; };
                        div.onmouseout = function() { this.style.background = ''; };
                        div.onclick = function() {
                            var id = String(l.id);
                            var opt = leadSelect.querySelector('option[value="' + id + '"]');
                            if (!opt) {
                                opt = document.createElement('option');
                                opt.value = id;
                                opt.setAttribute('data-search', (l.name + ' ' + (l.email || '') + ' ' + (l.phone || '') + ' ' + id).toLowerCase());
                                opt.textContent = label;
                                leadSelect.appendChild(opt);
                            }
                            leadSelect.value = id;
                            leadSearchResults.style.display = 'none';
                            leadSearchResults.innerHTML = '';
                            leadSearch.value = '';
                            leadSearchStatus.textContent = 'Selecionado: ' + (l.name || '') + ' (#' + l.id + ')';
                        };
                        leadSearchResults.appendChild(div);
                    });
                    leadSearchResults.style.display = 'block';
                }).catch(function() {
                    leadSearchStatus.textContent = 'Erro na busca.';
                });
            }, 300);
        });
        leadSearch.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && leadSearchResults.innerHTML) leadSearchResults.style.display = 'block';
        });
        document.addEventListener('click', function(e) {
            if (leadSearchResults && !leadSearch.contains(e.target) && !leadSearchResults.contains(e.target)) {
                leadSearchResults.style.display = 'none';
            }
        });
    }
})();
</script>
<?php endif; ?>
