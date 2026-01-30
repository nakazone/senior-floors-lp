# Checklist: Leads no banco de dados

Siga na ordem. Quando todos os itens estiverem ✓, os leads passarão a ser salvos no MySQL.

---

## 1. Criar `config/database.php`

- [ ] No servidor (Hostinger: Gerenciador de Arquivos ou FTP), vá em **config/**
- [ ] Copie **database.php.example** e renomeie a cópia para **database.php**
- [ ] Resultado: existe o arquivo `config/database.php`

---

## 2. Trocar placeholders por credenciais reais

- [ ] Abra **config/database.php** no editor
- [ ] Substitua:
  - **DB_NAME** → nome completo do banco no Hostinger (ex.: `u123456789_senior_floors_db`)
  - **DB_USER** → nome completo do usuário MySQL (ex.: `u123456789_senior_user`)
  - **DB_PASS** → senha do usuário MySQL
- [ ] Mantenha **DB_HOST** como `localhost` e **DB_CHARSET** como `utf8mb4`
- [ ] Salve o arquivo

*Credenciais: Hostinger → Bancos de dados MySQL → seu banco e usuário.*

---

## 3. Conferir conexão (host, banco, usuário, senha)

- [ ] No Hostinger, em **Bancos de dados MySQL**, confira:
  - Host (geralmente `localhost`)
  - Nome do banco (com prefixo)
  - Usuário (com prefixo)
  - Senha do usuário
- [ ] Acesse **https://SEU_DOMINIO/diagnostico-banco.php** e veja se aparece “✓ Conexão com o MySQL estabelecida”
- [ ] Se der erro de conexão, revise host, nome do banco, usuário e senha em **config/database.php**

---

## 4. Criar a tabela `leads`

- [ ] No Hostinger, abra **phpMyAdmin** (link do seu banco)
- [ ] Selecione o banco de dados que você usa no **config/database.php**
- [ ] Vá em **Importar** (Import) ou na aba **SQL**
- [ ] Execute o arquivo **database/schema-v3-completo.sql** do projeto (upload do arquivo ou colar o conteúdo na aba SQL)
- [ ] Confirme que a tabela **leads** aparece na lista de tabelas
- [ ] Em **diagnostico-banco.php** deve aparecer “✓ A tabela leads existe”

---

## 5. (Opcional) Leads no Pipeline (Kanban)

Para os leads aparecerem no **Pipeline Comercial** (menu Pipeline no painel):

- [ ] No phpMyAdmin, no mesmo banco onde está a tabela **leads**, execute o arquivo **database/migration-pipeline-only.sql** (Importar ou colar o conteúdo na aba SQL).
- [ ] Se aparecer erro **"Duplicate column name 'pipeline_stage_id'"**, a coluna já existe — ignore e siga.
- [ ] No painel, abra **Pipeline (Kanban)**: os leads devem aparecer na coluna **Lead recebido** e você pode movê-los com o dropdown em cada card.

---

## 5b. (Opcional) CRM completo – 11 estágios, auditoria e qualificação

Para validação de etapas no Pipeline, logs de mudança de status e telas de qualificação/auditoria:

- [ ] No phpMyAdmin, no mesmo banco, execute o arquivo **database/migration-crm-full-spec.sql** (Importar ou colar o conteúdo na aba SQL).
- [ ] Confirme que existem as tabelas: `lead_qualification`, `lead_status_change_log`, `audit_log`, `interactions` e que `pipeline_stages` tem 11 estágios.
- [ ] No painel: Pipeline passa a validar “não pular etapas”, e mudanças de status são registradas no histórico.

---

## 6. Depois de concluir (testar)

- [ ] Envie um lead de teste pelo formulário do site (hero ou contato).
- [ ] Acesse **diagnostico-banco.php** e veja a seção **5. Últimas linhas do log**.
- [ ] Deve aparecer pelo menos: **send-lead.php chamado** e depois **LP recebido**; se o banco estiver ok: **✅ Lead saved to database**.
- [ ] Acesse **system.php** → **CRM - Leads**: o lead deve aparecer na lista.

---

## Se o log continuar vazio

O formulário envia para `https://SEU_DOMINIO/send-lead.php` (raiz do site). Se o log nunca aparecer:

1. **Confirme que send-lead.php está na raiz**  
   No servidor, o arquivo deve estar em `public_html/send-lead.php` (ou na pasta que é a “raiz” do domínio). Se estiver só em `public_html/lp/send-lead.php`, o site chama `/send-lead.php` e pode dar 404 — aí o script não roda e o log fica vazio.

2. **Veja o “Caminho lido” no diagnóstico**  
   Em **diagnostico-banco.php**, seção 5, aparece o caminho do arquivo de log. No FTP/painel, verifique se existe **lead-db-save.log** nessa pasta ou na pasta onde está o **send-lead.php** (o script tenta gravar nos dois lugares).

3. **Permissão de escrita**  
   A pasta onde o log é gravado precisa permitir escrita pelo servidor (permissão 755 ou 775 na pasta; o arquivo pode ser criado pelo PHP).

---

| Problema | O que fazer |
|----------|-------------|
| **config/database.php não existe** | Crie a partir de **config/database.php.example** com as credenciais MySQL do Hostinger. |
| **Banco “não configurado”** | Troque placeholders (seu_usuario, sua_senha, senior_floors_db) pelos valores reais. |
| **Erro de conexão** | Confira host, nome do banco, usuário e senha no painel do Hostinger. |
| **Tabela leads não existe** | Execute **database/schema-v3-completo.sql** no MySQL (phpMyAdmin). |
| **Leads não aparecem no Pipeline** | Execute **database/migration-pipeline-only.sql** no phpMyAdmin (cria estágios e coluna `pipeline_stage_id` em leads). |
| **Responsável pelo lead / Histórico de contatos** | Execute **database/migration-lead-owner-and-activities.sql** (coluna `owner_id` em leads + tabelas `activities` e `assignment_history`). Cadastre usuários em **Users** para poder atribuir responsável. |
| **Pipeline com 11 estágios / Auditoria / Qualificação** | Execute **database/migration-crm-full-spec.sql** (11 estágios, `lead_qualification`, `lead_status_change_log`, `audit_log`, `interactions`). Necessário para validação de etapas e logs de status. |
