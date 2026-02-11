# Database Schema

## Como executar o schema

### ⚡ Opção 1: Via Script Node.js (Mais Fácil - Railway)

Se você tem Railway CLI instalado:

```bash
# No diretório do projeto
railway run node database/run-schema.js
```

O script detecta automaticamente as variáveis do Railway MySQL (`MYSQLHOST`, `MYSQLDATABASE`, etc.) e executa o schema.

**Sem Railway CLI?** Veja outras opções abaixo ou instale: `npm install -g @railway/cli`

### Opção 2: Via MySQL CLI

1. No Railway MySQL → **"Data"** → copie as credenciais (`MYSQLHOST`, `MYSQLUSER`, etc.)
2. Execute:

```bash
mysql -h MYSQLHOST -P MYSQLPORT -u MYSQLUSER -pMYSQLPASSWORD MYSQLDATABASE < database/schema.sql
```

### Opção 3: Via Ferramenta GUI (MySQL Workbench, DBeaver, TablePlus)

1. No Railway MySQL → **"Data"** → **"Connect"** → copie a connection string
2. Conecte com MySQL Workbench/DBeaver/TablePlus
3. Execute o conteúdo de `schema.sql`

### Opção 4: Via phpMyAdmin (Hostinger)

1. Acesse phpMyAdmin no Hostinger
2. Selecione seu banco de dados
3. Vá em **"SQL"**
4. Cole o conteúdo de `schema.sql`
5. Clique em **"Executar"**

### Opção 3: Via linha de comando MySQL

```bash
mysql -h DB_HOST -u DB_USER -p DB_NAME < database/schema.sql
```

Ou conecte e execute:

```bash
mysql -h DB_HOST -u DB_USER -p
USE DB_NAME;
SOURCE database/schema.sql;
```

## Estrutura da tabela `leads`

- **id** — Primary key, auto increment
- **name** — Nome do lead
- **email** — Email do lead
- **phone** — Telefone
- **zipcode** — CEP (5 dígitos)
- **message** — Mensagem opcional
- **source** — Origem (LP-Hero, LP-Contact)
- **form_type** — Tipo de formulário (hero-form, contact-form)
- **status** — Status (new, contacted, qualified, converted, lost)
- **priority** — Prioridade (low, medium, high)
- **ip_address** — IP do visitante
- **owner_id** — ID do usuário responsável (opcional)
- **pipeline_stage_id** — ID do estágio no pipeline (opcional)
- **created_at** — Data de criação
- **updated_at** — Data de atualização

## Após executar

Teste novamente:
```
https://sua-url-railway.up.railway.app/api/db-check
```

Deve retornar: `"table_leads_exists": true`
