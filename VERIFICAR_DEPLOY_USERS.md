# 🔍 Verificar Deploy do Módulo Users

## ✅ Arquivos que Devem Estar no Servidor

### Módulos Admin
- ✅ `admin-modules/users.php`
- ✅ `admin-modules/user-detail.php`

### APIs
- ✅ `api/users/create.php`
- ✅ `api/users/update.php`
- ✅ `api/users/list.php`
- ✅ `api/users/permissions.php`

### Configuração
- ✅ `config/permissions.php`

### Banco de Dados
- ✅ `database/migration-add-permissions.sql`
- ✅ `database/schema-permissions.sql`

### Arquivo Principal
- ✅ `system.php` (atualizado com módulo users)

---

## 🔍 Como Verificar se o Deploy Funcionou

### 1. Verificar Arquivos no Servidor

**Via File Manager do Hostinger:**

1. Acesse File Manager
2. Navegue até `public_html/`
3. Verifique se os arquivos existem:
   - `admin-modules/users.php`
   - `admin-modules/user-detail.php`
   - `api/users/create.php`
   - `config/permissions.php`

### 2. Verificar Módulo no Menu

1. **Acesse:** `https://seudominio.com/system.php`
2. **Faça login**
3. **Verifique o menu lateral:**
   - Deve aparecer "👥 Users" no menu
   - Se não aparecer, o módulo não foi registrado

### 3. Acessar Módulo Diretamente

1. **Acesse:** `https://seudominio.com/system.php?module=users`
2. **Se aparecer erro 404 ou página em branco:**
   - Arquivo não existe no servidor
   - Ou arquivo não foi enviado no deploy

### 4. Verificar GitHub Actions

1. **Acesse:** https://github.com/nakazone/senior-floors-system/actions
2. **Verifique o último workflow:**
   - Deve ter executado com sucesso (verde)
   - Se falhou (vermelho), veja os logs

---

## 🚨 Se os Arquivos Não Estão no Servidor

### Opção 1: Fazer Upload Manual

1. **Baixe os arquivos do GitHub:**
   - https://github.com/nakazone/senior-floors-system/tree/main/admin-modules
   - https://github.com/nakazone/senior-floors-system/tree/main/api/users
   - https://github.com/nakazone/senior-floors-system/tree/main/config

2. **Faça upload via File Manager:**
   - `admin-modules/users.php` → `public_html/admin-modules/`
   - `admin-modules/user-detail.php` → `public_html/admin-modules/`
   - `api/users/*.php` → `public_html/api/users/`
   - `config/permissions.php` → `public_html/config/`

3. **Atualize system.php:**
   - Baixe `system.php` do GitHub
   - Faça upload substituindo o arquivo atual

### Opção 2: Verificar GitHub Actions

1. **Acesse:** https://github.com/nakazone/senior-floors-system/actions
2. **Veja o último workflow executado**
3. **Se falhou, veja os logs:**
   - Pode ser problema de SSH/FTP
   - Pode ser problema de secrets
   - Pode ser problema de caminho

### Opção 3: Forçar Novo Deploy

1. **Faça uma pequena alteração** em qualquer arquivo
2. **Commit e push:**
   ```bash
   git add .
   git commit -m "Trigger deploy"
   git push origin main
   ```
3. **Isso vai disparar o deploy novamente**

---

## 📋 Checklist de Verificação

- [ ] Arquivo `admin-modules/users.php` existe no servidor
- [ ] Arquivo `admin-modules/user-detail.php` existe no servidor
- [ ] Pasta `api/users/` existe com todos os arquivos
- [ ] Arquivo `config/permissions.php` existe
- [ ] Arquivo `system.php` foi atualizado
- [ ] Módulo "Users" aparece no menu lateral
- [ ] É possível acessar `system.php?module=users`
- [ ] Não há erros ao carregar a página

---

## 🐛 Problemas Comuns

### Módulo não aparece no menu

**Causa:** `system.php` não foi atualizado no servidor

**Solução:**
- Faça upload manual do `system.php` atualizado
- Ou verifique se o deploy incluiu o arquivo

### Erro 404 ao acessar módulo

**Causa:** Arquivo não existe no servidor

**Solução:**
- Faça upload manual dos arquivos faltantes
- Verifique caminhos no File Manager

### Erro de permissão

**Causa:** Tabelas de permissões não foram criadas

**Solução:**
- Execute `database/migration-add-permissions.sql`
- Veja: `EXECUTAR_SCHEMA_COMPLETO.md`

### Erro "Permission denied"

**Causa:** Usuário não tem permissão `users.view`

**Solução:**
- Execute a migration de permissões
- Ou faça login como admin

---

## 🚀 Solução Rápida

Se os arquivos não estão no servidor, faça upload manual:

1. **Baixe do GitHub:**
   - https://github.com/nakazone/senior-floors-system/archive/main.zip

2. **Extraia os arquivos necessários:**
   - `admin-modules/users.php`
   - `admin-modules/user-detail.php`
   - `api/users/*.php`
   - `config/permissions.php`
   - `system.php` (atualizado)

3. **Faça upload via File Manager ou FTP**

4. **Execute a migration:**
   - `database/migration-add-permissions.sql`

---

**Última atualização:** Janeiro 2025
