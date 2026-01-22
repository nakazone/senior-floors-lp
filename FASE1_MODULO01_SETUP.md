# FASE 1 - MÓDULO 01: Central de Leads
## Sistema de Banco de Dados MySQL + API Endpoint

## 📋 O QUE FOI CRIADO

### 1. Estrutura de Banco de Dados
- **Arquivo**: `database/schema.sql`
- **Tabelas criadas**:
  - `leads` - Tabela principal de leads
  - `lead_tags` - Tags para qualificação (FASE 2)
  - `lead_notes` - Observações internas (FASE 2)

### 2. Configuração de Banco de Dados
- **Arquivo**: `config/database.php`
- Gerencia conexão PDO com MySQL
- Função de verificação de configuração

### 3. API Endpoint
- **Arquivo**: `api/leads/create.php`
- **Endpoint**: `POST /api/leads/create.php`
- Recebe dados do formulário
- Salva no MySQL + CSV (backup)

## 🚀 INSTALAÇÃO

### Passo 1: Criar Banco de Dados no Hostinger

1. Acesse o **cPanel** do Hostinger
2. Vá em **MySQL Databases**
3. Crie um novo banco de dados:
   - Nome: `senior_floors_db` (ou outro nome)
   - Anote o nome completo (geralmente `usuario_senior_floors_db`)

4. Crie um usuário MySQL:
   - Usuário: `senior_floors_user` (ou outro)
   - Senha: (crie uma senha forte)
   - Anote as credenciais

5. Adicione o usuário ao banco de dados com **ALL PRIVILEGES**

### Passo 2: Configurar Credenciais

1. Abra `config/database.php`
2. Atualize as constantes:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'seu_usuario_senior_floors_db'); // Nome completo do banco
   define('DB_USER', 'seu_usuario_senior_floors_user'); // Usuário criado
   define('DB_PASS', 'sua_senha_aqui'); // Senha criada
   ```

### Passo 3: Criar Tabelas

1. No cPanel, vá em **phpMyAdmin**
2. Selecione seu banco de dados
3. Clique em **SQL**
4. Copie e cole o conteúdo de `database/schema.sql`
5. Clique em **Executar**

### Passo 4: Verificar Estrutura de Pastas

Certifique-se de que a estrutura está assim:
```
public_html/
├── api/
│   └── leads/
│       └── create.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── lp/
│   └── send-lead.php (já existe)
└── system.php (já existe)
```

## 🔄 INTEGRAÇÃO COM FORMULÁRIO EXISTENTE

O endpoint `api/leads/create.php` está pronto para receber dados do formulário.

**Compatibilidade:**
- ✅ Salva no MySQL (novo)
- ✅ Salva no CSV (backup/compatibilidade)
- ✅ Mantém funcionamento atual

**Próximo passo:** Atualizar `send-lead.php` para também chamar o novo endpoint (opcional, pois já salva em CSV).

## 📊 ESTRUTURA DA TABELA `leads`

```sql
- id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR 255)
- email (VARCHAR 255)
- phone (VARCHAR 50)
- zipcode (VARCHAR 10)
- message (TEXT)
- source (VARCHAR 50) - 'LP-Hero', 'LP-Contact', etc.
- form_type (VARCHAR 50) - 'hero-form', 'contact-form'
- status (ENUM) - 'new', 'contacted', 'qualified', 'proposal', 'closed_won', 'closed_lost'
- priority (ENUM) - 'low', 'medium', 'high'
- ip_address (VARCHAR 45)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## ✅ TESTE

### Teste Manual via cURL:

```bash
curl -X POST https://seudominio.com/api/leads/create.php \
  -d "form-name=contact-form" \
  -d "name=Test User" \
  -d "email=test@example.com" \
  -d "phone=7205551234" \
  -d "zipcode=80202" \
  -d "message=Test message"
```

### Resposta Esperada:

```json
{
  "success": true,
  "message": "Lead created successfully",
  "data": {
    "lead_id": 1,
    "saved_to_db": true,
    "saved_to_csv": true
  },
  "timestamp": "2024-01-20 10:30:00"
}
```

## 🔍 VERIFICAÇÃO

1. **Verificar no banco:**
   - Acesse phpMyAdmin
   - Selecione seu banco
   - Veja a tabela `leads`
   - Deve ter o registro criado

2. **Verificar CSV:**
   - Abra `public_html/leads.csv`
   - Deve ter o mesmo registro

3. **Verificar logs:**
   - Abra `public_html/api-leads.log`
   - Deve mostrar o registro

## ⚠️ TROUBLESHOOTING

### Erro: "Database connection error"
- Verifique credenciais em `config/database.php`
- Verifique se o banco existe
- Verifique se o usuário tem permissões

### Erro: "Table doesn't exist"
- Execute o `schema.sql` no phpMyAdmin
- Verifique se está no banco correto

### Erro: "Access denied"
- Verifique usuário e senha
- Verifique se o usuário tem ALL PRIVILEGES no banco

## 📝 PRÓXIMOS PASSOS

Após instalar o MÓDULO 01:
- ✅ Leads serão salvos no MySQL
- ✅ CSV continua funcionando (backup)
- ✅ Pronto para MÓDULO 02 (Telegram Alerts)
- ✅ Pronto para MÓDULO 03 (Email Confirmation)
