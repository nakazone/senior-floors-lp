# Deploy da Landing Page na Vercel (Node.js)

A LP pode ser hospedada na **Vercel** com o backend em **Node.js** (serverless). A Vercel serve os estáticos (HTML, CSS, JS, assets) e as rotas `/api/send-lead`, `/api/receive-lead` e `/api/db-check` como funções serverless.

## O que está configurado

- **Estáticos:** `index.html`, `script.js`, `styles.css`, `assets/` — servidos pela Vercel.
- **API (Node.js serverless):**
  - `POST /api/send-lead` — recebe o formulário (validação, CSV em `/tmp`, opcional MySQL e e-mail).
  - `POST /api/receive-lead` — grava lead no MySQL (mesma lógica do `server/`).
  - `GET /api/db-check` — verifica conexão com o banco.
- **Rewrites:** `/send-lead` e `/send-lead.php` redirecionam para `/api/send-lead`.
- **Formulário:** Em domínios `*.vercel.app`, o `index.html` usa automaticamente `/api/send-lead` e `/api/receive-lead` (mesma origem).

## Deploy na Vercel

1. **Conectar o repositório**
   - Vercel Dashboard → Add New Project → Import Git Repository.
   - Escolha o repositório e a branch (ex.: `main`).

2. **Configuração do projeto**
   - Framework Preset: **Other** (ou None).
   - Build Command: pode deixar em branco.
   - Output Directory: em branco (a raiz é publicada).
   - Install Command: `npm install` (já está no `vercel.json`).

3. **Variáveis de ambiente (opcional)**
   - Em **Settings → Environment Variables** adicione, se quiser MySQL e e-mail:
     - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` — para gravar leads no banco.
     - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM_EMAIL`, `SMTP_TO_EMAIL` — para envio de e-mail.
   - Se não definir DB, os leads só serão gravados em CSV em `/tmp` (efémero) ou pode usar `SYSTEM_API_URL` para reenviar para outro backend.

4. **Deploy**
   - Clique em Deploy. A Vercel faz `npm install` e publica os ficheiros e as funções em `api/*.js`.

## URLs após o deploy

- **LP:** `https://seu-projeto.vercel.app/`
- **Formulário:** envia para `https://seu-projeto.vercel.app/api/send-lead` (definido automaticamente no HTML quando o host é Vercel).
- **Fallback receive-lead:** `https://seu-projeto.vercel.app/api/receive-lead`
- **DB check:** `https://seu-projeto.vercel.app/api/db-check`

## Limitações na Vercel

- **Ficheiros:** O sistema de ficheiros é read-only exceto `/tmp`. Logs e CSV são escritos em `/tmp` (efémero; perdem-se entre invocações).
- **MySQL:** Funciona se configurar `DB_*` nas variáveis de ambiente (banco externo, ex.: Hostinger).
- **E-mail:** Funciona com Nodemailer se configurar `SMTP_*` nas variáveis de ambiente.

## Desenvolvimento local com Vercel CLI

```bash
npm install -g vercel
vercel dev
```

Abre o site em `http://localhost:3000` e as rotas `/api/*` usam as funções locais. Pode criar um `.env` na raiz (ou em `server/`) com as mesmas variáveis para testar DB e SMTP.

## Estrutura relevante

```
api/
  send-lead.js      → POST /api/send-lead
  receive-lead.js   → POST /api/receive-lead
  db-check.js       → GET /api/db-check
server/             → lógica partilhada (routes, config, lib)
index.html          → detecta Vercel e usa /api/send-lead
vercel.json         → rewrites e runtime Node
package.json        → type: "module", dependencies do server
```
