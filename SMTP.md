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
