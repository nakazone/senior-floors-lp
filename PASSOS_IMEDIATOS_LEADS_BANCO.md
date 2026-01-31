# Passos imediatos: leads só no CSV, não no banco

Se os dados do formulário estão indo **só para o CSV** e **não para o MySQL**, siga estes passos **no servidor do painel** (senior-floors.com).

---

## 1. Abrir a página de diagnóstico

No navegador, acesse:

**https://senior-floors.com/diagnostico-banco.php**

Ela mostra:

- Se **config/database.php** existe
- Se o banco está **configurado** (credenciais reais, não placeholders)
- Se a **conexão MySQL** funciona
- Se a tabela **leads** existe
- Últimas linhas do **lead-db-save.log**
- Últimas linhas do **system-integration.log** (chamada do send-lead para o receive-lead)
- Link para **system.php?api=db-check**

Corrija qualquer item que aparecer com ✗ ou aviso.

---

## 2. Conferir config/database.php (no servidor)

- No Hostinger: **Gerenciador de Arquivos** ou FTP → pasta **public_html** (ou onde está o painel) → **config/**
- Deve existir o arquivo **config/database.php** (não só o .example).
- Abra **config/database.php** e confira:
  - **DB_NAME** = nome completo do banco no Hostinger (ex.: `u123456789_senior`)
  - **DB_USER** = usuário MySQL completo (ex.: `u123456789_user`)
  - **DB_PASS** = senha real do usuário MySQL
- **Não** pode ter `seu_usuario`, `sua_senha` ou `senior_floors_db` como placeholders.

*Credenciais: Hostinger → **Bancos de dados MySQL** → seu banco e usuário.*

---

## 3. Conferir a tabela leads

- No Hostinger, abra **phpMyAdmin** (link do seu banco).
- Selecione o **mesmo banco** que está em **config/database.php**.
- Verifique se existe a tabela **leads**.
- Se **não existir**: importe/execute o arquivo **database/schema-v3-completo.sql** do projeto (aba SQL ou Importar).

---

## 4. Testar db-check

Abra no navegador:

**https://senior-floors.com/system.php?api=db-check**

A resposta deve ser JSON com algo como:

```json
{
  "config_loaded": true,
  "database_configured": true,
  "connection_ok": true,
  "table_leads_exists": true,
  "hint": "",
  "api_version": "v2-early"
}
```

Se **database_configured**, **connection_ok** ou **table_leads_exists** for **false**, use o texto do campo **hint** para corrigir (editar config, rodar schema, etc.).

---

## 5. Enviar um lead de teste e ver o log

1. Envie um lead de teste pelo formulário da LP (lp.senior-floors.com) ou use **form-test-lp.html**.
2. Abra de novo **https://senior-floors.com/diagnostico-banco.php**.
3. Na seção **6. Log da integração (system-integration.log)**:
   - Deve aparecer a **System API URL** e depois **✅ Lead sent to system.php API successfully**.
   - Se aparecer **Retry with internal URL** ou erro HTTP (404, 500), o problema é no curl ou no system.php (URL errada, arquivo não deployado).
4. Se o curl der certo mas o lead não aparecer no CRM, na resposta do **form-test-lp.html** veja o campo **"Detalhe do system (banco)"** (system_db_error) — ele mostra o erro retornado pelo receive-lead (ex.: "Database not configured", "Could not connect").

---

## Resumo rápido

| O que verificar | Onde |
|-----------------|------|
| config/database.php existe e com credenciais reais | Servidor: config/database.php |
| Tabela leads existe | phpMyAdmin → seu banco |
| db-check retorna connection_ok e table_leads_exists true | https://senior-floors.com/system.php?api=db-check |
| send-lead chama receive-lead (curl OK) | diagnostico-banco.php → seção 6 (system-integration.log) |
| Form da LP envia para senior-floors.com | index.html action e script.js → https://senior-floors.com/send-lead.php |

Depois de corrigir **config/database.php** e a tabela **leads**, faça um novo envio de teste e confira no CRM do painel se o lead aparece.
