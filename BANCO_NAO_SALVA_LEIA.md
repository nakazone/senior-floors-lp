# Banco de dados não salva os leads — o que fazer

Se **e-mail e CSV funcionam** mas os **leads não aparecem no banco** (CRM), siga estes passos **no servidor do painel** (senior-floors.com).

---

## 1. Ver o motivo exato no log

No servidor, abra o arquivo **system-integration.log** (na raiz do site ou na pasta do send-lead.php).

Procure por linhas como:
- **`❌ DB not saved by receive-lead: ...`** — o texto depois é o motivo (ex.: "Database not configured", "Table 'leads' does not exist", "Could not connect to database").

Isso indica o que o receive-lead (system.php) retornou quando não conseguiu salvar no MySQL.

---

## 2. Testar o banco com db-check

No navegador, abra:

**https://senior-floors.com/system.php?api=db-check**

A resposta é um JSON com:

- **config_loaded** — `true` se o arquivo `config/database.php` existe no servidor.
- **database_configured** — `true` se as credenciais não são placeholders (SEU_USUARIO, SUA_SENHA_AQUI, etc.).
- **connection_ok** — `true` se a conexão MySQL funcionou.
- **table_leads_exists** — `true` se a tabela `leads` existe.
- **hint** — texto explicando o que fazer quando algo estiver `false`.

Corrija conforme o **hint**:

| Se aparecer | O que fazer |
|-------------|-------------|
| config_loaded: false | Criar **config/database.php** no servidor: copiar **config/database.php.example** para **config/database.php** e preencher DB_NAME, DB_USER, DB_PASS com os dados do MySQL no Hostinger. |
| database_configured: false | Editar **config/database.php** e trocar **SEU_USUARIO**, **SUA_SENHA_AQUI**, **SEU_USUARIO_senior_floors_db** pelos valores reais do banco (nome completo com prefixo do Hostinger). |
| connection_ok: false | Conferir no Hostinger (Bancos de dados MySQL) o host, nome do banco, usuário e senha; ajustar em **config/database.php**. |
| table_leads_exists: false | No phpMyAdmin (Hostinger), selecionar o banco e executar o arquivo **database/schema-v3-completo.sql** (Importar ou colar na aba SQL). |

---

## 3. Página de diagnóstico

Abra também:

**https://senior-floors.com/diagnostico-banco.php**

Ela mostra se o `config/database.php` existe, se a conexão MySQL funciona, se a tabela `leads` existe e as últimas linhas dos logs (incluindo **system-integration.log**).

---

## Resumo

1. **system-integration.log** → linha "DB not saved by receive-lead:" indica o motivo.
2. **system.php?api=db-check** → JSON com config_loaded, connection_ok, table_leads_exists e **hint**.
3. **diagnostico-banco.php** → visão geral e logs.
4. Corrigir no servidor: **config/database.php** (criar/editar com credenciais reais) e tabela **leads** (rodar schema-v3-completo.sql no MySQL).

O arquivo **config/database.php** não vai no deploy (está no .gitignore); ele precisa existir e estar configurado **manualmente** no servidor.
