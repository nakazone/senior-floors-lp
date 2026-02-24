# Deploy – Senior Floors

## 1. Landing Page (LP) na Vercel

### Primeira vez (login)
```bash
npx vercel login
```
Siga o link no terminal para autorizar no navegador.

### Deploy em produção
Na **raiz do projeto** (onde está `index.html` e `vercel.json`):

```bash
npx vercel --prod
```

Ou, se já tiver o projeto linkado:
```bash
vercel --prod
```

### Variáveis na Vercel
No [Vercel Dashboard](https://vercel.com) → projeto → **Settings** → **Environment Variables**:

- `SYSTEM_API_URL` = URL do sistema no Railway (ex: `https://xxx.up.railway.app`)
- (Opcional) SMTP_* para e-mail de leads

---

## 2. Sistema (CRM) no Railway

O Railway costuma fazer deploy automático ao dar **push** no repositório conectado.

### Se o projeto Railway usa este repositório
- No Railway: **Settings** do serviço → **Root Directory** = `senior-floors-system`
- **Start Command**: `npm start` (ou deixe em branco para usar o do `package.json`)
- Variáveis: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (ou `DATABASE_URL`), `SESSION_SECRET`

### Disparar deploy manualmente
1. Faça commit e push das alterações:
   ```bash
   git add .
   git commit -m "Deploy: atualizações"
   git push origin main
   ```
2. Se o Railway estiver conectado ao GitHub, o deploy inicia sozinho.

### Ou usando Railway CLI
```bash
npm i -g @railway/cli
railway login
railway link   # escolha o projeto
cd senior-floors-system
railway up
```

---

## Resumo rápido

| O quê        | Onde     | Como                          |
|-------------|----------|--------------------------------|
| LP + /send-lead | Vercel  | `npx vercel login` → `npx vercel --prod` |
| CRM (Node)  | Railway  | Push no GitHub ou `railway up` na pasta `senior-floors-system` |

Depois do deploy da LP, confira se `SYSTEM_API_URL` está apontando para a URL do Railway.
