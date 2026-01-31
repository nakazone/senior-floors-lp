# Última configuração que funcionou (lead salvo em 2026-01-29 22:55:02)

Lead que chegou ao sistema com sucesso:
- **Data/hora:** 2026-01-29 22:55:02  
- **Formulário:** contact-form  
- **Nome:** Douglas  
- **Telefone:** (000) 000-0000  
- **E-mail:** doug.nakazone@gmail.com  
- **CEP:** 77777  
- **Mensagem:** I want to install LVP on my living room  

---

## Configuração que deve estar no servidor

### 1. LP (lp.senior-floors.com)

| Arquivo | Configuração |
|--------|---------------|
| **index.html** | `window.SENIOR_FLOORS_FORM_URL = 'https://senior-floors.com/send-lead.php'` no `<head>` (já definido no código). |
| **script.js** | Hero e contact form usam `SENIOR_FLOORS_FORM_URL` quando definido (já no código). |
| **form-test-lp.html** | Envia para `https://senior-floors.com/send-lead.php` (ou `SENIOR_FLOORS_FORM_URL` se definido na página). |

**Fluxo:** O visitante preenche o form na LP (lp.senior-floors.com). O JavaScript envia o POST para **https://senior-floors.com/send-lead.php** (não para lp.senior-floors.com).

---

### 2. Painel (senior-floors.com)

| Item | Onde | Valor |
|------|------|--------|
| **send-lead.php** | Raiz do site (ex.: `public_html/send-lead.php`) | Recebe POST da LP; envia e-mail, grava CSV e chama receive-lead. |
| **SYSTEM_API_URL** | Em send-lead.php ou em **config/system-api-url.php** | `https://senior-floors.com/system.php?api=receive-lead` (já é o padrão no código). |
| **system.php** | Raiz do site | Bloco API no topo (antes de session_start) para `?api=receive-lead` e `?api=db-check`. |
| **api/receive-lead-handler.php** | Pasta `api/` | Recebe o lead, grava na tabela `leads`, retorna JSON com `lead_id`, `database_saved`, `api_version`, `inserted_new`. |
| **config/database.php** | Pasta `config/` | DB_HOST, DB_NAME, DB_USER, DB_PASS (sem placeholders). |
| **Tabela leads** | MySQL | Criada com **database/schema-v3-completo.sql**. |

**Fluxo:** send-lead.php (em senior-floors.com) recebe o POST, envia e-mail (se PHPMailer/SMTP configurado), grava CSV e faz POST para `https://senior-floors.com/system.php?api=receive-lead`. O receive-lead-handler grava no MySQL e responde com sucesso.

---

### 3. Resumo dos domínios

| O quê | URL / local |
|-------|-------------|
| LP (página do formulário) | **https://lp.senior-floors.com** |
| Envio do form (action do form) | **https://senior-floors.com/send-lead.php** |
| API do painel (receive-lead) | **https://senior-floors.com/system.php?api=receive-lead** |
| Diagnóstico do banco | **https://senior-floors.com/system.php?api=db-check** |
| CRM (ver leads) | **https://senior-floors.com/system.php?module=crm** |

---

## Como testar

1. **Form-test (recomendado)**  
   - Abra **https://lp.senior-floors.com/form-test-lp.html** (ou **https://senior-floors.com/form-test-lp.html**).  
   - Clique em “Enviar teste”.  
   - Confira na resposta: `success: true`, `system_sent: true`, `system_database_saved: true`, `system_api_version: "receive-lead-v2-early"`.  
   - Se `system_inserted_new: true`, uma nova linha foi inserida no banco; se `false`, foi considerada duplicata (mesmo e-mail/telefone).

2. **Formulário da LP**  
   - Abra **https://lp.senior-floors.com**, preencha o form (hero ou contato) e envie.  
   - O POST deve ir para **https://senior-floors.com/send-lead.php** (verifique na aba Rede do navegador, F12).  
   - O lead deve aparecer no CRM: **https://senior-floors.com/system.php?module=crm**.

3. **db-check**  
   - Abra **https://senior-floors.com/system.php?api=db-check**.  
   - Deve retornar JSON com `database_configured: true`, `connection_ok: true`, `table_leads_exists: true`, `api_version: "v2-early"`.

---

## Arquivos que precisam estar no servidor (upload)

**No servidor da LP (lp.senior-floors.com):**
- index.html  
- script.js  
- form-test-lp.html  

**No servidor do painel (senior-floors.com):**
- send-lead.php  
- system.php  
- api/receive-lead-handler.php  
- config/database.php (com credenciais reais)  
- (opcional) config/system-api-url.php (se quiser sobrescrever a URL do system)

Depois do upload, use o form-test e o form da LP para validar; a configuração que salvou o lead de 2026-01-29 22:55:02 é a descrita acima.

---

## Teste rápido com curl (opcional)

No terminal, para testar se o send-lead.php responde (substitua os dados se quiser):

```bash
curl -s -X POST 'https://senior-floors.com/send-lead.php' \
  -H 'Accept: application/json' \
  -d 'form-name=contact-form' \
  -d 'name=Teste Curl' \
  -d 'email=teste@exemplo.com' \
  -d 'phone=11999999999' \
  -d 'zipcode=80202' \
  -d 'message=Teste ultima config'
```

A resposta deve ser JSON com `success: true`, `system_sent: true`, `system_database_saved: true` (se o painel e o banco estiverem ok).
