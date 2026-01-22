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
                // Buscar observações
                $stmt = $pdo->prepare("
                    SELECT id, note, created_by, created_at
                    FROM lead_notes
                    WHERE lead_id = :lead_id
                    ORDER BY created_at DESC
                ");
                $stmt->execute([':lead_id' => $lead_id]);
                $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Buscar tags
                $stmt = $pdo->prepare("
                    SELECT id, tag, created_at
                    FROM lead_tags
                    WHERE lead_id = :lead_id
                    ORDER BY created_at DESC
                ");
                $stmt->execute([':lead_id' => $lead_id]);
                $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    
    if ($_POST['action'] === 'add_note') {
        $api_url = '../api/leads/notes.php';
        $post_data = [
            'lead_id' => $lead_id,
            'note' => $_POST['note'],
            'created_by' => $_SESSION['admin_name'] ?? 'admin'
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Recarregar página após adicionar observação
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
</style>

<div class="lead-detail-container">
    <div class="lead-header">
        <h1>Lead #<?php echo htmlspecialchars($lead['id']); ?></h1>
        <a href="?module=crm" class="back-link">← Voltar para CRM</a>
    </div>
    
    <div class="lead-info-grid">
        <!-- Informações do Lead -->
        <div class="info-card">
            <h2>Informações do Lead</h2>
            
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['name']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">
                    <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" style="color: #1a2036;">
                        <?php echo htmlspecialchars($lead['email']); ?>
                    </a>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value">
                    <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" style="color: #1a2036;">
                        <?php echo htmlspecialchars($lead['phone']); ?>
                    </a>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">CEP:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['zipcode'] ?? 'N/A'); ?></div>
            </div>
            
            <?php if (!empty($lead['message'])): ?>
            <div class="info-row">
                <div class="info-label">Mensagem:</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($lead['message'])); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="info-row">
                <div class="info-label">Origem:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['source'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Formulário:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['form_type'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">IP:</div>
                <div class="info-value"><?php echo htmlspecialchars($lead['ip_address'] ?? 'N/A'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Criado em:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($lead['created_at'])); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Atualizado em:</div>
                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($lead['updated_at'])); ?></div>
            </div>
        </div>
        
        <!-- Status e Ações -->
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
        </div>
    </div>
    
    <!-- Tags -->
    <div class="info-card notes-section">
        <h2>Tags</h2>
        
        <div style="margin-bottom: 20px;">
            <?php if (empty($tags)): ?>
                <p style="color: #718096; font-style: italic; margin-bottom: 15px;">Nenhuma tag adicionada ainda.</p>
            <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                    <?php foreach ($tags as $tag_item): ?>
                        <span style="background: #1a2036; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 8px;">
                            <?php echo htmlspecialchars(getTagLabel($tag_item['tag'])); ?>
                            <form method="POST" style="display: inline; margin: 0;">
                                <input type="hidden" name="action" value="remove_tag">
                                <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tag_item['tag']); ?>">
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
                        <select name="tag" class="form-group select" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                            <option value="">Selecione uma tag...</option>
                            <?php 
                            $available_tags = getAvailableTags();
                            $current_tags = array_column($tags, 'tag');
                            foreach ($available_tags as $tag_key => $tag_label): 
                                if (!in_array($tag_key, $current_tags)):
                            ?>
                                <option value="<?php echo htmlspecialchars($tag_key); ?>"><?php echo htmlspecialchars($tag_label); ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Adicionar Tag</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Observações -->
    <div class="info-card notes-section">
        <h2>Observações Internas</h2>
        
        <form method="POST" style="margin-bottom: 30px;">
            <input type="hidden" name="action" value="add_note">
            <div class="form-group">
                <label>Adicionar Observação:</label>
                <textarea name="note" class="textarea" placeholder="Digite uma observação sobre este lead..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Adicionar Observação</button>
        </form>
        
        <?php if (empty($notes)): ?>
            <p style="color: #718096; font-style: italic;">Nenhuma observação ainda.</p>
        <?php else: ?>
            <?php foreach ($notes as $note): ?>
                <div class="note-item">
                    <div class="note-header">
                        <span class="note-author"><?php echo htmlspecialchars($note['created_by']); ?></span>
                        <span class="note-date"><?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?></span>
                    </div>
                    <div class="note-text"><?php echo nl2br(htmlspecialchars($note['note'])); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
