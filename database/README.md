# Database Schema

## Como executar o schema

### Opção 1: Via Railway MySQL Console

1. No Railway, vá no serviço MySQL → **"Data"** ou **"Query"**
2. Cole o conteúdo de `schema.sql`
3. Execute

### Opção 2: Via phpMyAdmin (Hostinger)

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
