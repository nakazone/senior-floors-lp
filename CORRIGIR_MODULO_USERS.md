# 🔧 Corrigir Módulo Users - Guia Rápido

## ⚠️ Problemas Comuns

### 1. Módulo não aparece no menu lateral

**Causa:** Arquivo `system.php` não foi atualizado no servidor

**Solução:**
1. Baixe `system.php` do GitHub: https://github.com/nakazone/senior-floors-system/blob/main/system.php
2. Faça upload substituindo o arquivo no servidor
3. Ou aguarde o deploy automático

### 2. Erro ao acessar módulo

**Causa:** Arquivos não estão no servidor

**Solução:**
Faça upload manual dos arquivos:
- `admin-modules/users.php`
- `admin-modules/user-detail.php`
- `api/users/create.php`
- `api/users/update.php`
- `api/users/permissions.php`
- `config/permissions.php`

### 3. Não consigo criar usuários

**Causa:** Tabela `users` não existe ou API não funciona

**Solução:**
1. Execute o schema completo: `database/schema-v3-completo.sql`
2. Ou execute apenas a migration: `database/migration-add-permissions.sql`
3. Verifique se a tabela `users` existe no banco

### 4. Erro de permissão

**Causa:** Sistema de permissões não está configurado

**Solução:**
- O módulo agora funciona **mesmo sem permissões configuradas**
- Se você é admin, pode usar normalmente
- Se quiser usar permissões, execute: `database/migration-add-permissions.sql`

---

## ✅ Verificação Rápida

### Passo 1: Verificar Arquivos

Acesse via File Manager e verifique se existem:
- ✅ `public_html/admin-modules/users.php`
- ✅ `public_html/admin-modules/user-detail.php`
- ✅ `public_html/api/users/create.php`
- ✅ `public_html/config/permissions.php`

### Passo 2: Verificar Menu

1. Acesse: `https://seudominio.com/system.php`
2. Faça login
3. Verifique se aparece "👥 Users" no menu lateral

### Passo 3: Testar Acesso

1. Acesse: `https://seudominio.com/system.php?module=users`
2. Deve carregar a página de usuários
3. Se aparecer erro, veja o console do navegador (F12)

---

## 🚀 Solução Rápida - Upload Manual

Se o deploy não funcionou, faça upload manual:

### 1. Baixar Arquivos do GitHub

Acesse e baixe cada arquivo:
- https://github.com/nakazone/senior-floors-system/tree/main/admin-modules
- https://github.com/nakazone/senior-floors-system/tree/main/api/users
- https://github.com/nakazone/senior-floors-system/tree/main/config

### 2. Fazer Upload

Via File Manager:
- `admin-modules/users.php` → `public_html/admin-modules/`
- `admin-modules/user-detail.php` → `public_html/admin-modules/`
- `api/users/create.php` → `public_html/api/users/`
- `api/users/update.php` → `public_html/api/users/`
- `api/users/permissions.php` → `public_html/api/users/`
- `config/permissions.php` → `public_html/config/`
- `system.php` → `public_html/` (substituir)

### 3. Executar Migration (Opcional)

Se quiser usar permissões:
- Execute: `database/migration-add-permissions.sql`

---

## 🧪 Testar Funcionalidade

### Criar Usuário:

1. Acesse: `system.php?module=users`
2. Clique em "+ New User"
3. Preencha:
   - Nome
   - Email
   - Senha (mínimo 6 caracteres)
   - Role
4. Clique em "Create User"

### Gerenciar Permissões:

1. Clique em "Manage" ao lado do usuário
2. Role até "Individual Permissions"
3. Marque/desmarque permissões
4. Permissões são salvas automaticamente

---

## 📋 Checklist Final

- [ ] Arquivos estão no servidor
- [ ] `system.php` foi atualizado
- [ ] Módulo aparece no menu
- [ ] É possível acessar `system.php?module=users`
- [ ] Botão "+ New User" aparece
- [ ] É possível criar usuário
- [ ] É possível editar usuário
- [ ] É possível gerenciar permissões (se migration executada)

---

**Última atualização:** Janeiro 2025
