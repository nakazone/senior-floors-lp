# Senior Floors — Backend em Node.js

Backend que **substitui** o fluxo PHP: `send-lead.php`, `system.php?api=receive-lead` e `api/receive-lead-handler.php`. Usa o **mesmo banco MySQL** e o mesmo esquema (tabela `leads`), então você pode rodar Node e PHP em paralelo ou migrar aos poucos.

## O que está implementado

| Funcionalidade | Rota | Equivalente PHP |
|----------------|------|------------------|
| Receber lead (gravar no banco) | `POST /api/receive-lead` | `api/receive-lead-handler.php` |
| Envio do formulário da LP | `POST /send-lead` ou `POST /send-lead.php` | `send-lead.php` |
| Verificar banco | `GET /api/db-check` | `system.php?api=db-check` |
| Listar leads | `GET /api/leads?page=1&limit=20` | `api/leads/list` (parcial) |
| Detalhe do lead | `GET /api/leads/:id` | `api/leads/get.php` |
| Compatibilidade URL | `POST /system.php?api=receive-lead` | `system.php` |

O `POST /send-lead` valida, grava em **CSV** (backup), grava no **MySQL** (se configurado) e opcionalmente envia **e-mail** (Nodemailer). Não chama mais o PHP.

## Requisitos

- **Node.js 18+** (para `fetch` nativo e ESM)
- **MySQL** com o schema já criado (por exemplo `database/schema-v3-completo.sql`)

## Instalação e execução

```bash
cd server
cp env.example .env
# Edite .env: DB_HOST, DB_NAME, DB_USER, DB_PASS (e opcionalmente SMTP_*, PORT)
npm install
npm start
```

O servidor sobe em `http://localhost:3000` (ou a `PORT` do `.env`).

## Configuração (.env)

- **DB_HOST**, **DB_NAME**, **DB_USER**, **DB_PASS** — mesmo que em `config/database.php` (obrigatório para salvar leads no banco).
- **PORT** — porta do servidor (padrão 3000).
- **SMTP_*** — opcional; se preenchido, o `POST /send-lead` envia e-mail (Nodemailer).
- **LEADS_CSV_PATH** — opcional; caminho do arquivo CSV (padrão: `../leads.csv`).
- **SYSTEM_API_URL** — opcional; se o Node não gravar no banco (ex.: sem DB), o send-lead pode chamar essa URL para outro backend gravar.

## Usar a LP com o servidor Node

1. Subir o Node: `cd server && npm start`.
2. Apontar a LP para o Node:
   - Se a LP estiver no mesmo host (servido pelo Express): abrir `http://localhost:3000`; o formulário já pode usar `action="/send-lead"` ou o `script.js` apontar para `http://localhost:3000/send-lead`.
   - Se a LP estiver em outro domínio (ex.: `lp.senior-floors.com`): em `script.js` / `index.html`, definir a URL do formulário para `https://seu-dominio.com/send-lead` (onde estiver rodando o Node).

## Migração completa do sistema PHP → Node

O projeto PHP tem dezenas de endpoints (CRM, orçamentos, visitas, usuários, etc.). Este servidor Node cobre:

- Fluxo de **captura de leads** (receive-lead + send-lead + CSV + e-mail).
- **db-check** e **leads** (list + get).

Para migrar o **resto** (painel admin, CRM, quotes, visits, etc.):

1. **Opção A — Substituir apenas o backend:**  
   Manter o front do painel (HTML/JS) e ir recriando cada endpoint PHP em Node (ex.: `api/quotes/*`, `api/visits/*`, `api/users/*`) neste mesmo Express. O front continuaria chamando as mesmas URLs, agora no Node.

2. **Opção B — Novo painel:**  
   Criar um painel novo (React, Vue, ou HTML estático + JS) que consome apenas as APIs Node. Aí você pode desligar o PHP quando todas as funções estiverem na API Node.

3. **Ordem sugerida para portar:**  
   - receive-lead / send-lead / db-check (já feitos)  
   - leads: list, get, update, search (parcial já)  
   - auth/session (login do painel)  
   - pipeline, activities, visits, quotes, users (conforme uso)

## Estrutura do servidor

```
server/
├── index.js              # Express, CORS, rotas
├── config/
│   └── db.js             # Pool MySQL (env)
├── lib/
│   └── leadLogic.js      # Duplicata, round-robin owner
├── routes/
│   ├── receiveLead.js    # POST /api/receive-lead
│   ├── sendLead.js       # POST /send-lead
│   ├── dbCheck.js        # GET /api/db-check
│   └── leads.js          # GET /api/leads, GET /api/leads/:id
├── package.json
├── env.example
└── README.md
```

Logs (opcionais): `server/lead-db-save.log`, `server/system-api.log` (na pasta `server/`).
