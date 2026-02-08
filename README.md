# Senior Floors – Landing Page (Node.js)

Landing Page da Senior Floors para Vercel. Contém apenas os arquivos necessários para a LP: HTML, CSS, JS, assets e backend Node.js (formulários em serverless).

O **Sistema** (CRM, painel admin) é hospedado separadamente (ex.: Railway).

## Conteúdo

- **LP:** `index.html`, `script.js`, `styles.css`, `assets/`
- **Backend Node (Vercel serverless):** `api/send-lead.js`, `api/receive-lead.js`, `api/db-check.js`
- **Lógica partilhada:** `server/` (config, routes, lib)
- **Config:** `package.json`, `vercel.json`

## Deploy na Vercel

1. Conecte o repositório no [Vercel](https://vercel.com) (Import Git Repository).
2. Framework: **Other**. Build e Output podem ficar em branco; Install: `npm install`.
3. (Opcional) Variáveis de ambiente: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` para MySQL; `SMTP_*` para e-mail; `SYSTEM_API_URL` para reenviar leads ao Sistema (ex.: Railway).
4. Deploy. A LP fica em `https://seu-projeto.vercel.app/` e o formulário envia para `/api/send-lead`.

Ver **[VERCEL.md](VERCEL.md)** para detalhes.

## Desenvolvimento local

```bash
npm install
npm start
```

Abre em `http://localhost:3000` (servidor Node em `server/`).

## Integração com o Sistema (Railway)

Se o CRM/Sistema estiver no Railway, defina na Vercel a variável **`SYSTEM_API_URL`** com a URL do backend (ex.: `https://seu-app.railway.app`). O `send-lead` pode reenviar o lead para `SYSTEM_API_URL/api/receive-lead` quando não houver MySQL configurado na Vercel.

---

Senior Floors – Denver Hardwood Flooring
