# 🗄️ Fazer Banco de Dados Funcionar

## Status Atual
- ✅ CSV está funcionando perfeitamente
- ❌ Banco de dados precisa ser configurado

## O Que Precisa Ser Feito

### 1. Verificar se o Banco Existe

**Via File Manager ou phpMyAdmin:**
1. Acesse o painel Hostinger
2. Vá em **MySQL Databases** ou **phpMyAdmin**
3. Veja se já existe um banco de dados criado

### 2. Se o Banco NÃO Existe: Criar Banco

**No painel Hostinger:**
1. Vá em **MySQL Databases**
2. Clique em **Create Database**
3. Nome: `senior_floors_db` (ou outro nome)
4. **Anote o nome completo** (geralmente `usuario_senior_floors_db`)

### 3. Criar Tabelas

**Via phpMyAdmin:**
1. Acesse phpMyAdmin
2. Selecione seu banco de dados
3. Clique na aba **SQL**
4. Cole o código de `database/schema.sql`
5. Clique em **Go** ou **Executar**

**Ou via File Manager:**
- Execute o arquivo `database/schema.sql` no phpMyAdmin

### 4. Configurar Credenciais

**Edite o arquivo `config/database.php`:**

1. **Via File Manager:**
   - Acesse `public_html/config/database.php`
   - Clique em **Edit**

2. **Atualize as linhas:**
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u485294289_senior_floors_db'); // Nome COMPLETO do banco
   define('DB_USER', 'u485294289_usuario');           // Nome COMPLETO do usuário
   define('DB_PASS', 'SuaSenhaRealAqui');             // Senha real
   ```

3. **Salve o arquivo**

### 5. Testar Conexão

**Acesse:**
```
https://seudominio.com/test-db-completo.php
```

Deve mostrar:
- ✅ Conexão estabelecida
- ✅ Tabelas existem
- ✅ Pode inserir dados

### 6. Testar Formulário

1. Preencha o formulário na LP
2. Envie
3. Verifique no CRM se aparece
4. O CRM deve mostrar: **"Fonte de dados: MySQL Database"**

## Verificar se Está Funcionando

### No CRM:
- Deve mostrar: **"📊 Fonte de dados: MySQL Database"**
- Os leads devem aparecer na lista

### No Banco:
- Acesse phpMyAdmin
- Selecione seu banco
- Veja a tabela `leads`
- Deve ter os leads salvos

## Problemas Comuns

### Problema 1: "Access denied"
**Causa:** Usuário ou senha incorretos
**Solução:** Verifique as credenciais em `config/database.php`

### Problema 2: "Unknown database"
**Causa:** Nome do banco incorreto
**Solução:** Use o nome COMPLETO (com prefixo do usuário)

### Problema 3: "Table doesn't exist"
**Causa:** Schema não foi executado
**Solução:** Execute `database/schema.sql` no phpMyAdmin

## Arquivos Importantes

- `config/database.php` - Credenciais do banco
- `database/schema.sql` - Estrutura das tabelas
- `test-db-completo.php` - Teste de conexão

---

**Última atualização:** 24/01/2025
