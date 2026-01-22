# 🎯 Resumo Rápido: Configurar Banco de Dados

## ⚡ Versão Ultra Rápida (5 minutos)

### 1️⃣ Criar Banco no Hostinger
- cPanel → **MySQL Databases**
- Criar banco: `senior_floors_db`
- **Anotar nome completo** (ex: `u123456789_senior_floors_db`)

### 2️⃣ Criar Usuário
- Mesma página → Criar usuário
- Nome: `senior_floors_user`
- Senha forte
- **Anotar nome completo e senha**

### 3️⃣ Conectar
- Adicionar usuário ao banco
- Marcar **ALL PRIVILEGES**

### 4️⃣ Executar SQL
- cPanel → **phpMyAdmin**
- Selecionar banco → Aba **SQL**
- Copiar TODO o conteúdo de `database/schema.sql`
- Colar e **Executar**

### 5️⃣ Configurar Arquivo
- File Manager → `public_html/config/database.php`
- Editar com suas credenciais:
  ```php
  define('DB_NAME', 'SEU_USUARIO_senior_floors_db');
  define('DB_USER', 'SEU_USUARIO_senior_floors_user');
  define('DB_PASS', 'SUA_SENHA');
  ```

### 6️⃣ Testar
- Acessar: `https://seudominio.com/test-db.php`
- Deve mostrar ✅ em todos os testes

---

## 📚 Guias Completos

- **`CONFIGURAR_DATABASE_PASSO_A_PASSO.md`** - Guia detalhado completo
- **`CHECKLIST_DATABASE.md`** - Checklist para seguir

---

## 🔑 Informações Importantes

### ⚠️ Nome Completo
No Hostinger, o nome completo sempre tem um prefixo:
- ❌ `senior_floors_db` (errado)
- ✅ `u123456789_senior_floors_db` (correto)

### ⚠️ Onde Encontrar
- **Nome completo:** cPanel → MySQL Databases → Lista de bancos
- **Credenciais:** cPanel → MySQL Databases → Botão "Show" na senha

---

## ✅ Depois de Configurar

1. Teste o formulário na landing page
2. Verifique no phpMyAdmin → tabela `leads`
3. Deve aparecer o lead! 🎉

---

**Dúvidas?** Veja `CONFIGURAR_DATABASE_PASSO_A_PASSO.md` para detalhes completos!
