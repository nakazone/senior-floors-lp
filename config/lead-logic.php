<?php
/**
 * Lógica de leads: duplicados, distribuição (round-robin), tarefa automática
 * Senior Floors CRM
 */

require_once __DIR__ . '/database.php';

/**
 * Verifica se já existe lead com mesmo email ou telefone (evitar duplicados)
 * @param PDO $pdo
 * @param string $email
 * @param string $phone
 * @param int $exclude_lead_id Ignorar este ID (para updates)
 * @return array ['is_duplicate' => bool, 'existing_id' => int|null]
 */
function checkDuplicateLead($pdo, $email, $phone, $exclude_lead_id = null) {
    $email = trim($email);
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) < 8) {
        return ['is_duplicate' => false, 'existing_id' => null];
    }
    $sql = "SELECT id FROM leads WHERE LOWER(TRIM(email)) = LOWER(?) OR TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'(',''),')',''),'+','')) = ?";
    $params = [$email, $phone];
    if ($exclude_lead_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_lead_id;
    }
    $sql .= " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'is_duplicate' => (bool) $row,
        'existing_id' => $row ? (int) $row['id'] : null
    ];
}

/**
 * Retorna o próximo usuário para round-robin (vendedores ativos)
 * @param PDO $pdo
 * @return int|null user_id ou null se não houver usuários
 */
function getNextOwnerRoundRobin($pdo) {
    $stmt = $pdo->query("
        SELECT id FROM users 
        WHERE is_active = 1 AND role IN ('admin', 'sales_rep', 'project_manager') 
        ORDER BY id
    ");
    $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($user_ids)) {
        return null;
    }
    // Só usa round-robin por owner_id se a coluna existir na tabela leads
    $has_owner_col = false;
    try {
        $check = $pdo->query("SHOW COLUMNS FROM leads LIKE 'owner_id'");
        $has_owner_col = $check && $check->rowCount() > 0;
    } catch (Throwable $e) {
        // ignora
    }
    if (!$has_owner_col) {
        return (int) $user_ids[0];
    }
    // Último atribuído (último lead.owner_id)
    $stmt = $pdo->query("
        SELECT owner_id FROM leads 
        WHERE owner_id IS NOT NULL 
        ORDER BY created_at DESC LIMIT 1
    ");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_id = $last ? (int) $last['owner_id'] : null;
    $idx = $last_id ? array_search($last_id, $user_ids) : -1;
    $next_idx = ($idx + 1) % count($user_ids);
    return (int) $user_ids[$next_idx];
}

/**
 * Cria tarefa automática ao entrar lead (ex: "Contatar lead em 24h")
 * @param PDO $pdo
 * @param int $lead_id
 * @param int|null $assigned_to
 */
function createLeadEntryTask($pdo, $lead_id, $assigned_to = null) {
    $due = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $stmt = $pdo->prepare("
        INSERT INTO tasks (lead_id, title, description, due_at, assigned_to, created_by)
        VALUES (?, 'Contatar lead', 'Primeiro contato com o lead (entrada automática)', ?, ?, NULL)
    ");
    $stmt->execute([$lead_id, $due, $assigned_to]);
}

/**
 * Calcula score simples do lead (0-100) com base em qualificação
 * @param array $lead Row com budget_estimated, urgency, is_decision_maker, payment_type, has_competition
 * @return int
 */
function calculateLeadScore($lead) {
    $score = 0;
    if (!empty($lead['budget_estimated']) && (float)$lead['budget_estimated'] >= 5000) $score += 25;
    elseif (!empty($lead['budget_estimated'])) $score += 10;
    if (isset($lead['urgency']) && $lead['urgency'] === 'imediato') $score += 25;
    elseif (isset($lead['urgency']) && $lead['urgency'] === '30_dias') $score += 15;
    if (!empty($lead['is_decision_maker'])) $score += 20;
    if (isset($lead['payment_type']) && $lead['payment_type'] === 'cash') $score += 15;
    if (isset($lead['has_competition']) && !$lead['has_competition']) $score += 15;
    return min(100, $score);
}

/**
 * Aplica tags automáticas ao lead (High Ticket, Commercial, Urgent)
 * @param PDO $pdo
 * @param int $lead_id
 * @param array $lead Row com budget_estimated, property_type, urgency
 */
function applyAutoTags($pdo, $lead_id, $lead) {
    $tags = [];
    if (!empty($lead['budget_estimated']) && (float)$lead['budget_estimated'] >= 10000) {
        $tags[] = 'High Ticket';
    }
    if (isset($lead['property_type']) && $lead['property_type'] === 'comercial') {
        $tags[] = 'Commercial';
    }
    if (isset($lead['urgency']) && $lead['urgency'] === 'imediato') {
        $tags[] = 'Urgent';
    }
    $tag_col = 'tag_name';
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM lead_tags LIKE 'tag_name'");
        if (!$chk || $chk->rowCount() === 0) $tag_col = 'tag';
    } catch (Throwable $e) { $tag_col = 'tag'; }
    foreach ($tags as $tag) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO lead_tags (lead_id, $tag_col) VALUES (?, ?)");
        $stmt->execute([$lead_id, $tag]);
    }
}
