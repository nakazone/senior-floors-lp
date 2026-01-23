# 🗄️ Configurar Banco de Dados - Passo a Passo AGORA

**Guia completo e simplificado para configurar o MySQL no Hostinger**

---

## 📋 O QUE VOCÊ VAI FAZER

1. Criar banco de dados MySQL no Hostinger
2. Criar usuário MySQL
3. Associar usuário ao banco
4. Executar schema SQL (criar tabelas)
5. Configurar `config/database.php` com as credenciais

**Tempo estimado:** 10-15 minutos

---

## 🚀 PASSO 1: Acessar Painel do Hostinger

1. **Acesse:** https://hpanel.hostinger.com
2. **Faça login** com suas credenciais
3. **Selecione seu domínio** (se tiver múltiplos)

---

## 🗄️ PASSO 2: Criar Banco de Dados MySQL

### 2.1. Encontrar MySQL Databases

1. **No menu lateral esquerdo**, procure por:
   - **"MySQL Databases"** ou
   - **"Databases"** ou
   - **"MySQL"**

2. **Clique** para abrir

### 2.2. Criar Novo Banco

1. **Procure pela seção "Create Database"** ou **"New Database"**

2. **Preencha:**
   - **Database Name:** `senior_floors_db` (ou outro nome de sua escolha)
   - ⚠️ **IMPORTANTE:** Anote o nome completo que aparecerá (geralmente `usuario_senior_floors_db`)

3. **Clique em "Create"** ou **"Create Database"**

4. **✅ Anote o nome completo do banco** (você vai precisar!)

---

## 👤 PASSO 3: Criar Usuário MySQL

### 3.1. Criar Novo Usuário

1. **Na mesma página, procure por "MySQL Users"** ou **"Create User"**

2. **Preencha:**
   - **Username:** Escolha um nome (ex: `senior_floors_user`)
   - ⚠️ **IMPORTANTE:** Anote o nome completo (geralmente `usuario_senior_floors_user`)
   - **Password:** 
     - Clique em **"Generate"** para gerar uma senha forte
     - **OU** digite uma senha forte
   - ⚠️ **MUITO IMPORTANTE:** Anote a senha! Você não poderá vê-la depois!

3. **Clique em "Create"** ou **"Create User"**

4. **✅ Anote:**
   - Nome completo do usuário
   - Senha

---

## 🔗 PASSO 4: Associar Usuário ao Banco

### 4.1. Adicionar Usuário ao Banco

1. **Procure por "Add User to Database"** ou **"Manage Users"**

2. **Selecione:**
   - **User:** Seu usuário MySQL (ex: `usuario_senior_floors_user`)
   - **Database:** Seu banco (ex: `usuario_senior_floors_db`)

3. **Marque as permissões:**
   - ✅ **ALL PRIVILEGES** (todas as permissões)
   - Ou marque todas as opções disponíveis

4. **Clique em "Add"** ou **"Add User to Database"**

5. **✅ Confirmação:** Você verá uma mensagem de sucesso

---

## 📝 PASSO 5: Executar Schema SQL (Criar Tabelas)

### Opção A: Via phpMyAdmin (Mais Fácil)

1. **No painel Hostinger, procure por "phpMyAdmin"**
   - Geralmente no menu lateral ou em "Advanced"

2. **Clique em "phpMyAdmin"**

3. **Selecione seu banco de dados:**
   - No menu lateral esquerdo, clique no nome do seu banco
   - Exemplo: `usuario_senior_floors_db`

4. **Clique na aba "SQL"** (no topo)

5. **Cole o seguinte código SQL:**

```sql
-- ============================================
-- Senior Floors - Database Schema
-- FASE 1 - MÓDULO 01: Central de Leads
-- ============================================

-- Tabela principal de leads
CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `zipcode` VARCHAR(10) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `source` VARCHAR(50) DEFAULT 'LP' COMMENT 'LP, Website, Ads, etc.',
  `form_type` VARCHAR(50) DEFAULT 'contact-form' COMMENT 'hero-form, contact-form',
  `status` ENUM('new', 'contacted', 'qualified', 'proposal', 'closed_won', 'closed_lost') DEFAULT 'new',
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_source` (`source`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tags (para MÓDULO 05)
CREATE TABLE IF NOT EXISTS `lead_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `tag` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_tag` (`tag`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de observações internas (para MÓDULO 04)
CREATE TABLE IF NOT EXISTS `lead_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `note` TEXT NOT NULL,
  `created_by` VARCHAR(100) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

6. **Clique em "Go"** ou **"Executar"**

7. **✅ Verifique se apareceu:**
   - "3 queries executed successfully" ou
   - Mensagem de sucesso

8. **Verifique se as tabelas foram criadas:**
   - No menu lateral esquerdo, você deve ver:
     - `leads`
     - `lead_tags`
     - `lead_notes`

### Opção B: Via Arquivo SQL (Alternativa)

1. **Baixe o arquivo:** `database/schema.sql` do repositório GitHub

2. **No phpMyAdmin:**
   - Selecione seu banco
   - Clique em "Import" (aba no topo)
   - Clique em "Choose File"
   - Selecione o arquivo `schema.sql`
   - Clique em "Go"

---

## ⚙️ PASSO 6: Configurar `config/database.php`

### 6.1. Encontrar o Arquivo

No servidor Hostinger, o arquivo está em:
```
public_html/config/database.php
```

### 6.2. Editar o Arquivo

**Via File Manager do Hostinger:**

1. **No painel Hostinger, procure por "File Manager"**

2. **Navegue até:** `public_html/config/`

3. **Clique em `database.php`** → **"Edit"**

4. **Atualize os valores:**

```php
// ANTES (valores padrão):
define('DB_NAME', 'senior_floors_db');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');

// DEPOIS (seus valores reais):
define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_senior_floors_db');  // ← Nome completo do banco
define('DB_USER', 'usuario_senior_floors_user'); // ← Nome completo do usuário
define('DB_PASS', 'sua_senha_gerada_aqui');      // ← Senha que você anotou
define('DB_CHARSET', 'utf8mb4');
```

5. **Salve o arquivo**

**Via FTP/SSH:**

1. **Baixe o arquivo** `config/database.php` do servidor

2. **Edite localmente** com as credenciais

3. **Faça upload** de volta para o servidor

---

## ✅ PASSO 7: Testar se Funcionou

### 7.1. Teste Rápido

1. **Acesse:** `https://seudominio.com/system.php?module=crm`

2. **Verifique a mensagem no topo:**
   - ✅ **"📊 Fonte de dados: MySQL Database ✅ Banco de dados ativo"** = FUNCIONOU!
   - ❌ **"📊 Fonte de dados: CSV File ⚠️"** = Ainda não configurado

### 7.2. Teste com Formulário

1. **Envie um formulário de teste** na landing page

2. **Acesse o CRM:**
   - O lead deve aparecer na lista
   - Deve mostrar "MySQL Database" como fonte

3. **Verifique no phpMyAdmin:**
   - Acesse phpMyAdmin
   - Selecione seu banco
   - Clique na tabela `leads`
   - Você deve ver o lead que acabou de criar!

---

## 🔍 Verificar Informações no Hostinger

### Como Encontrar o Nome Completo do Banco:

1. **No painel Hostinger → MySQL Databases**
2. **Procure pela lista de databases**
3. **O nome completo geralmente é:** `usuario_nome_do_banco`
   - Exemplo: Se seu usuário é `u123456789` e você criou `senior_floors_db`
   - O nome completo será: `u123456789_senior_floors_db`

### Como Encontrar o Nome Completo do Usuário:

1. **No painel Hostinger → MySQL Databases**
2. **Procure por "MySQL Users"**
3. **O nome completo geralmente é:** `usuario_nome_do_usuario`
   - Exemplo: `u123456789_senior_floors_user`

---

## ⚠️ Problemas Comuns

### 1. "Access denied for user"

**Causa:** Usuário não tem permissões no banco

**Solução:**
- Volte ao Passo 4
- Certifique-se de que associou o usuário ao banco
- Marque **ALL PRIVILEGES**

### 2. "Unknown database"

**Causa:** Nome do banco está errado

**Solução:**
- Verifique o nome completo no painel Hostinger
- Use o nome completo (com prefixo do usuário)
- Exemplo: `u123456789_senior_floors_db`

### 3. "Table doesn't exist"

**Causa:** Schema SQL não foi executado

**Solução:**
- Volte ao Passo 5
- Execute o schema SQL novamente
- Verifique se as 3 tabelas foram criadas

### 4. Ainda mostra "CSV File"

**Causa:** `config/database.php` ainda tem valores padrão

**Solução:**
- Verifique se editou o arquivo no servidor
- Certifique-se de que salvou as alterações
- Verifique se não há erros de digitação

---

## 📋 Checklist Final

- [ ] Banco de dados criado no Hostinger
- [ ] Nome completo do banco anotado
- [ ] Usuário MySQL criado
- [ ] Nome completo do usuário anotado
- [ ] Senha do usuário anotada
- [ ] Usuário associado ao banco (ALL PRIVILEGES)
- [ ] Schema SQL executado (3 tabelas criadas)
- [ ] `config/database.php` editado com credenciais reais
- [ ] Arquivo salvo no servidor
- [ ] Teste no CRM mostra "MySQL Database"
- [ ] Formulário de teste salva no banco

---

## 🎯 Próximos Passos Após Configurar

1. **Testar formulário** na landing page
2. **Verificar no CRM** se aparece "MySQL Database"
3. **Verificar no phpMyAdmin** se o lead foi salvo
4. **Configurar Telegram** (se quiser)
5. **Configurar Email SMTP** (se quiser)

---

**Última atualização:** 23/01/2025
