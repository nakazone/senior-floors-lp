# Deploy das novas funcionalidades

Este guia lista o que fazer para colocar no ar: **Responsável pelo lead**, **Histórico de conversas e contatos**, **Pipeline com drag and drop**, correções de **tag_name** e demais alterações.

---

## Opção 1: Deploy automático (GitHub → Hostinger)

Se você já usa GitHub Actions para deploy no Hostinger:

1. **Commit e push** de todos os arquivos alterados:
   ```bash
   cd /Users/naka/senior-floors-landing
   git add .
   git status   # conferir arquivos
   git commit -m "Novas funcionalidades: responsável pelo lead, histórico de contatos, pipeline drag-drop, correções tag_name"
   git push origin main
   ```
2. O workflow envia o projeto para `/public_html/` (exceto `config/database.php`, `admin-config.php` e outros itens da lista de exclusão).
3. **Depois do deploy**, execute as migrations no phpMyAdmin (ver “Migrations no servidor” abaixo).

---

## Opção 2: Deploy manual (FTP / Gerenciador de arquivos)

Envie estes arquivos e pastas para o servidor (em geral em `public_html/` ou `public_html/lp/`, conforme sua estrutura):

### Arquivos PHP (raiz ou conforme sua pasta)
- `system.php`
- `send-lead.php`
- `diagnostico-banco.php`
- `error-check.php` (opcional; pode remover depois)

### Pasta config/
- `config/database.php` (só se quiser sobrescrever; normalmente **não** envia para não apagar credenciais)
- `config/lead-logic.php`
- `config/permissions.php` (se existir)
- `config/tags.php` (se existir)
- `config/pipeline.php` (se existir)

### Pasta admin-modules/
- `admin-modules/lead-detail.php`
- `admin-modules/pipeline.php`
- `admin-modules/crm.php` (se foi alterado)

### Pasta api/
- `api/leads/get.php`
- `api/leads/update.php`
- `api/leads/tags.php`
- `api/pipeline/move.php`
- `api/assignment/assign.php`
- `api/activities/create.php`
- `api/activities/list.php`

### Pasta database/ (só os .sql para rodar no phpMyAdmin)
- `database/migration-pipeline-only.sql`
- `database/migration-lead-owner-and-activities.sql`
- `database/pipeline-atualizar-leads.sql`

### Outros
- `sw.js`
- `CONFIG_BANCO_CHECKLIST.md`
- `DEPLOY_NOVAS_FUNCIONALIDADES.md` (este arquivo)

**Não sobrescreva no servidor** (mantenha como estão):
- `config/database.php` (credenciais do banco)
- `admin-config.php` (credenciais de login)
- `leads.csv` (dados locais)

---

## Migrations no servidor (phpMyAdmin)

Depois de enviar o código, execute no **phpMyAdmin** (no banco onde está a tabela `leads`):

1. **Pipeline (se ainda não fez)**  
   - Abra **database/migration-pipeline-only.sql**.  
   - Se der erro de “Duplicate column name 'pipeline_stage_id'”, ignore.  
   - Depois execute:  
     `UPDATE leads SET pipeline_stage_id = 1 WHERE pipeline_stage_id IS NULL;`  
     (ou use **database/pipeline-atualizar-leads.sql**.)

2. **Responsável pelo lead e histórico de contatos**  
   - Execute **database/migration-lead-owner-and-activities.sql**.  
   - Isso cria/ajusta: coluna `owner_id` em `leads`, tabelas `activities` e `assignment_history`.  
   - Se der “Duplicate column” ou “Table already exists”, pode ignorar.

---

## Conferência rápida

- [ ] Código enviado (git push ou FTP).
- [ ] **Pipeline**: migration do pipeline executada; leads aparecem no Kanban e drag-and-drop funciona.
- [ ] **Responsável**: migration `migration-lead-owner-and-activities.sql` executada; no detalhe do lead aparece “Responsável pelo lead” e dropdown de usuários.
- [ ] **Histórico**: no detalhe do lead aparece “Histórico de conversas e contatos”, “Registrar contato” e linha do tempo.
- [ ] **Detalhe do lead**: ao clicar em um lead não aparece mais erro de “tag_name”; tags e observações carregam.
- [ ] Usuários cadastrados em **Users** (para poder atribuir responsável).

---

## Rollback (se precisar)

- No Git: `git revert HEAD` e novo `git push origin main` (se usar deploy automático).  
- Via FTP: substituir apenas os arquivos listados acima por versões anteriores, se tiver backup.
