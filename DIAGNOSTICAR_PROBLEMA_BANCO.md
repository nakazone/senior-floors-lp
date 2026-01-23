# 🔍 Diagnosticar Problema do Banco de Dados

**Problema:** Configurei o banco mas ainda não está funcionando

---

## 🧪 PASSO 1: Executar Teste de Diagnóstico

1. **Faça upload do arquivo `test-db-completo.php` para o servidor:**
   - Localização: `public_html/test-db-completo.php`

2. **Acesse no navegador:**
   ```
   https://seudominio.com/test-db-completo.php
   ```

3. **Veja os resultados:**
   - O script vai mostrar exatamente qual é o problema
   - Siga as instruções que aparecerem

---

## 🔍 Problemas Comuns e Soluções

### Problema 1: "isDatabaseConfigured() retorna FALSE"

**Causa:** O arquivo `config/database.php` ainda tem valores padrão

**Solução:**
1. Acesse File Manager no Hostinger
2. Vá em `public_html/config/database.php`
3. Verifique se os valores foram realmente alterados:
   ```php
   // ❌ ERRADO (valores padrão):
   define('DB_NAME', 'senior_floors_db');
   define('DB_USER', 'seu_usuario');
   define('DB_PASS', 'sua_senha');
   
   // ✅ CORRETO (valores reais):
   define('DB_NAME', 'u123456789_senior_floors_db');
   define('DB_USER', 'u123456789_senior_user');
   define('DB_PASS', 'SenhaReal123!');
   ```

4. **Certifique-se de SALVAR o arquivo após editar**

---

### Problema 2: "Access denied for user"

**Causa:** Usuário não tem permissões ou credenciais incorretas

**Solução:**
1. No painel Hostinger → MySQL Databases
2. Verifique se o usuário está associado ao banco
3. Certifique-se de que tem **ALL PRIVILEGES**
4. Verifique se a senha está correta

---

### Problema 3: "Unknown database"

**Causa:** Nome do banco está incorreto

**Solução:**
1. No painel Hostinger → MySQL Databases
2. Veja o nome completo do banco (com prefixo)
3. Use o nome COMPLETO no `config/database.php`
4. Exemplo: `u123456789_senior_floors_db` (não só `senior_floors_db`)

---

### Problema 4: "Table doesn't exist"

**Causa:** Schema SQL não foi executado

**Solução:**
1. Acesse phpMyAdmin
2. Selecione seu banco
3. Clique em "SQL"
4. Cole o código de `EXECUTAR_SQL_HOSTINGER.sql`
5. Clique em "Go"
6. Verifique se as 3 tabelas foram criadas

---

### Problema 5: Arquivo não foi atualizado no servidor

**Causa:** Editou localmente mas não fez upload

**Solução:**
1. **Via File Manager:**
   - Edite diretamente no servidor via File Manager do Hostinger
   
2. **Via FTP:**
   - Baixe `config/database.php` do servidor
   - Edite localmente
   - Faça upload de volta

3. **Via GitHub (se deploy funcionou):**
   - Edite localmente
   - Commit e push
   - Aguarde deploy automático

---

## 📋 Checklist de Verificação

Execute o teste e verifique:

- [ ] Arquivo `config/database.php` existe no servidor
- [ ] Constantes estão definidas (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- [ ] Valores NÃO são padrão (não são 'seu_usuario', 'sua_senha')
- [ ] `isDatabaseConfigured()` retorna TRUE
- [ ] Conexão com banco funciona
- [ ] Tabelas existem (leads, lead_tags, lead_notes)
- [ ] Teste de inserção funciona

---

## 🎯 Onde Está o Arquivo?

**No servidor Hostinger:**
```
public_html/config/database.php
```

**Você precisa editar ESTE arquivo no servidor**, não o local!

---

## 💡 Dica: Editar Diretamente no Servidor

A forma mais fácil:

1. **Acesse File Manager no Hostinger**
2. **Navegue até:** `public_html/config/`
3. **Clique em `database.php`**
4. **Clique em "Edit"** (ou ícone de lápis)
5. **Edite os valores**
6. **Clique em "Save"** ou "Salvar"

**Isso garante que as alterações estão no servidor!**

---

## 🆘 Se Ainda Não Funcionar

1. **Execute o teste:** `test-db-completo.php`
2. **Copie os erros** que aparecerem
3. **Verifique:**
   - Nome completo do banco (com prefixo)
   - Nome completo do usuário (com prefixo)
   - Senha correta
   - Usuário tem ALL PRIVILEGES
   - Tabelas foram criadas

---

**Última atualização:** 23/01/2025
