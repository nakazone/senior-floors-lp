# Análise: dados só no CSV, não no banco

Este documento explica **por que os dados podem estar indo só para o CSV e não para o banco** e o que fazer para corrigir.

---

## Fluxo esperado

1. **Formulário da LP** (lp.senior-floors.com) → POST para **https://senior-floors.com/send-lead.php**
2. **send-lead.php** (no painel):
   - Grava no **CSV** (sempre, em `leads.csv`)
   - Faz **curl** para **https://senior-floors.com/system.php?api=receive-lead**
3. **receive-lead** (system.php ou api/receive-lead-handler.php):
   - Conecta no **MySQL** (config/database.php)
   - Insere na tabela **leads**
   - Retorna JSON com `database_saved: true` e `lead_id`

Se em algum ponto desse fluxo algo falhar, o lead fica só no CSV.

---

## Possíveis causas (e como conferir)

### 1. Form não chega no send-lead do painel

**Sintoma:** CSV é preenchido na **LP** (lp.senior-floors.com), mas não no painel.

**Causa:** O form ainda envia para **lp.senior-floors.com/send-lead.php** em vez de **senior-floors.com/send-lead.php**.

**O que fazer:**
- No **index.html** da LP: `action="https://senior-floors.com/send-lead.php"` nos dois forms (hero e contact).
- No **script.js**: em lp.senior-floors.com a URL de envio deve ser `https://senior-floors.com/send-lead.php`.
- Fazer upload de **index.html** e **script.js** na LP (lp.senior-floors.com).
- No navegador (F12 → Rede): ao enviar o form, a requisição POST deve ir para **senior-floors.com/send-lead.php**.

---

### 2. send-lead.php não chama receive-lead (curl falha)

**Sintoma:** CSV é preenchido no **painel** (senior-floors.com), mas o banco não recebe.

**Causa:** O curl de send-lead.php para system.php?api=receive-lead falha (timeout, 404, 500).

**O que fazer:**
- No painel, abrir o log: **system-integration.log** (na pasta do send-lead.php ou em `public_html/`).
- Se aparecer "System API failed: HTTP ..." ou "Retry with internal URL", o curl está falhando.
- O código já tenta duas URLs: a normal e `http://127.0.0.1/system.php?api=receive-lead` (com Host: senior-floors.com).
- Conferir se **SYSTEM_API_URL** está definido em send-lead.php ou em **config/system-api-url.php**: `https://senior-floors.com/system.php?api=receive-lead`.

---

### 3. receive-lead não existe ou não roda (404 / HTML em vez de JSON)

**Sintoma:** send-lead.php recebe 404 ou uma página HTML em vez de JSON.

**Causa:** No servidor do painel:
- **api/receive-lead-handler.php** não existe (pasta api/ não deployada), ou
- system.php não está na raiz, ou
- A URL usada no curl está errada.

**O que fazer:**
- Foi adicionado um **fallback** no **system.php**: quando `api/receive-lead-handler.php` **não existe**, o próprio system.php executa a lógica de receive-lead no início do arquivo (antes de session_start) e devolve JSON puro com `database_saved` e `lead_id`.
- Garantir que o **system.php** atualizado está no servidor (deploy pelo Git ou upload manual).
- Testar no navegador: **https://senior-floors.com/system.php?api=db-check** → deve retornar JSON com `database_configured`, `connection_ok`, `table_leads_exists`.

---

### 4. Banco não configurado no painel (config/database.php)

**Sintoma:** receive-lead retorna `database_saved: false` e `db_error: "Database not configured..."`.

**Causa:** No servidor do **painel** (senior-floors.com) não existe **config/database.php** ou ele ainda tem placeholders (`seu_usuario`, `sua_senha`).

**O que fazer:**
- No painel (FTP ou Gerenciador de Arquivos), em **config/**:
  - Criar **config/database.php** (copiar de **config/database.php.example**).
  - Trocar **DB_NAME**, **DB_USER**, **DB_PASS** pelos valores reais do MySQL no Hostinger (sem `seu_usuario` / `sua_senha`).
- **config/database.php** não vai no deploy pelo Git (está no .gitignore). Tem que existir e estar configurado **manualmente** no servidor.

---

### 5. Tabela `leads` não existe no MySQL

**Sintoma:** receive-lead retorna `database_saved: false` e `db_error: "Table 'leads' does not exist..."`.

**Causa:** O banco existe mas a tabela **leads** não foi criada.

**O que fazer:**
- No Hostinger, abrir **phpMyAdmin** do banco usado em **config/database.php**.
- Executar o script **database/schema-v3-completo.sql** (Importar ou colar o conteúdo na aba SQL).
- Conferir: **https://senior-floors.com/system.php?api=db-check** deve retornar `table_leads_exists: true`.

---

### 6. Erro de conexão MySQL (host/senha/porta)

**Sintoma:** db-check retorna `connection_ok: false` ou receive-lead retorna `db_error: "Could not connect..."`.

**Causa:** DB_HOST, DB_NAME, DB_USER ou DB_PASS incorretos em config/database.php.

**O que fazer:**
- Conferir no painel da Hostinger (Bancos de dados MySQL) o **host** (geralmente `localhost`), **nome do banco**, **usuário** e **senha**.
- Ajustar **config/database.php** no servidor com esses valores e salvar.

---

## Checklist no servidor do painel (senior-floors.com)

| Item | Onde | Verificação |
|------|------|-------------|
| **config/database.php** | config/ | Existe e tem DB_NAME, DB_USER, DB_PASS **reais** (não placeholders)? |
| **Tabela leads** | MySQL (phpMyAdmin) | Existe? Se não, rodar **database/schema-v3-completo.sql** |
| **system.php** | Raiz (ex.: public_html/) | Versão com bloco API no topo e fallback receive-lead? |
| **api/receive-lead-handler.php** | api/ | Existe? (Opcional: se não existir, o fallback no system.php cuida.) |
| **db-check** | Navegador | Abrir **system.php?api=db-check** → `database_configured`, `connection_ok`, `table_leads_exists` devem ser **true** |

---

## Teste rápido após corrigir

1. Abrir **https://senior-floors.com/system.php?api=db-check**  
   → JSON com `connection_ok: true`, `table_leads_exists: true`.

2. Enviar um lead de teste (form da LP ou **form-test-lp.html**).

3. Na resposta JSON do form, conferir:
   - `system_sent: true`
   - `system_database_saved: true`
   - `lead_id`: número

4. No painel: **system.php → CRM**  
   → O lead deve aparecer na lista (e no banco).

---

## Resumo das alterações feitas no código

1. **system.php**  
   - Quando **api/receive-lead-handler.php** **não existe** no servidor, o **system.php** executa a lógica de receive-lead no **início** do arquivo (antes de session e includes), insere na tabela **leads** e retorna JSON puro com `database_saved` e `lead_id`. Assim o banco passa a ser preenchido mesmo sem a pasta **api/** deployada.

2. **api/receive-lead-handler.php**  
   - Inicialização de **$inserted_new** e definição ao inserir novo lead, para evitar aviso e para a resposta incluir corretamente `inserted_new`.

Com o fallback no system.php e o **config/database.php** e a tabela **leads** corretos no servidor, os dados passam a ser salvos no banco e o CRM consegue acessá-los.
