# Senior Floors CRM – Arquitetura dos 11 Blocos

Este documento descreve a arquitetura funcional e técnica do CRM completo da Senior Floors, da entrada do lead ao pós-venda, e o que foi implementado.

---

## 1️⃣ CAPTURA E ENTRADA DE LEADS

**Fontes de lead:**
- Site (formulários) – `LP-Hero`, `LP-Contact`, `site_form`
- WhatsApp – `whatsapp`
- Instagram / Facebook – `instagram`
- Google Ads – `google_ads`
- Indicação manual – `manual`
- Upload em massa (CSV) – `csv_upload`

**Campos por lead:**
- Nome completo, Telefone, Email, Endereço completo
- Tipo de imóvel (Casa / Apartamento / Comercial) – `property_type`
- Tipo de serviço (Vinyl, Hardwood, Tile, Carpet, Refinishing etc.) – `service_type`
- Interesse principal – `main_interest`
- Fonte do lead – `source`
- Data e hora de entrada – `created_at`
- Responsável (owner) – `owner_id`
- Status inicial – `new` + `pipeline_stage_id = 1` (Lead recebido)

**Implementado:**
- Validação de dados em `send-lead.php` e `api/leads/create.php`
- Prevenção de duplicados em `config/lead-logic.php` (`checkDuplicateLead` por email/telefone)
- Distribuição round-robin em `getNextOwnerRoundRobin`
- Criação automática de tarefa “Contatar lead” em 24h em `createLeadEntryTask` (tabela `tasks`)
- Novos campos e regras no banco via `database/migration-crm-completo.sql`

---

## 2️⃣ QUALIFICAÇÃO DO LEAD (PRÉ-VENDA)

**Campos de qualificação:**
- Orçamento estimado – `budget_estimated`
- Urgência (Imediato / 30 dias / 60+ dias) – `urgency`
- Decisor (Sim / Não) – `is_decision_maker`
- Tipo de pagamento (Cash / Financing) – `payment_type`
- Concorrência (Sim / Não) – `has_competition`

**Implementado:**
- Colunas em `leads` na migration
- Score automático em `config/lead-logic.php` (`calculateLeadScore`)
- Tags automáticas em `applyAutoTags` (High Ticket, Commercial, Urgent)
- API `api/leads/update.php` aceita `budget_estimated`, `urgency`, `is_decision_maker`, `payment_type`, `has_competition` e `pipeline_stage_id`

**A fazer (automações):** mudança de estágio por respostas, alertas e follow-up automático (workflows).

---

## 3️⃣ PIPELINE COMERCIAL (KANBAN)

**Estágios:**
1. Lead recebido  
2. Contato feito  
3. Qualificado  
4. Visita / Medição agendada  
5. Medição realizada  
6. Orçamento enviado  
7. Negociação  
8. Fechado - Ganhou  
9. Fechado - Perdeu  
10. Pós-venda  

**Implementado:**
- Tabela `pipeline_stages` (nome, slug, order_num, sla_hours, required_actions, required_fields, is_closed)
- Coluna `leads.pipeline_stage_id`
- Config em `config/pipeline.php` (estágios e fontes)
- API: `api/pipeline/stages.php`, `api/pipeline/leads.php`, `api/pipeline/move.php`
- Módulo admin **Pipeline (Kanban)** em `admin-modules/pipeline.php` – colunas por estágio, mover lead por dropdown

**A fazer:** SLAs por estágio, ações/campos obrigatórios por estágio e automação de tarefas (uso de `required_actions` / `required_fields`).

---

## 4️⃣ GESTÃO DE VISITAS E MEDIÇÕES

**Implementado (banco):**
- `visits` – agendamento, vendedor, técnico, status (scheduled, completed, cancelled, no_show)
- `measurements` – visit_id, metragem (area_sqft), cômodos, observações técnicas
- `visit_attachments` – fotos/vídeos do local

**A fazer:** APIs CRUD de visitas/medições, integração com calendário, checklist de medição e upload de arquivos na interface.

---

## 5️⃣ ORÇAMENTOS E PROPOSTAS

**Implementado (banco):**
- `quotes` – lead_id, customer_id, project_id, version, total_amount, labor_amount, materials_amount, margin_percent, status (draft/sent/viewed/approved/rejected), datas sent/viewed/approved, pdf_path
- `quote_items` – tipo de piso, metragem, preço unitário/total

**Implementado:** APIs `api/quotes/list.php`, `create.php`, `get.php`, `update.php`; módulo admin Orçamentos (`admin-modules/quotes.php`, `quote-detail.php`) com cálculo por metragem/preço unitário e margem. Status: draft, sent, viewed, approved, rejected.
**A fazer:** Geração de PDF, histórico de versões, assinatura digital e notificações.

---

## 6️⃣ FECHAMENTO E CONTRATOS

**Implementado (banco):**
- `contracts` – lead_id, customer_id, project_id, quote_id, closed_amount, payment_method, installments, start_date, end_date, responsible_id, contract_path, signed_at

**Implementado:** API contracts/create e list; ao criar contrato atualiza lead para closed_won e quote para approved. **A fazer:** Interface de fechamento, PDF do contrato, assinatura digital; integração com estágio “Fechado - Ganhou”.

---

## 7️⃣ PÓS-VENDA E OBRA

**Implementado (banco):**
- `projects.post_service_status` (já existia)
- `project_documents` – upload de documentos
- `project_issues` – registro de problemas (open, in_progress, resolved)
- `delivery_checklists` – itens de entrega

**A fazer:** Interface de pós-venda (status da obra, documentos, problemas, checklist de entrega e comunicação com cliente).

---

## 8️⃣ AUTOMAÇÕES E WORKFLOWS

**Implementado (banco):**
- `workflows` – trigger_type (stage_change, inactivity, new_lead, schedule), trigger_config, actions (JSON)
- `scheduled_followups` – lead_id, scheduled_at, channel (email/whatsapp/phone), message_template, sent_at

**Implementado:** Script `cron-workflows.php` (executar via cron): marca follow-ups agendados como enviados e detecta leads inativos para workflows de inatividade. Implementar envio real (email/WhatsApp) conforme integração.

---

## 9️⃣ DASHBOARDS E RELATÓRIOS

**Requisitos:** Leads por fonte, taxa de conversão por etapa, tempo médio de fechamento, ticket médio, performance por vendedor, receita projetada vs realizada.

**A fazer:** Dashboards e relatórios no módulo Dashboard e/ou novo módulo Reports, usando as tabelas existentes (leads, projects, contracts, pipeline_stages).

---

## 🔟 PERMISSÕES E USUÁRIOS

**Implementado:**
- Tabelas `permissions`, `user_permissions`
- Roles: admin, sales_rep, project_manager, support
- Módulo Users e gestão de permissões por usuário
- Config em `config/permissions.php`

**Implementado:** Migration `database/migration-add-crm-modules-permissions.sql` adiciona permissões: visits.view/create/edit, quotes.view/create/edit, pipeline.view/edit, contracts.view/create. Admin recebe todas. Uso de `hasPermission()` nos módulos é opcional.

---

## 1️⃣1️⃣ PREPARAÇÃO PARA IA

**Implementado (banco):**
- `interaction_logs` – entity_type, entity_id, event_type, payload (JSON), user_id, created_at – para eventos rastreáveis e logs de interação

**A fazer:** Lead scoring inteligente, sugestão de follow-ups, previsão de fechamento, respostas automáticas e assistente comercial, consumindo `interaction_logs` e dados do CRM.

---

## Como aplicar a migration

1. Execute o schema completo v3 (se ainda não fez):  
   `database/schema-v3-completo.sql`

2. Execute a migration do CRM completo:  
   `database/migration-crm-completo.sql`

   Se alguma coluna já existir (por exemplo após rodar a migration duas vezes), ignore o erro daquele `ALTER` ou comente a linha correspondente.

3. Opcional: executar via PHP (se tiver PDO e permissão):  
   Criar um script que lê `migration-crm-completo.sql` e executa cada statement (ou usar seu cliente MySQL).

Após a migration, o menu do sistema terá **Pipeline (Kanban)** e os novos campos de lead e qualificação estarão disponíveis nas APIs e no painel.
