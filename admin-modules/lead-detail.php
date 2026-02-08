<?php
/**
 * Lead Detail Module
 * FASE 2 - MÓDULO 04: Painel Admin (MVP)
 * 
 * Tela de detalhe do lead com alteração de status e observações
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/tags.php';

// Obter ID do lead
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lead_id <= 0) {
    echo "<div style='padding: 20px;'><p style='color: #e53e3e;'>❌ ID do lead inválido</p></div>";
    exit;
}

// Buscar lead do banco de dados
$lead = null;
$notes = [];
$tags = [];
$users = [];
$owner_name = null;
$has_owner_col = false;
$has_followup_col = false;
$activities = [];
$lead_visits = [];
$has_visits_table = false;
$error = null;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        if ($pdo) {
            // Buscar lead
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id");
            $stmt->execute([':id' => $lead_id]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            $has_followup_col = $lead && array_key_exists('next_follow_up_at', $lead);
            
            if ($lead) {
                // Buscar observações (se a tabela existir)
                try {
                    if ($pdo->query("SHOW TABLES LIKE 'lead_notes'")->rowCount() > 0) {
                        $stmt = $pdo->prepare("SELECT id, note, created_by, created_at FROM lead_notes WHERE lead_id = :lead_id ORDER BY created_at DESC");
                        $stmt->execute([':lead_id' => $lead_id]);
                        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    error_log("Lead detail load notes: " . $e->getMessage());
                }
                
                // Buscar tags (coluna pode ser tag ou tag_name conforme o schema)
                $tag_col = 'tag_name';
                try {
                    $chk = $pdo->query("SHOW COLUMNS FROM lead_tags LIKE 'tag_name'");
                    if (!$chk || $chk->rowCount() === 0) $tag_col = 'tag';
                } catch (Exception $e) { $tag_col = 'tag'; }
                $tag_select = $tag_col === 'tag_name' ? 'id, tag_name, created_at' : 'id, tag AS tag_name, created_at';
                $stmt = $pdo->prepare("
                    SELECT $tag_select
                    FROM lead_tags
                    WHERE lead_id = :lead_id
                    ORDER BY created_at DESC
                ");
                $stmt->execute([':lead_id' => $lead_id]);
                $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Usuários (para responsável e encaminhamento)
                $users = [];
                $owner_name = null;
                $has_owner_col = false;
                try {
                    if ($pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0) {
                        $users = $pdo->query("SELECT id, name, email, role FROM users WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                    }
                    if ($pdo->query("SHOW COLUMNS FROM leads LIKE 'owner_id'")->rowCount() > 0) {
                        $has_owner_col = true;
                        if (!empty($lead['owner_id'])) {
                            $own = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                            $own->execute([$lead['owner_id']]);
                            $row = $own->fetch(PDO::FETCH_ASSOC);
                            $owner_name = $row ? $row['name'] : null;
                        }
                    }
                } catch (Exception $e) {}
                
                // Atividades/histórico de contatos (se tabela existir)
                $activities = [];
                try {
                    if ($pdo->query("SHOW TABLES LIKE 'activities'")->rowCount() > 0) {
                        $stmt = $pdo->prepare("SELECT id, activity_type, subject, description, activity_date, user_id, created_at FROM activities WHERE lead_id = ? ORDER BY COALESCE(activity_date, created_at) DESC, created_at DESC LIMIT 100");
                        $stmt->execute([$lead_id]);
                        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    error_log("Lead detail load activities: " . $e->getMessage());
                }
                // Visitas agendadas para este lead
                $lead_visits = [];
                $has_visits_table = false;
                try {
                    if ($pdo->query("SHOW TABLES LIKE 'visits'")->rowCount() > 0) {
                        $has_visits_table = true;
                        $stmt = $pdo->prepare("SELECT id, lead_id, scheduled_at, seller_id, technician_id, address, notes, status, created_at FROM visits WHERE lead_id = ? ORDER BY scheduled_at DESC");
                        $stmt->execute([$lead_id]);
                        $lead_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {}
            } else {
                $error = "Lead não encontrado";
            }
        }
    } catch (PDOException $e) {
        $error = "Erro ao buscar lead: " . $e->getMessage();
        error_log("Lead detail error: " . $e->getMessage());
    }
} else {
    $error = "Banco de dados não configurado";
}

if ($error || !$lead) {
    echo "<div style='padding: 20px;'>";
    echo "<p style='color: #e53e3e;'>❌ " . htmlspecialchars($error ?? 'Lead não encontrado') . "</p>";
    echo "<a href='?module=crm' style='color: #1a2036; text-decoration: none;'>← Voltar para CRM</a>";
    echo "</div>";
    exit;
}

// Processar atualização de status/prioridade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' || $_POST['action'] === 'update_priority') {
        $api_url = '../api/leads/update.php';
        $post_data = [
            'lead_id' => $lead_id,
            'status' => $_POST['action'] === 'update_status' ? $_POST['status'] : $lead['status'],
            'priority' => $_POST['action'] === 'update_priority' ? $_POST['priority'] : $lead['priority']
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Recarregar página após atualização
        header('Location: ?module=lead-detail&id=' . $lead_id);
        exit;
    }
    
    if ($_POST['action'] === 'add_note' && !empty(trim($_POST['note'] ?? ''))) {
        $note_text = trim($_POST['note']);
        $created_by = $_SESSION['admin_name'] ?? 'admin';
        if (isDatabaseConfigured()) {
            try {
                $pdo_note = getDBConnection();
                if ($pdo_note && $pdo_note->query("SHOW TABLES LIKE 'lead_notes'")->rowCount() > 0) {
                    $stmt = $pdo_note->prepare("INSERT INTO lead_notes (lead_id, note, created_by) VALUES (?, ?, ?)");
                    $stmt->execute([$lead_id, $note_text, $created_by]);
                }
            } catch (Exception $e) {
                error_log("Lead detail add_note: " . $e->getMessage());
            }
        }
        header('Location: ?module=lead-detail&id=' . $lead_id . '#interacoes');
        exit;
    }

    if ($_POST['action'] === 'assign_owner' && isset($_POST['owner_id'])) {
        $api_url = '../api/assignment/assign.php';
        $post_data = [
            'lead_id' => $lead_id,
            'to_user_id' => (int)$_POST['owner_id'],
            'reason' => isset($_POST['assign_reason']) ? trim($_POST['assign_reason']) : '',
            'assigned_by' => $_SESSION['admin_user_id'] ?? null
        ];
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        header('Location: ?module=lead-detail&id=' . $lead_id);
        exit;
    }
    
    if ($_POST['action'] === 'add_activity' && isset($_POST['activity_type'])) {
        $activity_type = trim($_POST['activity_type']);
        $subject = isset($_POST['activity_subject']) ? trim($_POST['activity_subject']) : null;
        $description = isset($_POST['activity_description']) ? trim($_POST['activity_description']) : null;
        // Data/hora do contato: usar o enviado ou agora
        $activity_date = null;
        if (!empty(trim($_POST['activity_datetime'] ?? ''))) {
            $ts = strtotime(trim($_POST['activity_datetime']));
            $activity_date = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }
        if (!$activity_date) {
            $activity_date = date('Y-m-d H:i:s');
        }
        $valid_types = ['email_sent', 'whatsapp_message', 'phone_call', 'meeting_scheduled', 'site_visit', 'proposal_sent', 'note', 'status_change', 'assignment', 'other'];
        $type_labels_short = ['phone_call' => 'Ligação', 'email_sent' => 'E-mail', 'whatsapp_message' => 'WhatsApp', 'meeting_scheduled' => 'Reunião', 'note' => 'Observação', 'other' => 'Contato'];
        $saved = false;
        if (in_array($activity_type, $valid_types) && isDatabaseConfigured()) {
            try {
                $pdo_act = getDBConnection();
                if ($pdo_act && $pdo_act->query("SHOW TABLES LIKE 'activities'")->rowCount() > 0) {
                    $stmt = $pdo_act->prepare("
                        INSERT INTO activities (lead_id, activity_type, subject, description, activity_date, user_id, related_to)
                        VALUES (?, ?, ?, ?, ?, ?, 'lead')
                    ");
                    $stmt->execute([$lead_id, $activity_type, $subject ?: null, $description ?: null, $activity_date, $_SESSION['admin_user_id'] ?? null]);
                    $saved = true;
                }
                if (!$saved && $pdo_act && $pdo_act->query("SHOW TABLES LIKE 'lead_notes'")->rowCount() > 0) {
                    $label = $type_labels_short[$activity_type] ?? 'Contato';
                    $note_text = "[$label]" . ($subject ? " $subject" : '') . ($description ? " — $description" : '');
                    $created_by = $_SESSION['admin_name'] ?? 'admin';
                    $stmt = $pdo_act->prepare("INSERT INTO lead_notes (lead_id, note, created_by) VALUES (?, ?, ?)");
                    $stmt->execute([$lead_id, $note_text, $created_by]);
                    $saved = true;
                }
            } catch (Exception $e) {
                error_log("Lead detail add_activity: " . $e->getMessage());
            }
        }
        header('Location: ?module=lead-detail&id=' . $lead_id . '#interacoes');
        exit;
    }
    
    if ($_POST['action'] === 'set_follow_up' && isDatabaseConfigured()) {
        $next_at = null;
        if (!empty(trim($_POST['next_follow_up_at'] ?? ''))) {
            $ts = strtotime(trim($_POST['next_follow_up_at']));
            $next_at = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }
        try {
            $pdo_fu = getDBConnection();
            if ($pdo_fu && $pdo_fu->query("SHOW COLUMNS FROM leads LIKE 'next_follow_up_at'")->rowCount() > 0) {
                $pdo_fu->prepare("UPDATE leads SET next_follow_up_at = ? WHERE id = ?")->execute([$next_at, $lead_id]);
                $lead['next_follow_up_at'] = $next_at;
            }
        } catch (Exception $e) {
            error_log("Lead detail set_follow_up: " . $e->getMessage());
        }
        header('Location: ?module=lead-detail&id=' . $lead_id);
        exit;
    }
    
    if ($_POST['action'] === 'update_qualification') {
        $api_url = '../api/leads/update.php';
        $post_data = [
            'lead_id' => $lead_id,
            'property_type' => isset($_POST['property_type']) ? trim($_POST['property_type']) : '',
            'estimated_area' => isset($_POST['estimated_area']) ? trim($_POST['estimated_area']) : '',
            'service_type' => isset($_POST['service_type']) ? trim($_POST['service_type']) : '',
            'budget_estimated' => isset($_POST['budget_estimated']) ? $_POST['budget_estimated'] : '',
            'urgency' => isset($_POST['urgency']) ? $_POST['urgency'] : '',
            'is_decision_maker' => isset($_POST['is_decision_maker']) ? $_POST['is_decision_maker'] : '',
            'payment_type' => isset($_POST['payment_type']) ? $_POST['payment_type'] : '',
            'has_competition' => isset($_POST['has_competition']) ? $_POST['has_competition'] : ''
        ];
        if (isset($_POST['status']) && $_POST['status'] !== '') {
            $post_data['status'] = trim($_POST['status']);
            if ($post_data['status'] === 'closed_lost' && isset($_POST['disqualification_reason'])) {
                $post_data['disqualification_reason'] = trim($_POST['disqualification_reason']);
            }
        }
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
        header('Location: ?module=lead-detail&id=' . $lead_id . '#qualificacao');
        exit;
    }
    
    if ($_POST['action'] === 'schedule_visit' && isDatabaseConfigured()) {
        $scheduled_at = isset($_POST['scheduled_at']) ? trim($_POST['scheduled_at']) : '';
        if ($scheduled_at) {
            $ts = strtotime($scheduled_at);
            $scheduled_at_sql = $ts ? date('Y-m-d H:i:s', $ts) : null;
            if ($scheduled_at_sql) {
                try {
                    $pdo_v = getDBConnection();
                    if ($pdo_v && $pdo_v->query("SHOW TABLES LIKE 'visits'")->rowCount() > 0) {
                        $seller_id = !empty($_POST['seller_id']) ? (int)$_POST['seller_id'] : null;
                        $address = isset($_POST['address']) ? trim($_POST['address']) : null;
                        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
                        $pdo_v->prepare("INSERT INTO visits (lead_id, scheduled_at, seller_id, address, notes, status) VALUES (?, ?, ?, ?, ?, 'scheduled')")
                            ->execute([$lead_id, $scheduled_at_sql, $seller_id, $address ?: null, $notes ?: null]);
                        header('Location: ?module=lead-detail&id=' . $lead_id . '#visitas');
                        exit;
                    }
                } catch (Exception $e) {
                    error_log("Lead detail schedule_visit: " . $e->getMessage());
                    header('Location: ?module=lead-detail&id=' . $lead_id . '&visit_error=1#visitas');
                    exit;
                }
            }
        }
        header('Location: ?module=lead-detail&id=' . $lead_id . '#visitas');
        exit;
    }
}
?>
<style>
    .lead-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .lead-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
    }
    .lead-header h1 {
        margin: 0;
        color: #1a2036;
        font-size: 28px;
    }
    .back-link {
        color: #1a2036;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .back-link:hover {
        color: #252b47;
    }
    .lead-info-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    .info-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .info-card h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #1a2036;
        font-size: 20px;
        border-bottom: 2px solid #1a2036;
        padding-bottom: 10px;
    }
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-weight: 600;
        color: #4a5568;
        width: 120px;
        flex-shrink: 0;
    }
    .info-value {
        color: #333;
        flex: 1;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-new { background: #e3f2fd; color: #1976d2; }
    .status-contacted { background: #fff3e0; color: #f57c00; }
    .status-qualified { background: #e8f5e9; color: #388e3c; }
    .status-proposal { background: #f3e5f5; color: #7b1fa2; }
    .status-closed_won { background: #c8e6c9; color: #2e7d32; }
    .status-closed_lost { background: #ffcdd2; color: #c62828; }
    .priority-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .priority-low { background: #e8f5e9; color: #388e3c; }
    .priority-medium { background: #fff3e0; color: #f57c00; }
    .priority-high { background: #ffebee; color: #c62828; }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #4a5568;
        font-size: 14px;
    }
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }
    .form-group select:focus {
        outline: none;
        border-color: #1a2036;
    }
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-primary {
        background: linear-gradient(135deg, #1a2036 0%, #252b47 100%);
        color: white;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #252b47 0%, #2a3150 100%);
    }
    .notes-section {
        margin-top: 30px;
    }
    .note-item {
        background: #f8f9fa;
        border-left: 4px solid #1a2036;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    .note-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .note-author {
        font-weight: 600;
        color: #1a2036;
    }
    .note-date {
        color: #718096;
        font-size: 12px;
    }
    .note-text {
        color: #333;
        line-height: 1.6;
    }
    .textarea {
        width: 100%;
        padding: 10px;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        resize: vertical;
        min-height: 100px;
    }
    .textarea:focus {
        outline: none;
        border-color: #1a2036;
    }
    .lead-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 20px;
        padding: 8px 0;
        border-bottom: 2px solid #e2e8f0;
    }
    .lead-tab {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s;
    }
    .lead-tab:hover { background: #f1f5f9; color: #1a2036; }
    .lead-tab.active { background: #1a2036; color: white; }
    .lead-tab { font-family: inherit; cursor: pointer; border: none; background: transparent; }
    .tab-panel { display: none !important; }
    .tab-panel.active { display: block !important; }
</style>

<div class="lead-detail-container" id="lead-detail-tabs-root">
    <div class="lead-header">
        <h1>Lead #<?php echo htmlspecialchars($lead['id']); ?></h1>
        <a href="?module=crm" class="back-link">← Voltar para CRM</a>
    </div>

    <nav class="lead-tabs" role="tablist" id="lead-tablist">
        <button type="button" class="lead-tab active" data-tab="resumo" onclick="window.leadDetailShowPanel('resumo')">Resumo</button>
        <button type="button" class="lead-tab" data-tab="qualificacao" onclick="window.leadDetailShowPanel('qualificacao')">Qualificação</button>
        <button type="button" class="lead-tab" data-tab="interacoes" onclick="window.leadDetailShowPanel('interacoes')">Interações</button>
        <button type="button" class="lead-tab" data-tab="visitas" onclick="window.leadDetailShowPanel('visitas')">Visitas</button>
        <button type="button" class="lead-tab" data-tab="propostas" onclick="window.leadDetailShowPanel('propostas')">Propostas</button>
        <button type="button" class="lead-tab" data-tab="contrato" onclick="window.leadDetailShowPanel('contrato')">Contrato</button>
        <button type="button" class="lead-tab" data-tab="producao" onclick="window.leadDetailShowPanel('producao')">Produção</button>
    </nav>
    <script>
    window.leadDetailShowPanel = function(tabId) {
        tabId = String(tabId || '').replace(/^#/, '').replace(/^panel-/, '') || 'resumo';
        var container = document.getElementById('lead-detail-tabs-root');
        if (!container) return;
        var panels = container.querySelectorAll('.tab-panel');
        var tabs = container.querySelectorAll('.lead-tabs [data-tab]');
        for (var i = 0; i < panels.length; i++) {
            var p = panels[i];
            var isActive = p.id === 'panel-' + tabId;
            p.classList.toggle('active', isActive);
            p.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        }
        for (var j = 0; j < tabs.length; j++) {
            var t = tabs[j];
            var isTabActive = t.getAttribute('data-tab') === tabId;
            t.classList.toggle('active', isTabActive);
            t.setAttribute('aria-selected', isTabActive ? 'true' : 'false');
        }
        try {
            var url = location.pathname + (location.search || '') + '#' + tabId;
            if (history.replaceState) history.replaceState(null, '', url);
        } catch (err) {}
    };
    </script>
    
    <!-- Painel Resumo: dados essenciais + status e ações -->
    <div class="tab-panel active" id="panel-resumo" role="tabpanel" aria-labelledby="tab-resumo" aria-hidden="false">
    <div class="lead-info-grid">
        <!-- Informações do Lead (resumo) -->
        <div class="info-card">
            <h2>Informações do Lead</h2>
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">
                    <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" style="color: #1a2036;"><?php echo htmlspecialchars($lead['email']); ?></a>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value">
                    <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" style="color: #1a2036;"><?php echo htmlspecialchars($lead['phone']); ?></a>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">CEP:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['zipcode'] ?? '—'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Origem:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['source'] ?? '—'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Formulário:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['form_type'] ?? '—'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-<?php echo htmlspecialchars($lead['status']); ?>">
                        <?php $status_labels = ['new' => 'Novo', 'contacted' => 'Contatado', 'qualified' => 'Qualificado', 'proposal' => 'Proposta', 'closed_won' => 'Fechado - Ganho', 'closed_lost' => 'Fechado - Perdido']; echo $status_labels[$lead['status']] ?? $lead['status']; ?>
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Prioridade:</div>
                <div class="info-value">
                    <span class="priority-badge priority-<?php echo htmlspecialchars($lead['priority']); ?>">
                        <?php $priority_labels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta']; echo $priority_labels[$lead['priority']] ?? $lead['priority']; ?>
                    </span>
                </div>
            </div>
            <?php if ($has_owner_col && $owner_name): ?>
            <div class="info-row">
                <div class="info-label">Responsável:</div>
                <div class="info-value"><?php echo htmlspecialchars($owner_name); ?></div>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <div class="info-label">Criado em:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($lead['created_at'])); ?></div>
            </div>
        </div>
        
        <!-- Status e Ações (no Resumo) -->
        <div class="info-card">
            <h2>Status e Ações</h2>
            
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="update_status">
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="new" <?php echo $lead['status'] === 'new' ? 'selected' : ''; ?>>Novo</option>
                        <option value="contacted" <?php echo $lead['status'] === 'contacted' ? 'selected' : ''; ?>>Contatado</option>
                        <option value="qualified" <?php echo $lead['status'] === 'qualified' ? 'selected' : ''; ?>>Qualificado</option>
                        <option value="proposal" <?php echo $lead['status'] === 'proposal' ? 'selected' : ''; ?>>Proposta</option>
                        <option value="closed_won" <?php echo $lead['status'] === 'closed_won' ? 'selected' : ''; ?>>Fechado - Ganho</option>
                        <option value="closed_lost" <?php echo $lead['status'] === 'closed_lost' ? 'selected' : ''; ?>>Fechado - Perdido</option>
                    </select>
                </div>
            </form>
            
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="update_priority">
                <div class="form-group">
                    <label>Prioridade:</label>
                    <select name="priority" onchange="this.form.submit()">
                        <option value="low" <?php echo $lead['priority'] === 'low' ? 'selected' : ''; ?>>Baixa</option>
                        <option value="medium" <?php echo $lead['priority'] === 'medium' ? 'selected' : ''; ?>>Média</option>
                        <option value="high" <?php echo $lead['priority'] === 'high' ? 'selected' : ''; ?>>Alta</option>
                    </select>
                </div>
            </form>
            
            <?php if ($has_followup_col): 
                $next_fu = $lead['next_follow_up_at'] ?? null;
                $next_fu_display = $next_fu ? date('d/m/Y H:i', strtotime($next_fu)) : null;
                $next_fu_value = $next_fu ? date('Y-m-d\TH:i', strtotime($next_fu)) : '';
            ?>
            <div class="follow-up-block" style="margin-bottom: 20px; padding: 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #166534;">&#128197; Próximo follow-up</h3>
                <?php if ($next_fu_display): ?>
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #166534;"><strong><?php echo $next_fu_display; ?></strong></p>
                <?php endif; ?>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
                    <form method="POST" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; flex: 1; min-width: 200px;">
                        <input type="hidden" name="action" value="set_follow_up">
                        <div class="form-group" style="margin: 0; flex: 1; min-width: 160px;">
                            <label style="font-size: 12px;">Data e hora:</label>
                            <input type="datetime-local" name="next_follow_up_at" value="<?php echo htmlspecialchars($next_fu_value); ?>" style="width: 100%; padding: 8px 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 16px;"><?php echo $next_fu ? 'Atualizar' : 'Agendar'; ?></button>
                    </form>
                    <?php if ($next_fu): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="set_follow_up">
                        <input type="hidden" name="next_follow_up_at" value="">
                        <button type="submit" class="btn btn-secondary" style="padding: 8px 16px;">Limpar</button>
                    </form>
                    <?php endif; ?>
                </div>
                <p style="margin: 8px 0 0 0; font-size: 11px; color: #64748b;">O lead aparecerá em &quot;Follow-up hoje&quot; no CRM e no Dashboard na data escolhida.</p>
            </div>
            <?php endif; ?>
            
            <div class="info-row">
                <div class="info-label">Status Atual:</div>
                <div class="info-value">
                    <span class="status-badge status-<?php echo htmlspecialchars($lead['status']); ?>">
                        <?php 
                        $status_labels = [
                            'new' => 'Novo',
                            'contacted' => 'Contatado',
                            'qualified' => 'Qualificado',
                            'proposal' => 'Proposta',
                            'closed_won' => 'Fechado - Ganho',
                            'closed_lost' => 'Fechado - Perdido'
                        ];
                        echo $status_labels[$lead['status']] ?? $lead['status'];
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Prioridade:</div>
                <div class="info-value">
                    <span class="priority-badge priority-<?php echo htmlspecialchars($lead['priority']); ?>">
                        <?php 
                        $priority_labels = [
                            'low' => 'Baixa',
                            'medium' => 'Média',
                            'high' => 'Alta'
                        ];
                        echo $priority_labels[$lead['priority']] ?? $lead['priority'];
                        ?>
                    </span>
                </div>
            </div>
            
            <?php if ($has_owner_col && !empty($users)): ?>
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">
            <h3 style="margin-bottom: 12px; font-size: 16px;">Responsável pelo lead</h3>
            <p style="color: #64748b; margin-bottom: 12px;">Quem faz o contato com o lead. Admin pode encaminhar para outro usuário.</p>
            <form method="POST" action="?module=lead-detail&id=<?php echo $lead_id; ?>">
                <input type="hidden" name="action" value="assign_owner">
                <div class="form-group">
                    <label>Atribuir / encaminhar para:</label>
                    <select name="owner_id" class="form-group select" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                        <option value="">— Nenhum / Não atribuído —</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo (int)$u['id']; ?>" <?php echo (isset($lead['owner_id']) && (int)$lead['owner_id'] === (int)$u['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Motivo (opcional):</label>
                    <input type="text" name="assign_reason" placeholder="Ex: Encaminhamento por região" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                </div>
                <button type="submit" class="btn btn-primary">Salvar responsável</button>
            </form>
            <?php if ($owner_name): ?>
            <p style="margin-top: 12px; color: #475569; font-size: 14px;">Atual: <strong><?php echo htmlspecialchars($owner_name); ?></strong></p>
            <?php endif; ?>
            <?php elseif ($has_owner_col && empty($users)): ?>
            <p style="color: #64748b; font-size: 13px;">Cadastre usuários em <strong>Users</strong> para atribuir responsável pelo lead.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <!-- Fim painel Resumo -->
    
    <!-- Painel Qualificação -->
    <div class="tab-panel" id="panel-qualificacao" role="tabpanel" aria-labelledby="tab-qualificacao" aria-hidden="true">
        <div class="qualificacao-layout">
        <!-- Coluna esquerda: Dados atuais + Automação -->
        <div class="qual-col-left">
            <div class="info-card">
                <h2>Dados de qualificação</h2>
                <?php if (!empty($lead['message'])): ?>
                <div class="info-row">
                    <div class="info-label">Mensagem:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($lead['message'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($lead['address'])): ?>
                <div class="info-row">
                    <div class="info-label">Endereço:</div>
                    <div class="info-value"><?php echo htmlspecialchars($lead['address']); ?></div>
                </div>
                <?php endif; ?>
                <?php 
                $pt = $lead['property_type'] ?? '';
                $pt_label = $pt === 'casa' ? 'Residencial (Casa)' : ($pt === 'apartamento' ? 'Residencial (Apartamento)' : ($pt === 'comercial' ? 'Comercial' : '—'));
                if ($pt): ?>
                <div class="info-row">
                    <div class="info-label">Tipo de imóvel:</div>
                    <div class="info-value"><?php echo $pt_label; ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($lead['estimated_area'])): ?>
                <div class="info-row">
                    <div class="info-label">Metragem estimada:</div>
                    <div class="info-value"><?php echo htmlspecialchars($lead['estimated_area']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($lead['service_type'])): ?>
                <div class="info-row">
                    <div class="info-label">Tipo de serviço / piso:</div>
                    <div class="info-value"><?php echo htmlspecialchars($lead['service_type']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($lead['main_interest'])): ?>
                <div class="info-row">
                    <div class="info-label">Interesse principal:</div>
                    <div class="info-value"><?php echo htmlspecialchars($lead['main_interest']); ?></div>
                </div>
                <?php endif; ?>
                <?php 
                $urg = $lead['urgency'] ?? '';
                $urg_label = $urg === 'imediato' ? 'Imediato' : ($urg === '30_dias' ? '30 dias' : ($urg === '60_mais' ? '60+ dias' : '—'));
                if ($urg): ?>
                <div class="info-row">
                    <div class="info-label">Prazo / Urgência:</div>
                    <div class="info-value"><?php echo $urg_label; ?></div>
                </div>
                <?php endif; ?>
                <?php if (isset($lead['budget_estimated']) && $lead['budget_estimated'] !== '' && $lead['budget_estimated'] !== null): ?>
                <div class="info-row">
                    <div class="info-label">Orçamento estimado:</div>
                    <div class="info-value"><?php echo htmlspecialchars(is_numeric($lead['budget_estimated']) ? number_format((float)$lead['budget_estimated'], 0, ',', '.') : $lead['budget_estimated']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (isset($lead['is_decision_maker']) && $lead['is_decision_maker'] !== '' && $lead['is_decision_maker'] !== null): ?>
                <div class="info-row">
                    <div class="info-label">Decisor:</div>
                    <div class="info-value"><?php echo $lead['is_decision_maker'] ? 'Sim' : 'Não'; ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($lead['payment_type'])): ?>
                <div class="info-row">
                    <div class="info-label">Forma de pagamento:</div>
                    <div class="info-value"><?php echo $lead['payment_type'] === 'cash' ? 'À vista' : 'Financiamento'; ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($lead['disqualification_reason'])): ?>
                <div class="info-row" style="background: #fef2f2; padding: 10px; border-radius: 6px;">
                    <div class="info-label">Motivo desqualificação:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($lead['disqualification_reason'])); ?></div>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <div class="info-label">IP:</div>
                    <div class="info-value"><?php echo htmlspecialchars($lead['ip_address'] ?? '—'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Atualizado em:</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($lead['updated_at'])); ?></div>
                </div>
            </div>
            <div class="info-card" style="background: #f8fafc; border-left: 4px solid #6366f1;">
                <h2>⚙️ Automação</h2>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">Ao salvar a qualificação, o lead recebe:</p>
                <ul style="margin: 0 0 12px 0; padding-left: 20px; font-size: 13px; color: #475569;">
                    <li><strong>Score</strong> (calculado pelos dados)</li>
                    <li><strong>Tags automáticas</strong> (se config/lead-logic.php existir)</li>
                </ul>
                <?php if (isset($lead['lead_score']) && $lead['lead_score'] !== ''): ?>
                <div class="info-row">
                    <div class="info-label">Score atual:</div>
                    <div class="info-value"><strong><?php echo (int)$lead['lead_score']; ?></strong> / 100</div>
                </div>
                <?php endif; ?>
                <p style="font-size: 12px; color: #64748b; margin: 12px 0 0 0;">Status pode mudar para <strong>Qualificado</strong> ou <strong>Desqualificado</strong> (com motivo obrigatório). Se desqualificado, lead entra em nutrição/arquivamento.</p>
            </div>
        </div>
        <!-- Coluna direita: Formulário -->
        <div class="qual-col-right">
            <div class="info-card">
                <h2>🧾 Perguntas-chave / Registro obrigatório</h2>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Preencha os dados da qualificação. Depois marque como Qualificado ou Desqualificado.</p>
                <form method="POST" action="?module=lead-detail&id=<?php echo $lead_id; ?>#qualificacao" id="form-qualificacao">
                    <input type="hidden" name="action" value="update_qualification">
                    <div class="qual-grid">
                        <div class="form-group">
                            <label>Tipo de imóvel</label>
                            <select name="property_type">
                                <option value="">— Selecione —</option>
                                <option value="casa" <?php echo ($lead['property_type'] ?? '') === 'casa' ? 'selected' : ''; ?>>Residencial (Casa)</option>
                                <option value="apartamento" <?php echo ($lead['property_type'] ?? '') === 'apartamento' ? 'selected' : ''; ?>>Residencial (Apartamento)</option>
                                <option value="comercial" <?php echo ($lead['property_type'] ?? '') === 'comercial' ? 'selected' : ''; ?>>Comercial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Metragem estimada</label>
                            <input type="text" name="estimated_area" value="<?php echo htmlspecialchars($lead['estimated_area'] ?? ''); ?>" placeholder="Ex: 50 m²">
                        </div>
                        <div class="form-group">
                            <label>Tipo de serviço / piso desejado</label>
                            <select name="service_type">
                                <option value="">— Selecione —</option>
                                <option value="Instalação" <?php echo ($lead['service_type'] ?? '') === 'Instalação' ? 'selected' : ''; ?>>Instalação</option>
                                <option value="Refinishing" <?php echo ($lead['service_type'] ?? '') === 'Refinishing' ? 'selected' : ''; ?>>Refinishing</option>
                                <option value="Reparo" <?php echo ($lead['service_type'] ?? '') === 'Reparo' ? 'selected' : ''; ?>>Reparo</option>
                                <option value="Vinyl" <?php echo ($lead['service_type'] ?? '') === 'Vinyl' ? 'selected' : ''; ?>>Vinyl</option>
                                <option value="Hardwood" <?php echo ($lead['service_type'] ?? '') === 'Hardwood' ? 'selected' : ''; ?>>Hardwood</option>
                                <option value="Tile" <?php echo ($lead['service_type'] ?? '') === 'Tile' ? 'selected' : ''; ?>>Tile</option>
                                <option value="Carpet" <?php echo ($lead['service_type'] ?? '') === 'Carpet' ? 'selected' : ''; ?>>Carpet</option>
                                <option value="Laminate" <?php echo ($lead['service_type'] ?? '') === 'Laminate' ? 'selected' : ''; ?>>Laminate</option>
                                <option value="Outro" <?php echo (!empty($lead['service_type']) && !in_array($lead['service_type'], ['Instalação','Refinishing','Reparo','Vinyl','Hardwood','Tile','Carpet','Laminate'])) ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prazo / Urgência</label>
                            <select name="urgency">
                                <option value="">— Selecione —</option>
                                <option value="imediato" <?php echo ($lead['urgency'] ?? '') === 'imediato' ? 'selected' : ''; ?>>Imediato</option>
                                <option value="30_dias" <?php echo ($lead['urgency'] ?? '') === '30_dias' ? 'selected' : ''; ?>>30 dias</option>
                                <option value="60_mais" <?php echo ($lead['urgency'] ?? '') === '60_mais' ? 'selected' : ''; ?>>60+ dias</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Orçamento estimado</label>
                            <input type="text" name="budget_estimated" value="<?php echo htmlspecialchars($lead['budget_estimated'] ?? ''); ?>" placeholder="Ex: 5000">
                        </div>
                        <div class="form-group">
                            <label>Decisor (Sim / Não)</label>
                            <select name="is_decision_maker">
                                <option value="">— Selecione —</option>
                                <option value="1" <?php echo isset($lead['is_decision_maker']) && $lead['is_decision_maker'] ? 'selected' : ''; ?>>Sim</option>
                                <option value="0" <?php echo isset($lead['is_decision_maker']) && $lead['is_decision_maker'] === '0' ? 'selected' : ''; ?>>Não</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Forma de pagamento</label>
                            <select name="payment_type">
                                <option value="">— Selecione —</option>
                                <option value="cash" <?php echo ($lead['payment_type'] ?? '') === 'cash' ? 'selected' : ''; ?>>À vista</option>
                                <option value="financing" <?php echo ($lead['payment_type'] ?? '') === 'financing' ? 'selected' : ''; ?>>Financiamento</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Concorrência?</label>
                            <select name="has_competition">
                                <option value="">—</option>
                                <option value="1" <?php echo isset($lead['has_competition']) && $lead['has_competition'] ? 'selected' : ''; ?>>Sim</option>
                                <option value="0" <?php echo isset($lead['has_competition']) && $lead['has_competition'] === '0' ? 'selected' : ''; ?>>Não</option>
                            </select>
                        </div>
                    </div>
                    <div class="qual-actions" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="btn btn-primary">Salvar qualificação</button>
                    </div>
                </form>
                <h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 15px;">❌ Definir resultado</h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">Após preencher os dados, marque o lead como <strong>Qualificado</strong> ou <strong>Desqualificado</strong>. Se desqualificado, o motivo é obrigatório (lead entra em nutrição/arquivamento).</p>
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start;">
                    <form method="POST" action="?module=lead-detail&id=<?php echo $lead_id; ?>#qualificacao" style="display: inline;">
                        <input type="hidden" name="action" value="update_qualification">
                        <input type="hidden" name="status" value="qualified">
                        <button type="submit" class="btn btn-primary" style="background: #059669;">✓ Marcar como Qualificado</button>
                    </form>
                    <form method="POST" action="?module=lead-detail&id=<?php echo $lead_id; ?>#qualificacao" style="display: inline;" id="form-desqualificar" onsubmit="return !!document.getElementById('motivo-desqualificar').value.trim();">
                        <input type="hidden" name="action" value="update_qualification">
                        <input type="hidden" name="status" value="closed_lost">
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end;">
                            <div class="form-group" style="margin: 0; min-width: 220px;">
                                <label>Motivo da desqualificação (obrigatório)</label>
                                <input type="text" name="disqualification_reason" id="motivo-desqualificar" placeholder="Ex: Orçamento incompatível, sem interesse" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                            </div>
                            <button type="submit" class="btn btn-secondary" style="background: #dc2626; color: #fff; border: none;">✕ Marcar como Desqualificado</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
        <style>
        .qualificacao-layout { display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px; }
        @media (max-width: 900px) { .qualificacao-layout { grid-template-columns: 1fr; } }
        .qual-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
        @media (max-width: 600px) { .qual-grid { grid-template-columns: 1fr; } }
        .qual-grid .form-group label { font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px; }
        .qual-grid .form-group input, .qual-grid .form-group select { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        </style>
    </div>
    <!-- Fim painel Qualificação -->
    
    <!-- Painel Interações -->
    <div class="tab-panel" id="panel-interacoes" role="tabpanel" aria-labelledby="tab-interacoes" aria-hidden="true">
    <div class="info-card notes-section">
        <h2>Histórico de conversas e contatos</h2>
        
        <?php
        $has_activities_table = false;
        $has_lead_notes_table = false;
        try {
            if (isDatabaseConfigured()) {
                $pdo_hist = getDBConnection();
                if ($pdo_hist) {
                    $has_activities_table = $pdo_hist->query("SHOW TABLES LIKE 'activities'")->rowCount() > 0;
                    $has_lead_notes_table = $pdo_hist->query("SHOW TABLES LIKE 'lead_notes'")->rowCount() > 0;
                }
            }
        } catch (Exception $e) {}
        $can_log_contact = $has_activities_table || $has_lead_notes_table;
        ?>
        
        <?php if ($can_log_contact): ?>
        <form method="POST" action="?module=lead-detail&id=<?php echo $lead_id; ?>#interacoes" style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="action" value="add_activity">
            <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 16px;">Registrar contato</h3>
            <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label>Data e hora do contato:</label>
                    <input type="datetime-local" name="activity_datetime" value="<?php echo date('Y-m-d\TH:i'); ?>" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label>Tipo de contato:</label>
                    <select name="activity_type" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                        <option value="phone_call">Ligação</option>
                        <option value="email_sent">E-mail enviado</option>
                        <option value="whatsapp_message">WhatsApp</option>
                        <option value="meeting_scheduled">Reunião agendada / realizada</option>
                        <option value="note">Observação / anotação</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
            </div>
            <style>.form-row-datetime-type{display:grid;grid-template-columns:1fr 1fr;gap:12px;}@media(max-width:600px){.form-row-datetime-type{grid-template-columns:1fr;}}</style>
            <div class="form-group">
                <label>Assunto / resumo (opcional):</label>
                <input type="text" name="activity_subject" placeholder="Ex: Retorno da ligação" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
            </div>
            <div class="form-group">
                <label>Detalhes:</label>
                <textarea name="activity_description" class="textarea" placeholder="Descreva o que foi tratado no contato..." style="min-height: 80px;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Registrar contato</button>
        </form>
        <?php else: ?>
        <p style="margin-bottom: 20px; padding: 12px; background: #fef3c7; border-radius: 8px; color: #92400e; font-size: 14px;">
            Para registrar contatos é preciso ter a tabela <code>lead_notes</code> ou <code>activities</code>. Execute no phpMyAdmin o arquivo <strong>database/schema-v3-completo.sql</strong> (ou <strong>database/migration-lead-owner-and-activities.sql</strong>) para criar as tabelas. Depois atualize esta página.
        </p>
        <?php endif; ?>
        
        <h3 style="margin-bottom: 12px; font-size: 16px;">Adicionar observação interna</h3>
        <form method="POST" action="?module=lead-detail&id=<?php echo (int)$lead_id; ?>#interacoes" style="margin-bottom: 24px;">
            <input type="hidden" name="action" value="add_note">
            <div class="form-group">
                <textarea name="note" class="textarea" placeholder="Digite uma observação sobre este lead..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Adicionar Observação</button>
        </form>
        
        <?php
        $type_labels = [
            'phone_call' => 'Ligação',
            'email_sent' => 'E-mail',
            'whatsapp_message' => 'WhatsApp',
            'meeting_scheduled' => 'Reunião',
            'note' => 'Observação',
            'assignment' => 'Encaminhamento',
            'other' => 'Outro'
        ];
        $timeline = [];
        foreach ($notes as $n) {
            $timeline[] = ['type' => 'note', 'type_label' => 'Observação', 'date' => $n['created_at'], 'author' => $n['created_by'], 'text' => $n['note'], 'subject' => null];
        }
        foreach ($activities as $a) {
            $timeline[] = [
                'type' => $a['activity_type'],
                'type_label' => $type_labels[$a['activity_type']] ?? $a['activity_type'],
                'date' => $a['activity_date'] ?? $a['created_at'],
                'author' => null,
                'text' => trim(($a['subject'] ?? '') . "\n" . ($a['description'] ?? '')),
                'subject' => $a['subject'] ?? null
            ];
        }
        usort($timeline, function ($a, $b) { return strcmp($b['date'], $a['date']); });
        ?>
        
        <h3 style="margin-bottom: 12px; font-size: 16px;">Linha do tempo</h3>
        <?php if (empty($timeline)): ?>
            <p style="color: #718096; font-style: italic;">Nenhum registro ainda. Adicione uma observação ou registre um contato acima.</p>
        <?php else: ?>
            <?php foreach ($timeline as $item): ?>
                <div class="note-item" style="<?php echo ($item['type'] ?? '') === 'assignment' ? 'border-left-color: #6366f1;' : ''; ?>">
                    <div class="note-header">
                        <span class="note-author">
                            <?php if (!empty($item['type_label'])): ?>
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; background: #e2e8f0; color: #475569; font-size: 11px; font-weight: 600; margin-right: 8px;"><?php echo htmlspecialchars($item['type_label']); ?></span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($item['author'] ?? 'Sistema'); ?>
                        </span>
                        <span class="note-date"><?php echo date('d/m/Y H:i', strtotime($item['date'])); ?></span>
                    </div>
                    <div class="note-text"><?php echo nl2br(htmlspecialchars(trim($item['text']))); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="info-card notes-section" style="margin-top: 24px;">
        <h2>Tags</h2>
        <?php if (empty($tags)): ?>
            <p style="color: #718096; font-style: italic; margin-bottom: 15px;">Nenhuma tag adicionada ainda.</p>
        <?php else: ?>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <?php foreach ($tags as $tag_item): ?>
                    <span style="background: #1a2036; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 8px;">
                        <?php echo htmlspecialchars(getTagLabel($tag_item['tag_name'] ?? $tag_item['tag'] ?? '')); ?>
                        <form method="POST" style="display: inline; margin: 0;">
                            <input type="hidden" name="action" value="remove_tag">
                            <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tag_item['tag_name'] ?? $tag_item['tag'] ?? ''); ?>">
                            <button type="submit" style="background: rgba(255,255,255,0.2); border: none; color: white; cursor: pointer; padding: 2px 6px; border-radius: 10px; font-size: 10px;">×</button>
                        </form>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="add_tag">
            <div class="form-group" style="display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label>Adicionar Tag:</label>
                    <select name="tag" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                        <option value="">Selecione uma tag...</option>
                        <?php 
                        $available_tags = getAvailableTags();
                        $current_tags = array_column($tags, 'tag_name');
                        foreach ($available_tags as $tag_key => $tag_label): 
                            if (!in_array($tag_key, $current_tags)):
                        ?>
                            <option value="<?php echo htmlspecialchars($tag_key); ?>"><?php echo htmlspecialchars($tag_label); ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Adicionar Tag</button>
            </div>
        </form>
    </div>
    </div>
    <!-- Fim painel Interações -->

    <div class="tab-panel" id="panel-visitas" role="tabpanel" aria-labelledby="tab-visitas" aria-hidden="true">
    <div class="tab-panel-inner" style="padding-top: 0;">
    <h2 class="panel-title" style="margin: 0 0 20px 0; font-size: 20px; color: #1a2036; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0;">Visitas — Lead #<?php echo (int)$lead_id; ?> <?php echo htmlspecialchars($lead['name'] ?? ''); ?></h2>
    <?php
    // Garantir detecção da tabela visits (re-check na aba caso carregamento inicial não tenha rodado)
    if (!$has_visits_table && isDatabaseConfigured()) {
        try {
            $pdo_visits = getDBConnection();
            if ($pdo_visits && $pdo_visits->query("SHOW TABLES LIKE 'visits'")->rowCount() > 0) {
                $has_visits_table = true;
                $stmt_v = $pdo_visits->prepare("SELECT id, lead_id, scheduled_at, seller_id, technician_id, address, notes, status, created_at FROM visits WHERE lead_id = ? ORDER BY scheduled_at DESC");
                $stmt_v->execute([$lead_id]);
                $lead_visits = $stmt_v->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {}
    }
    $visit_user_names = [];
    foreach ($users as $u) { $visit_user_names[(int)$u['id']] = $u['name']; }
    $visit_status_labels = ['scheduled' => 'Agendada', 'completed' => 'Realizada', 'cancelled' => 'Cancelada', 'no_show' => 'Não compareceu'];
    ?>
    <?php if (!$has_visits_table): ?>
    <div class="info-card" style="margin-bottom: 20px; background: #fffbeb; border: 1px solid #fcd34d;">
        <p style="margin: 0 0 8px 0; font-weight: 600; color: #92400e;">Para agendar visitas, crie a tabela <strong>visits</strong> no banco.</p>
        <p style="margin: 0; font-size: 13px; color: #92400e;">No phpMyAdmin execute <strong>database/migration-visits-only.sql</strong> (ou a seção &quot;4. VISITAS&quot; de migration-crm-completo.sql). Depois <a href="?module=lead-detail&id=<?php echo (int)$lead_id; ?>#visitas">atualize esta página</a>.</p>
    </div>
    <?php endif; ?>
    <?php if (!empty($_GET['visit_error'])): ?>
    <div class="info-card" style="margin-bottom: 16px; background: #fef2f2; border: 1px solid #fecaca;">
        <p style="margin: 0; color: #991b1b; font-size: 14px;">Erro ao agendar. Verifique se a tabela <strong>visits</strong> existe. Execute <strong>database/migration-visits-only.sql</strong> no phpMyAdmin.</p>
    </div>
    <?php endif; ?>
    <div class="info-card" style="margin-bottom: 24px;">
        <h2>Agendar visita (neste lead)</h2>
        <p style="color: #64748b; margin-bottom: 16px; font-size: 14px;">Escolha a data/hora e o usuário que fará a visita. A visita será adicionada à agenda.</p>
        <form method="POST" action="?module=lead-detail&id=<?php echo (int)$lead_id; ?>#visitas"<?php if (!$has_visits_table): ?> style="opacity: 0.85;"<?php endif; ?>>
            <input type="hidden" name="action" value="schedule_visit">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Data e hora</label>
                    <input type="datetime-local" name="scheduled_at" required value="<?php echo date('Y-m-d\TH:i', strtotime('+1 day')); ?>" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;"<?php if (!$has_visits_table): ?> disabled<?php endif; ?>>
                </div>
                <div class="form-group">
                    <label>Quem fará a visita</label>
                    <select name="seller_id" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;"<?php if (!$has_visits_table): ?> disabled<?php endif; ?>>
                        <option value="">— Selecione o usuário —</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?><?php echo !empty($u['email']) ? ' (' . htmlspecialchars($u['email']) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php if (empty($users) && $has_visits_table): ?>
            <p style="font-size: 13px; color: #dc2626; margin-bottom: 12px;">Cadastre usuários em <strong>Users</strong> para atribuir quem fará a visita.</p>
            <?php endif; ?>
            <div class="form-group" style="margin-top: 12px;">
                <label>Endereço (opcional)</label>
                <input type="text" name="address" placeholder="Endereço da visita" value="<?php echo htmlspecialchars($lead['address'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;"<?php if (!$has_visits_table): ?> disabled<?php endif; ?>>
            </div>
            <div class="form-group">
                <label>Observações (opcional)</label>
                <textarea name="notes" rows="2" placeholder="Checklist, observações" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; box-sizing: border-box;"<?php if (!$has_visits_table): ?> disabled<?php endif; ?>></textarea>
            </div>
            <button type="submit" class="btn btn-primary"<?php if (!$has_visits_table): ?> disabled title="Crie a tabela visits no banco para habilitar"<?php endif; ?>>Adicionar à agenda</button>
        </form>
    </div>
    <div class="info-card">
        <h2>Visitas deste lead</h2>
        <?php if (empty($lead_visits)): ?>
        <p style="color: #64748b;"><?php echo $has_visits_table ? 'Nenhuma visita agendada ainda. Use o formulário acima para agendar.' : 'Nenhuma visita (crie a tabela visits no banco para habilitar o agendamento).'; ?></p>
        <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f7f8fc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 10px 12px; text-align: left; font-size: 12px; color: #4a5568;">Data / Hora</th>
                    <th style="padding: 10px 12px; text-align: left; font-size: 12px; color: #4a5568;">Responsável</th>
                    <th style="padding: 10px 12px; text-align: left; font-size: 12px; color: #4a5568;">Status</th>
                    <th style="padding: 10px 12px; text-align: left; font-size: 12px; color: #4a5568;">Endereço</th>
                    <th style="padding: 10px 12px; text-align: left; font-size: 12px; color: #4a5568;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lead_visits as $v): ?>
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="padding: 12px;"><?php echo date('d/m/Y H:i', strtotime($v['scheduled_at'])); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($visit_user_names[(int)($v['seller_id'] ?? 0)] ?? '—'); ?></td>
                    <td style="padding: 12px;"><span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: <?php echo ($v['status'] ?? '') === 'completed' ? '#dcfce7' : (($v['status'] ?? '') === 'cancelled' ? '#fee2e2' : '#dbeafe'); ?>; color: <?php echo ($v['status'] ?? '') === 'completed' ? '#166534' : (($v['status'] ?? '') === 'cancelled' ? '#991b1b' : '#1d4ed8'); ?>;"><?php echo $visit_status_labels[$v['status'] ?? 'scheduled'] ?? $v['status']; ?></span></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars(mb_substr($v['address'] ?? '', 0, 50)); ?><?php echo mb_strlen($v['address'] ?? '') > 50 ? '…' : ''; ?></td>
                    <td style="padding: 12px;"><a href="?module=visit-detail&id=<?php echo (int)$v['id']; ?>" class="link" style="font-size: 13px;">Ver / Medição</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <p style="margin-top: 16px; font-size: 12px; color: #64748b;"><a href="?module=visits&lead_id=<?php echo (int)$lead_id; ?>" class="link">Ver agenda completa de visitas →</a></p>
    </div>
    </div>
    </div>

    <div class="tab-panel" id="panel-propostas" role="tabpanel" aria-labelledby="tab-propostas" aria-hidden="true">
    <div class="info-card">
        <h2>Propostas / Orçamentos</h2>
        <p style="color: #64748b; margin-bottom: 12px;">Crie e envie propostas para este lead.</p>
        <a href="?module=quotes&lead_id=<?php echo (int)$lead_id; ?>" class="btn btn-primary">Ver orçamentos</a>
    </div>
    </div>

    <div class="tab-panel" id="panel-contrato">
    <div class="info-card">
        <h2>Contrato</h2>
        <p style="color: #64748b; margin-bottom: 12px;">Gestão de contrato após fechamento.</p>
        <a href="?module=crm&id=<?php echo (int)$lead_id; ?>#contrato" class="btn btn-primary">Ver detalhes do lead</a>
    </div>
    </div>

    <div class="tab-panel" id="panel-producao" role="tabpanel" aria-labelledby="tab-producao" aria-hidden="true">
    <div class="info-card">
        <h2>Produção / Obra</h2>
        <p style="color: #64748b; margin-bottom: 12px;">Status da obra e cronograma.</p>
        <a href="?module=projects" class="btn btn-primary">Ver projetos</a>
    </div>
    </div>
</div>

<script>
(function() {
    function openTabFromHash() {
        var container = document.getElementById('lead-detail-tabs-root');
        if (!container) return;
        var hash = (location.hash || '').replace(/^#/, '').trim();
        if (hash && container.querySelector('#panel-' + hash)) {
            if (typeof window.leadDetailShowPanel === 'function') {
                window.leadDetailShowPanel(hash);
                try {
                    var panel = container.querySelector('#panel-' + hash);
                    if (panel && panel.classList.contains('active') && panel.scrollIntoView) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } catch (e) {}
            }
        } else if (typeof window.leadDetailShowPanel === 'function') {
            window.leadDetailShowPanel('resumo');
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { openTabFromHash(); setTimeout(openTabFromHash, 50); });
    } else {
        openTabFromHash();
        setTimeout(openTabFromHash, 50);
    }
    window.addEventListener('load', function() { openTabFromHash(); });
    window.addEventListener('hashchange', openTabFromHash);
})();
</script>
