# 🗄️ Configurar Banco de Dados - Passo a Passo Completo

## 📋 O Que Você Vai Fazer

1. Criar banco de dados MySQL no Hostinger
2. Criar usuário MySQL
3. Executar script SQL para criar tabelas
4. Configurar arquivo `config/database.php`
5. Testar conexão

---

## ✅ PASSO 1: Acessar cPanel do Hostinger

1. Acesse seu painel do Hostinger
2. Faça login
3. Vá para **cPanel** (ou painel de controle)

---

## ✅ PASSO 2: Criar Banco de Dados

1. No cPanel, procure por **MySQL Databases** ou **Bancos de Dados MySQL**
2. Role até a seção **Create New Database** (Criar Novo Banco de Dados)
3. Digite um nome para o banco:
   - Exemplo: `senior_floors_db`
   - **IMPORTANTE:** O nome completo será: `seu_usuario_senior_floors_db`
   - **ANOTE O NOME COMPLETO!** (você vai precisar)
4. Clique em **Create Database** (Criar Banco de Dados)
5. ✅ Banco criado!

---

## ✅ PASSO 3: Criar Usuário MySQL

1. Na mesma página, role até **MySQL Users** (Usuários MySQL)
2. Em **Username**, digite:
   - Exemplo: `senior_floors_user`
   - **IMPORTANTE:** O nome completo será: `seu_usuario_senior_floors_user`
   - **ANOTE O NOME COMPLETO!**
3. Em **Password**, crie uma senha forte:
   - Use gerador de senha ou crie uma senha segura
   - **ANOTE A SENHA!** (você vai precisar)
4. Clique em **Create User** (Criar Usuário)
5. ✅ Usuário criado!

---

## ✅ PASSO 4: Conectar Usuário ao Banco

1. Na mesma página, role até **Add User To Database** (Adicionar Usuário ao Banco)
2. Selecione o **usuário** que você criou
3. Selecione o **banco de dados** que você criou
4. Clique em **Add** (Adicionar)
5. Na próxima tela, marque **ALL PRIVILEGES** (Todas as Privilégios)
6. Clique em **Make Changes** (Fazer Alterações)
7. ✅ Usuário conectado ao banco!

---

## ✅ PASSO 5: Acessar phpMyAdmin

1. No cPanel, procure por **phpMyAdmin**
2. Clique para abrir
3. No menu lateral esquerdo, encontre seu banco de dados
4. Clique no nome do banco para selecioná-lo

---

## ✅ PASSO 6: Executar Script SQL

1. No phpMyAdmin, clique na aba **SQL** (no topo)
2. Abra o arquivo `database/schema.sql` do seu projeto
3. **Copie TODO o conteúdo** do arquivo
4. Cole no campo SQL do phpMyAdmin
5. Clique em **Executar** (Go)
6. ✅ Tabelas criadas!

**Você deve ver:**
- ✅ Tabela `leads` criada
- ✅ Tabela `lead_tags` criada
- ✅ Tabela `lead_notes` criada

---

## ✅ PASSO 7: Configurar config/database.php

1. No Hostinger, acesse **File Manager** (Gerenciador de Arquivos)
2. Navegue até: `public_html/config/`
3. Abra o arquivo `database.php`
4. Edite as linhas:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'SEU_USUARIO_senior_floors_db');  // Nome COMPLETO do banco
define('DB_USER', 'SEU_USUARIO_senior_floors_user'); // Nome COMPLETO do usuário
define('DB_PASS', 'SUA_SENHA_AQUI');                  // Senha que você criou
```

**Exemplo real:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_senior_floors_db');
define('DB_USER', 'u123456789_senior_user');
define('DB_PASS', 'MinhaSenh@Segura123!');
```

5. Salve o arquivo

---

## ✅ PASSO 8: Testar Conexão

Crie um arquivo de teste:

1. No File Manager, crie: `public_html/test-db.php`
2. Cole este código:

```php
<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Teste de Conexão MySQL</h2>";

if (isDatabaseConfigured()) {
    echo "<p style='color: green;'>✅ Configuração OK</p>";
    
    $pdo = getDBConnection();
    
    if ($pdo) {
        echo "<p style='color: green;'>✅ Conexão com banco OK!</p>";
        
        // Testar se tabelas existem
        $tables = ['leads', 'lead_tags', 'lead_notes'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✅ Tabela '$table' existe</p>";
            } else {
                echo "<p style='color: red;'>❌ Tabela '$table' NÃO existe</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Erro ao conectar ao banco</p>";
        echo "<p>Verifique as credenciais em config/database.php</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Configuração incompleta</p>";
    echo "<p>Verifique config/database.php</p>";
}
?>
```

3. Acesse: `https://seudominio.com/test-db.php`
4. Você deve ver mensagens de sucesso ✅

---

## ✅ PASSO 9: Testar Sistema Completo

1. Acesse sua landing page
2. Preencha e envie o formulário
3. Verifique no phpMyAdmin:
   - Abra a tabela `leads`
   - Você deve ver o lead que acabou de enviar!

---

## 📝 Checklist Completo

- [ ] Banco de dados criado no Hostinger
- [ ] Nome completo do banco anotado
- [ ] Usuário MySQL criado
- [ ] Nome completo do usuário anotado
- [ ] Senha do usuário anotada
- [ ] Usuário conectado ao banco (ALL PRIVILEGES)
- [ ] phpMyAdmin acessado
- [ ] Script `database/schema.sql` executado
- [ ] Tabelas criadas (leads, lead_tags, lead_notes)
- [ ] `config/database.php` configurado com credenciais
- [ ] Teste de conexão executado (`test-db.php`)
- [ ] Formulário testado e lead salvo no banco

---

## 🆘 Problemas Comuns

### Erro: "Access denied"
- Verifique se o usuário tem ALL PRIVILEGES no banco
- Verifique se a senha está correta

### Erro: "Unknown database"
- Verifique se o nome do banco está completo (com prefixo do usuário)
- Exemplo: `u123456789_senior_floors_db` (não só `senior_floors_db`)

### Erro: "Table doesn't exist"
- Execute o `schema.sql` novamente no phpMyAdmin
- Verifique se está no banco correto

### Erro: "Connection refused"
- Verifique se `DB_HOST` está como `localhost`
- No Hostinger geralmente é `localhost`

---

## 📞 Onde Encontrar Informações no Hostinger

### Nome Completo do Banco/Usuário:
- cPanel → MySQL Databases
- Veja a lista de bancos/usuários criados
- O nome completo aparece lá

### Credenciais:
- cPanel → MySQL Databases
- Clique em "Show" ao lado da senha para ver

### phpMyAdmin:
- cPanel → phpMyAdmin
- Ou acesse diretamente via URL fornecida

---

## ✅ Pronto!

Depois de seguir todos os passos:
- ✅ Banco configurado
- ✅ Tabelas criadas
- ✅ Sistema conectado
- ✅ Formulários salvando no MySQL

**Agora seus leads serão salvos no banco de dados MySQL!** 🎉
