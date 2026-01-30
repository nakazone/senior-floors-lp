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
$activities = [];
$error = null;

if (isDatabaseConfigured()) {
    try {
        $pdo = getDBConnection();
        
        if ($pdo) {
            // Buscar lead
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id");
            $stmt->execute([':id' => $lead_id]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
        header('Location: ?module=lead-detail&id=' . $lead_id);
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
        $valid_types = ['email_sent', 'whatsapp_message', 'phone_call', 'meeting_scheduled', 'site_visit', 'proposal_sent', 'note', 'status_change', 'assignment', 'other'];
        $type_labels_short = ['phone_call' => 'Ligação', 'email_sent' => 'E-mail', 'whatsapp_message' => 'WhatsApp', 'meeting_scheduled' => 'Reunião', 'note' => 'Observação', 'other' => 'Contato'];
        $saved = false;
        if (in_array($activity_type, $valid_types) && isDatabaseConfigured()) {
            try {
                $pdo_act = getDBConnection();
                if ($pdo_act && $pdo_act->query("SHOW TABLES LIKE 'activities'")->rowCount() > 0) {
                    $stmt = $pdo_act->prepare("
                        INSERT INTO activities (lead_id, activity_type, subject, description, activity_date, user_id, related_to)
                        VALUES (?, ?, ?, ?, NOW(), ?, 'lead')
                    ");
                    $stmt->execute([$lead_id, $activity_type, $subject ?: null, $description ?: null, $_SESSION['admin_user_id'] ?? null]);
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
        header('Location: ?module=lead-detail&id=' . $lead_id);
        exit;
    }
    
    if ($_POST['action'] === 'update_qualification') {
        $api_url = '../api/leads/update.php';
        $post_data = [
            'lead_id' => $lead_id,
            'budget_estimated' => isset($_POST['budget_estimated']) ? $_POST['budget_estimated'] : '',
            'urgency' => isset($_POST['urgency']) ? $_POST['urgency'] : '',
            'is_decision_maker' => isset($_POST['is_decision_maker']) ? $_POST['is_decision_maker'] : '',
            'payment_type' => isset($_POST['payment_type']) ? $_POST['payment_type'] : '',
            'has_competition' => isset($_POST['has_competition']) ? $_POST['has_competition'] : ''
        ];
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
        header('Location: ?module=lead-detail&id=' . $lead_id);
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

<div class="lead-detail-container">
    <div class="lead-header">
        <h1>Lead #<?php echo htmlspecialchars($lead['id']); ?></h1>
        <a href="?module=crm" class="back-link">← Voltar para CRM</a>
    </div>

    <nav class="lead-tabs" role="tablist" id="lead-tablist">
        <button type="button" class="lead-tab active" data-tab="resumo" role="tab" aria-selected="true" aria-controls="panel-resumo" id="tab-resumo">Resumo</button>
        <button type="button" class="lead-tab" data-tab="qualificacao" role="tab" aria-selected="false" aria-controls="panel-qualificacao" id="tab-qualificacao">Qualificação</button>
        <button type="button" class="lead-tab" data-tab="interacoes" role="tab" aria-selected="false" aria-controls="panel-interacoes" id="tab-interacoes">Interações</button>
        <button type="button" class="lead-tab" data-tab="visitas" role="tab" aria-selected="false" aria-controls="panel-visitas" id="tab-visitas">Visitas</button>
        <button type="button" class="lead-tab" data-tab="propostas" role="tab" aria-selected="false" aria-controls="panel-propostas" id="tab-propostas">Propostas</button>
        <button type="button" class="lead-tab" data-tab="contrato" role="tab" aria-selected="false" aria-controls="panel-contrato" id="tab-contrato">Contrato</button>
        <button type="button" class="lead-tab" data-tab="producao" role="tab" aria-selected="false" aria-controls="panel-producao" id="tab-producao">Produção</button>
    </nav>
    
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
        <div class="lead-info-grid">
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
            <?php if (!empty($lead['property_type'])): ?>
            <div class="info-row">
                <div class="info-label">Tipo imóvel:</div>
                <div class="info-value"><?php echo $lead['property_type'] === 'casa' ? 'Casa' : ($lead['property_type'] === 'apartamento' ? 'Apartamento' : 'Comercial'); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($lead['service_type'])): ?>
            <div class="info-row">
                <div class="info-label">Tipo serviço:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['service_type']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($lead['main_interest'])): ?>
            <div class="info-row">
                <div class="info-label">Interesse principal:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['main_interest']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (isset($lead['lead_score']) && $lead['lead_score'] > 0): ?>
            <div class="info-row">
                <div class="info-label">Score:</div>
                <div class="info-value"><strong><?php echo (int)$lead['lead_score']; ?></strong> / 100</div>
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
        <div class="info-card">
            <h2>Qualificação (atualizar)</h2>
            <form method="POST" action="?module=lead-detail&id=<?php echo $lead_id; ?>">
                <input type="hidden" name="action" value="update_qualification">
                <div class="form-group">
                    <label>Orçamento estimado ($)</label>
                    <input type="text" name="budget_estimated" value="<?php echo htmlspecialchars($lead['budget_estimated'] ?? ''); ?>" placeholder="Ex: 5000">
                </div>
                <div class="form-group">
                    <label>Urgência</label>
                    <select name="urgency">
                        <option value="">—</option>
                        <option value="imediato" <?php echo ($lead['urgency'] ?? '') === 'imediato' ? 'selected' : ''; ?>>Imediato</option>
                        <option value="30_dias" <?php echo ($lead['urgency'] ?? '') === '30_dias' ? 'selected' : ''; ?>>30 dias</option>
                        <option value="60_mais" <?php echo ($lead['urgency'] ?? '') === '60_mais' ? 'selected' : ''; ?>>60+ dias</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Decisor?</label>
                    <select name="is_decision_maker">
                        <option value="">—</option>
                        <option value="1" <?php echo isset($lead['is_decision_maker']) && $lead['is_decision_maker'] ? 'selected' : ''; ?>>Sim</option>
                        <option value="0" <?php echo isset($lead['is_decision_maker']) && $lead['is_decision_maker'] === '0' ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo pagamento</label>
                    <select name="payment_type">
                        <option value="">—</option>
                        <option value="cash" <?php echo ($lead['payment_type'] ?? '') === 'cash' ? 'selected' : ''; ?>>À vista (Cash)</option>
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
                <button type="submit" class="btn btn-primary">Salvar qualificação</button>
            </form>
        </div>
        </div>
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
        <form method="POST" action="?module=lead-detail&id=<?php echo $lead_id; ?>" style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="action" value="add_activity">
            <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 16px;">Registrar contato</h3>
            <div class="form-group">
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
        <form method="POST" action="?module=lead-detail&id=<?php echo (int)$lead_id; ?>" style="margin-bottom: 24px;">
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
    <div class="info-card">
        <h2>Visitas / Medições</h2>
        <p style="color: #64748b; margin-bottom: 12px;">Agende visitas e registre medições para este lead.</p>
        <a href="?module=visits&lead_id=<?php echo (int)$lead_id; ?>" class="btn btn-primary">Ver agenda de visitas</a>
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
    var container = document.querySelector('.lead-detail-container');
    if (!container) return;

    function showPanel(tabId) {
        tabId = String(tabId || '').replace(/^#/, '').replace(/^panel-/, '') || 'resumo';
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
    }

    container.addEventListener('click', function(e) {
        var target = e.target;
        if (target.getAttribute && target.getAttribute('data-tab')) {
            e.preventDefault();
            e.stopPropagation();
            showPanel(target.getAttribute('data-tab'));
            return false;
        }
    }, true);

    var hash = (location.hash || '').replace('#', '');
    if (hash && container.querySelector('#panel-' + hash)) {
        showPanel(hash);
    } else {
        showPanel('resumo');
    }
})();
</script>
