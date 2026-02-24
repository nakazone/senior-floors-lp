# Receber leads por email (SMTP) na LP

A API `/api/send-lead` (Vercel) pode enviar um email a cada lead recebido. Configure as variáveis de ambiente no **Vercel** (projeto da LP → Settings → Environment Variables).

## Variáveis obrigatórias

| Variável    | Exemplo              | Descrição                    |
|------------|----------------------|------------------------------|
| `SMTP_HOST`| `smtp.gmail.com`     | Servidor SMTP                |
| `SMTP_USER`| `seu@gmail.com`      | Usuário / email de login     |
| `SMTP_PASS`| `xxxx xxxx xxxx xxxx`| Senha ou **App Password**    |

## Variáveis opcionais

| Variável         | Exemplo                    | Descrição |
|------------------|----------------------------|-----------|
| `SMTP_PORT`      | `587`                      | Porta (default: 587) |
| `SMTP_FROM_NAME` | `Senior Floors LP`         | Nome que aparece no "De:" |
| `SMTP_FROM_EMAIL`| `noreply@seudominio.com`   | Email do "De:" (default: SMTP_USER) |
| `SMTP_TO_EMAIL`  | `leads@seudominio.com`     | Email que **recebe** os leads (default: SMTP_FROM_EMAIL) |
| `SMTP_SECURE`    | `true`                     | Use `true` para porta 465 (SSL) |

## Gmail

1. Ative verificação em 2 etapas na conta Google.
2. Gere uma **Senha de app**: Google Account → Segurança → Senhas de app.
3. Use `SMTP_HOST=smtp.gmail.com`, `SMTP_PORT=587`, usuário = seu Gmail, `SMTP_PASS` = senha de app (16 caracteres, sem espaços ou com espaços — o código remove espaços).

## Outros provedores

- **Outlook/Office 365:** `smtp.office365.com`, porta 587, usuário completo e senha da conta.
- **Hostinger / cPanel:** use o host SMTP do painel (ex.: `smtp.hostinger.com`), porta 587 ou 465, email e senha da caixa de email.

Depois de salvar as variáveis no Vercel, faça um novo deploy para aplicar. O envio de email é opcional: se não configurar SMTP, os leads continuam sendo salvos no sistema (SYSTEM_API_URL) e a resposta da API segue igual.

## Se os emails não chegarem (troubleshooting)

1. **Veja o erro na resposta da API**  
   Após enviar o form, abra **DevTools (F12)** → aba **Rede/Network** → envie de novo o formulário → clique na requisição **send-lead** (ou **api/send-lead**) → aba **Resposta**.  
   Se o email falhar, a resposta JSON terá `"email_sent": false` e `"email_error": "código ou mensagem"`.

2. **O que pode significar `email_error`:**
   - **`EAUTH`** ou **Invalid login** → usuário/senha SMTP errados. Gmail: use **Senha de app**, não a senha normal.
   - **`MODULE_NOT_FOUND`** ou **Cannot find module 'nodemailer'** → o projeto no Vercel precisa ter um `package.json` na raiz com `"nodemailer"` em `dependencies`. Faça push do `package.json` e redeploy.
   - **`ESOCKET`** / **ETIMEDOUT** → firewall ou porta bloqueada; teste com porta **587** (ou 465 com `SMTP_SECURE=true`).
   - **`SMTP not fully configured`** → falta uma das variáveis: SMTP_HOST, SMTP_USER ou SMTP_PASS.

3. **Console do navegador**  
   Se o email falhar, a LP mostra no console (F12 → Console) algo como: `[LP] Email não enviado: EAUTH`.

4. **Repositório no Vercel**  
   O build da Vercel usa o `package.json` da raiz do repositório. Se o repo **senior-floors-lp** tiver um `package.json` com `nodemailer` em `dependencies`, o envio por email funcionará. Caso contrário, adicione e faça deploy de novo.
