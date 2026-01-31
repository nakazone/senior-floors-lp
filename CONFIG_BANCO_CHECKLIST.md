# Checklist: Leads no banco de dados

Siga na ordem. Quando todos os itens estiverem ✓, os leads passarão a ser salvos no MySQL.

**Este projeto:** LP = **lp.senior-floors.com** (send-lead.php) | Painel = **senior-floors.com/system.php** (CRM). O send-lead já usa por padrão `https://senior-floors.com/system.php?api=receive-lead`; opcional: criar **config/system-api-url.php** no servidor da LP (copiar de system-api-url.php.example).

**Formulário da LP:** O formulário envia para **https://senior-floors.com/send-lead.php** (action e JS apontam para o painel). O send-lead.php no painel envia e-mail, grava CSV e repassa ao receive-lead (banco). O send-lead.php tem CORS (OPTIONS + headers) para aceitar POST da LP (lp.senior-floors.com). E-mail opcional via **config/smtp.php** (copie de config/smtp.php.example).

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

## 5c. (Opcional) E-mail enviado — PHPMailer/SMTP

Se o formulário retorna **email_sent: false**, o e-mail interno (para leads@senior-floors.com) não está sendo enviado. Para corrigir:

1. **Instalar PHPMailer (se ainda não tiver)**  
   - Baixe: https://github.com/PHPMailer/PHPMailer (Code → Download ZIP).  
   - Extraia e envie para o servidor a pasta **PHPMailer** (com os arquivos `Exception.php`, `PHPMailer.php`, `SMTP.php`) na **mesma pasta** onde está o **send-lead.php** (ex.: `public_html/lp/PHPMailer/` ou `public_html/PHPMailer/`).

2. **Configurar senha SMTP (Google App Password)**  
   - Abra **send-lead.php** no servidor.  
   - Localize a linha `define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE');`.  
   - Troque `YOUR_APP_PASSWORD_HERE` pela **App Password** do Gmail/Google Workspace (conta usada em `SMTP_USER`, ex.: contact@senior-floors.com).  
   - Para criar App Password: Google Account → Segurança → Verificação em 2 etapas (ativar) → Senhas de app → gerar uma para "Mail".

3. **Testar de novo**  
   - Envie um lead de teste (formulário ou **form-test-lp.html**).  
   - Se a resposta incluir `email_error` e `email_error_hint`, use essa mensagem para corrigir (ex.: `smtp_not_configured` = coloque a App Password; `phpmailer_not_installed` = instale a pasta PHPMailer).  
   - No servidor, confira **email-status.log** (na mesma pasta do log de leads) para erros detalhados do SMTP.

*Guia rápido em português: **INSTALAR_PHPMailer.md**. Detalhes: **PHPMailer_SETUP.md** e **QUICK_SETUP_APP_PASSWORD.md**.*

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
| **email_sent: false (e-mail não enviado)** | Se o motivo for **phpmailer_not_installed**: crie a pasta **PHPMailer** na mesma pasta do **send-lead.php** e coloque dentro os 3 arquivos (Exception.php, PHPMailer.php, SMTP.php) — ver **INSTALAR_PHPMailer.md**. Depois configure **SMTP_PASS** (Google App Password) em send-lead.php. Ver **5c** e **PHPMailer_SETUP.md**. |
| **E-mail chega mas lead não vai para o banco** | 1) Garanta que **config/database.php** existe na pasta do site (mesmo nível ou acima de send-lead.php). 2) Defina em **send-lead.php** a constante **SYSTEM_API_URL** com a URL completa do system (ex.: `https://seudominio.com/system.php?api=receive-lead`); o system recebe o lead e grava no banco do CRM. |
| **system_sent: false (lead não chega no CRM)** | Defina a URL do system: crie **config/system-api-url.php** (copie de **config/system-api-url.php.example**) e coloque a URL completa onde você abre o painel (ex.: `https://senior-floors.com/system.php?api=receive-lead`). Ou edite **send-lead.php** e defina **SYSTEM_API_URL**. Ver **CONFIGURAR_SYSTEM_URL.md**. |
| **system_sent: true mas lead não aparece no CRM** | A resposta agora inclui **system_database_saved**. Se for **false**, no servidor **onde está o system.php** (painel): confira **config/database.php** (existe e sem placeholders) e que a tabela **leads** existe (execute database/schema-v3-completo.sql). Use **form-test-lp.html**; a resposta mostra **system_error_hint** e **system_db_error** com o motivo. |
| **database_saved: false (ainda)** | 1) Veja na resposta do **form-test-lp.html** o campo **"Detalhe do system (banco)"** (system_db_error). 2) Abra **https://SEU_DOMINIO/system.php?api=db-check** no servidor do painel — o JSON mostra **database_configured**, **connection_ok**, **table_leads_exists** e **hint**. Siga o hint (editar config/database.php ou rodar schema no MySQL). Ver seção **"database_saved: false ainda?"** acima. |
| **db-check OK mas dados não chegam no CRM** | 1) Envie um lead de teste com **form-test-lp.html** e confira na resposta: **system_sent** (deve ser true) e **system_database_saved** (deve ser true). Se **system_database_saved: false**, veja **system_db_error** e **system_error_hint**. 2) No servidor do painel, abra o arquivo **system-api.log** (mesma pasta do system.php): deve aparecer "receive-lead POST received" e depois "saved to DB (id=...)". Se não aparecer, a chamada do send-lead para o system não está chegando — confira **SYSTEM_API_URL** em send-lead.php (URL completa do painel + ?api=receive-lead). 3) Atualize o **system.php** no servidor (versão com lead-logic protegido e log) e teste de novo. |
| **E-mail chega mas lead não aparece no CRM** | 1) Atualize a página do CRM (F5) e abra **sem filtros**: `system.php?module=crm` (sem search, date_from, date_to). 2) Envie um lead de teste pelo **form-test-lp.html** e veja na resposta: **system_sent** e **database_saved** devem ser **true**. Se **database_saved: false**, o lead não foi gravado no banco do painel — confira **SYSTEM_API_URL** no servidor da LP (deve ser a URL completa do painel, ex.: `https://painel.seudominio.com/system.php?api=receive-lead`). 3) Se usou o **mesmo e-mail ou telefone** de um lead anterior, o sistema considera duplicado e não cria nova linha — o lead aparece como o mesmo registro. 4) No CRM, confira a linha **"Último lead no banco"** (data/hora) para ver se há leads recentes. |
| **Resposta com lead_id e database_saved: true, mas na tabela não vejo os leads** | 1) **Mesmo banco?** No servidor do painel (senior-floors.com), o **config/database.php** define **DB_NAME**. No phpMyAdmin (do mesmo hosting do painel), selecione **exatamente esse banco** e consulte `SELECT * FROM leads ORDER BY id DESC`. Se estiver em outro banco ou outro servidor, os dados não aparecerão. 2) **Duplicata:** Se você testou várias vezes com o **mesmo e-mail ou telefone**, o sistema não insere nova linha — retorna o **id do lead existente** (ex.: sempre lead_id: 9). Veja na resposta **system_inserted_new**: **true** = nova linha inserida, **false** = considerado duplicata (não inseriu). Para ver novos registros, use e-mail e telefone **diferentes** em cada teste. 3) No painel, abra **system-api.log** (pasta do system.php): deve aparecer "saved to DB (id=...)" — confirme o id e o banco usado pelo painel. |
| **Formulário da LP só chega por e-mail; não grava em CSV nem no banco** | O form da LP precisa enviar para o **mesmo** send-lead.php que grava CSV e chama o CRM. Se a LP está em um domínio/pasta e o send-lead.php em outro (ex.: LP em senior-floors.com e send-lead em lp.senior-floors.com), o form usa a URL do site atual e pode estar caindo em outro script que só manda e-mail. **Solução:** No **index.html** da LP, descomente e defina **window.SENIOR_FLOORS_FORM_URL** com a URL completa do send-lead.php (ex.: `https://senior-floors.com/send-lead.php`). Os forms já têm **action="https://senior-floors.com/send-lead.php"** no HTML. Confira também que send-lead.php está na raiz no servidor do painel. |
| **Leads vão para o CSV mas não para o banco** | 1) O form da LP deve enviar para **senior-floors.com/send-lead.php** (não para lp.senior-floors.com). No **index.html** os forms já têm **action="https://senior-floors.com/send-lead.php"**; faça upload do **index.html** e **script.js** na LP. 2) Se mesmo assim o form postar para a LP (cache, etc.), use **forward-lead.php**: no servidor **lp.senior-floors.com**, substitua o conteúdo de **send-lead.php** pelo conteúdo de **forward-lead.php** (ou renomeie forward-lead.php para send-lead.php na LP). Assim todo POST para lp/send-lead.php será encaminhado para senior-floors.com/send-lead.php (e-mail + CSV + banco). 3) No servidor **senior-floors.com**, confira **config/database.php** (credenciais reais) e que a tabela **leads** existe; use **system.php?api=db-check** para diagnosticar. |
