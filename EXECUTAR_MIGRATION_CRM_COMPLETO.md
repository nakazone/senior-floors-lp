# Executar Migration CRM Completo (11 blocos)

Esta migration adiciona os campos e tabelas necessários para os 11 blocos do CRM (captura, qualificação, pipeline, visitas, orçamentos, contratos, pós-venda, workflows, IA).

## Pré-requisito

- Já ter executado o schema completo v3:  
  `database/schema-v3-completo.sql`

## Passos

### 1. Via phpMyAdmin ou MySQL CLI

1. Abra o arquivo `database/migration-crm-completo.sql`.
2. Se a tabela `pipeline_stages` ou colunas em `leads` já existirem, pode dar erro em algum `ALTER TABLE` ou `INSERT`. Nesse caso:
   - Ignore o erro daquele comando, ou
   - Comente no SQL a linha que adiciona a coluna que já existe.
3. Execute o conteúdo do arquivo no seu banco (Hostinger, local, etc.).

### 2. Via script PHP (opcional)

Se preferir rodar pela aplicação:

```php
<?php
require_once 'config/database.php';
if (!isDatabaseConfigured()) die('Database not configured');
$pdo = getDBConnection();
$sql = file_get_contents(__DIR__ . '/database/migration-crm-completo.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
    try {
        $pdo->exec($stmt);
        echo "OK: " . substr($stmt, 0, 60) . "...\n";
    } catch (PDOException $e) {
        echo "Skip or Error: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
```

Guarde em `executar-migration-crm.php` na raiz do projeto, execute uma vez (via browser ou CLI) e depois remova ou proteja por senha.

### 3. Verificar

- No painel: **Pipeline (Kanban)** deve aparecer no menu e carregar estágios e leads.
- Em **CRM - Leads**, ao editar um lead, devem aparecer (após a migration) campos como tipo de imóvel, tipo de serviço, orçamento estimado, urgência, decisor, etc., se a tela de edição for atualizada para incluí-los.

## Conteúdo principal da migration

- Novos campos em `leads`: address, property_type, service_type, main_interest, budget_estimated, urgency, is_decision_maker, payment_type, has_competition, lead_score, last_activity_at, pipeline_stage_id.
- Tabelas: `pipeline_stages`, `lead_distribution_rules`, `lead_distribution_state`, `visits`, `measurements`, `visit_attachments`, `quotes`, `quote_items`, `contracts`, `project_documents`, `project_issues`, `delivery_checklists`, `workflows`, `scheduled_followups`, `interaction_logs`, `tasks`.
- Seed dos estágios do pipeline (Lead recebido … Pós-venda).

## Problemas comuns

- **Duplicate column:** a coluna já existe. Comente o `ADD COLUMN` correspondente no SQL e rode de novo o restante.
- **Table 'pipeline_stages' already exists:** o `CREATE TABLE IF NOT EXISTS` não deve falhar; o `INSERT` pode dar conflito de chave. Use `INSERT IGNORE` ou ajuste o `ON DUPLICATE KEY UPDATE` conforme necessário.
- **Pipeline (Kanban) vazio:** confirme que `pipeline_stages` tem linhas e que `leads` tem a coluna `pipeline_stage_id`. Atualize os leads existentes com `UPDATE leads SET pipeline_stage_id = 1 WHERE pipeline_stage_id IS NULL;` se fizer sentido.
