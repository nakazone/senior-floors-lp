# Senior Floors CRM - Especificação e Implementação

Documento de referência: modelo de dados, pipeline, telas, permissões, automações e auditoria.

---

## 1. Modelo de Dados (Banco)

### Entidades e mapeamento

| Especificação | Implementação | Observação |
|---------------|---------------|------------|
| **users** (id, name, email, role, active) | `users` (id, name, email, role, is_active, phone, password_hash) | role: admin, sales_rep=sales, project_manager=manager, support=operational |
| **leads** (owner_id, status, source, ...) | `leads` + `pipeline_stage_id` | status legado + pipeline_stage_id para Kanban |
| **lead_qualification** | `lead_qualification` (nova) + campos em `leads` | property_type, service_type, estimated_area/budget, urgency, decision_maker, payment_type, score |
| **interactions** (type: call, whatsapp, email, visit) | `interactions` (nova) + `activities` (existente) | activities já tem activity_type; interactions alinhado à spec |
| **visits** | `visits` (lead_id, scheduled_at, address, assigned_to, status) | seller_id/technician_id = assigned_to |
| **measurements** (visit_id, final_area, technical_notes, photos, risks) | `measurements` (visit_id, area_sqft, technical_notes) | Adicionar final_area, photos JSON, risks se necessário |
| **proposals** | `quotes` (lead_id, version, total_amount, status, valid_until) | quote = proposta |
| **proposal_items** | `quote_items` (quote_id, product/floor_type, quantity, unit_price, labor, margin) | |
| **contracts** | `contracts` (quote_id, signed_at, payment_type, closed_amount, start_date, end_date) | |
| **projects** (contract_id, status, assigned_team, start_date, end_date) | `projects` (customer_id, lead_id, status, owner_id, dates) | Adicionar contract_id na migration |

### Migrations

- **migration-crm-full-spec.sql**: Cria `lead_qualification`, `lead_status_change_log`, `audit_log`, `interactions`; insere/atualiza 11 estágios do pipeline.
- Executar após: schema-v3-completo + migration-crm-completo (ou migration-pipeline-only).

---

## 2. Pipeline e Regras de Status

### 11 estágios (ordem)

1. Lead Recebido  
2. Contato Realizado  
3. Qualificado  
4. Visita Agendada  
5. Medição Realizada  
6. Proposta Criada  
7. Proposta Enviada  
8. Em Negociação  
9. Fechado - Ganhou  
10. Fechado - Perdido  
11. Produção / Obra  

### Regras

- **Não pular etapas**: avançar apenas para o próximo estágio (ou anterior). Exceção: "Fechado - Perdido" pode ser escolhido de qualquer etapa.
- **Campos obrigatórios**: definidos em `config/pipeline-rules.php` por estágio (ex.: Qualificado exige property_type, service_type, estimated_budget).
- **Logs**: toda mudança de status deve ser registrada em `lead_status_change_log` (função `logLeadStatusChange()` em `config/audit.php`).

### Uso no código

- Carregar regras: `require config/pipeline-rules.php` e usar o array.
- Ao mover lead no Kanban ou ao alterar status: validar transição (ordem + campos) e chamar `logLeadStatusChange()`.

---

## 3. Telas do CRM (UI/UX)

| Tela | Módulo atual | Observação |
|------|--------------|------------|
| **Dashboard** | `admin-modules/dashboard.php` | Já existe: leads por status, gráficos, KPIs. Incluir: receita projetada, performance por vendedor, alertas de follow-up. |
| **Lista de Leads** | `admin-modules/crm.php` | Filtros por status, vendedor, fonte; busca; ações em massa (a implementar/refinar). |
| **Detalhe do Lead** | `admin-modules/lead-detail.php` | Abas: Resumo, Qualificação, Interações (timeline), Visitas/Medições, Propostas, Contrato, Produção. Botão mudança de status, notas rápidas, histórico. |
| **Agenda de Visitas** | `admin-modules/visits.php` | Lista/calendário; checklists e confirmação (a implementar). |
| **Criação de Proposta** | `admin-modules/quotes.php` + `quote-detail.php` | Editor de itens, total/margem, preview PDF (a implementar). |
| **Gestão de Contratos** | Via lead/project | Upload, assinatura, status financeiro (a implementar). |
| **Produção / Obra** | `admin-modules/projects.php` | Status da obra, cronograma, responsáveis (já existe; alinhar a contract_id e produção). |

---

## 4. Automações

- **Distribuição de leads**: tabelas `lead_distribution_rules` e `lead_distribution_state` (migration-crm-completo); implementar round-robin ou por região/fonte.
- **Follow-up automático**: `scheduled_followups`; job/cron que envia lembretes (email/whatsapp/phone).
- **Alertas de inatividade**: workflow por `inactivity` em `workflows`; criar tarefas ou notificações.
- **Mudança de status automática**: workflow por `stage_change`; ex.: ao concluir visita, avançar para "Medição Realizada".
- **Criação de tarefas**: ao entrar lead, criar tarefa "Contatar em Xh" (tabela `tasks`).

Arquivo existente: `cron-workflows.php` (executar via cron).

---

## 5. Permissões

| Perfil | Acesso |
|--------|--------|
| **Admin** | Tudo |
| **Manager** (project_manager) | Visão geral + edição (leads, propostas, contratos, produção) |
| **Sales** (sales_rep) | Apenas seus leads (owner_id = user_id) |
| **Operational** (support) | Produção/obra apenas (projects, delivery) |

Implementação: usar `config/permissions.php` (hasPermission, currentUserHasPermission) e filtrar listagens por `owner_id` quando role = sales_rep.

---

## 6. Logs e Auditoria

- **lead_status_change_log**: lead_id, from_stage_id, to_stage_id, changed_by, notes, created_at.
- **audit_log**: entity_type, entity_id, action, field_name, old_value, new_value, user_id, created_at.

Funções em `config/audit.php`:

- `logLeadStatusChange($lead_id, $from_stage_id, $to_stage_id, $user_id, $notes)`  
- `logAudit($entity_type, $entity_id, $action, $field_name, $old_value, $new_value, $user_id)`  
- `auditCurrentUserId()` para preencher user_id da sessão.

Usar em: mudança de status do lead, alteração de proposta/valor, criação de contrato, alterações críticas em projetos.

---

## Ordem de execução (migrations)

1. schema-v3-completo.sql (ou schema base com leads, users, etc.)  
2. migration-add-permissions.sql  
3. migration-crm-completo.sql (pipeline_stages, visits, measurements, quotes, contracts, workflows, tasks, etc.)  
4. migration-crm-full-spec.sql (lead_qualification, lead_status_change_log, audit_log, interactions, 11 estágios)  
5. migration-add-crm-modules-permissions.sql (permissões de visits, quotes, pipeline, contracts)  

Depois, executar manualmente os ALTERs comentados em migration-crm-full-spec.sql (measurements, quotes.valid_until, projects.contract_id) se as tabelas já existirem.
