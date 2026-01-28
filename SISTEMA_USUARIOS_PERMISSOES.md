# 👥 Sistema de Usuários e Permissões - Senior Floors CRM

## 🎯 Funcionalidades Implementadas

✅ **Gerenciamento Completo de Usuários**
- Criar, editar e excluir usuários
- Definir roles (Admin, Sales Rep, Project Manager, Support)
- Ativar/desativar usuários
- Gerenciar senhas

✅ **Sistema de Permissões Individualizadas**
- 30+ permissões granulares disponíveis
- Cada usuário pode ter permissões específicas
- Permissões agrupadas por módulo
- Admin tem todas as permissões automaticamente

✅ **Interface Administrativa**
- Listagem completa de usuários
- Visualização detalhada de cada usuário
- Gerenciamento visual de permissões
- Checkboxes para ativar/desativar permissões individualmente

---

## 📋 Permissões Disponíveis

### Dashboard
- `dashboard.view` - Visualizar dashboard

### Leads
- `leads.view` - Visualizar leads
- `leads.create` - Criar leads
- `leads.edit` - Editar leads
- `leads.delete` - Excluir leads
- `leads.assign` - Atribuir leads

### Customers
- `customers.view` - Visualizar customers
- `customers.create` - Criar customers
- `customers.edit` - Editar customers
- `customers.delete` - Excluir customers

### Projects
- `projects.view` - Visualizar projects
- `projects.create` - Criar projects
- `projects.edit` - Editar projects
- `projects.delete` - Excluir projects
- `projects.update_status` - Atualizar status

### Coupons
- `coupons.view` - Visualizar coupons
- `coupons.create` - Criar coupons
- `coupons.edit` - Editar coupons
- `coupons.delete` - Excluir coupons

### Users & Permissions
- `users.view` - Visualizar usuários
- `users.create` - Criar usuários
- `users.edit` - Editar usuários
- `users.delete` - Excluir usuários
- `users.manage_permissions` - Gerenciar permissões

### Settings
- `settings.view` - Visualizar configurações
- `settings.edit` - Editar configurações

### Activities
- `activities.view` - Visualizar atividades
- `activities.create` - Criar atividades

### Reports
- `reports.view` - Visualizar relatórios
- `reports.export` - Exportar relatórios

---

## 🚀 Como Usar

### 1. Executar Migration do Banco de Dados

Primeiro, execute o script de migration para criar as tabelas de permissões:

```sql
-- Execute: database/migration-add-permissions.sql
```

Via phpMyAdmin:
1. Acesse phpMyAdmin
2. Selecione seu banco de dados
3. Clique na aba "SQL"
4. Cole o conteúdo de `database/migration-add-permissions.sql`
5. Clique em "Go"

### 2. Acessar Módulo de Usuários

1. **Faça login** no sistema admin
2. **Acesse:** `system.php?module=users`
3. Você verá a lista de todos os usuários

### 3. Criar Novo Usuário

1. **Clique em "+ New User"**
2. **Preencha os dados:**
   - Nome
   - Email (será usado para login)
   - Phone (opcional)
   - Role (Sales Rep, Project Manager, Support, Admin)
   - Password (mínimo 6 caracteres)
   - Status (Active/Inactive)
3. **Clique em "Create User"**

### 4. Gerenciar Permissões de um Usuário

1. **Clique em "Manage"** ao lado do usuário
2. **Role para baixo** até "Individual Permissions"
3. **Marque/desmarque** as permissões desejadas
4. **As permissões são salvas automaticamente** quando você marca/desmarca
5. **Ou clique em "Save All Permissions"** para salvar todas de uma vez

### 5. Editar Usuário

1. **Clique em "Manage"** ao lado do usuário
2. **Edite os campos** desejados:
   - Nome, Email, Phone, Role, Status
   - Password (deixe em branco para manter a atual)
3. **Clique em "Save Changes"**

---

## 🔐 Roles e Permissões

### Admin
- ✅ Tem **todas as permissões automaticamente**
- Não precisa configurar permissões individuais
- Pode gerenciar todos os usuários e permissões

### Sales Rep
- Permissões típicas:
  - Ver e criar leads
  - Ver e criar customers
  - Ver projects
  - Criar activities

### Project Manager
- Permissões típicas:
  - Ver todos os leads e customers
  - Criar e gerenciar projects
  - Atualizar status de projects
  - Criar activities

### Support
- Permissões típicas:
  - Ver leads, customers, projects
  - Criar activities
  - Adicionar notas

---

## 📊 Exemplos de Configuração

### Exemplo 1: Sales Rep com Acesso Limitado

**Permissões:**
- ✅ `leads.view`
- ✅ `leads.create`
- ✅ `leads.edit`
- ✅ `customers.view`
- ✅ `customers.create`
- ✅ `projects.view`
- ✅ `activities.create`

**Resultado:** Pode ver e criar leads, ver customers, ver projects, mas não pode excluir nada nem gerenciar usuários.

### Exemplo 2: Project Manager Completo

**Permissões:**
- ✅ Todas de `leads.*`
- ✅ Todas de `customers.*`
- ✅ Todas de `projects.*`
- ✅ Todas de `activities.*`

**Resultado:** Pode gerenciar completamente leads, customers e projects, mas não pode gerenciar usuários ou configurações.

### Exemplo 3: Usuário Somente Leitura

**Permissões:**
- ✅ `dashboard.view`
- ✅ `leads.view`
- ✅ `customers.view`
- ✅ `projects.view`
- ✅ `coupons.view`
- ✅ `activities.view`

**Resultado:** Pode ver tudo, mas não pode criar, editar ou excluir nada.

---

## 🔧 APIs Disponíveis

### Criar Usuário
```
POST /api/users/create.php
Body: name, email, phone, role, password, is_active
```

### Atualizar Usuário
```
POST /api/users/update.php
Body: user_id, name, email, phone, role, is_active, password (opcional)
```

### Listar Usuários
```
GET /api/users/list.php?is_active=1&role=sales_rep
```

### Gerenciar Permissões
```
POST /api/users/permissions.php
Body: user_id, action (grant/revoke/set_all), permission_key, permissions (array)
```

---

## ⚠️ Importante

### Segurança

1. **Senha Padrão do Admin:**
   - Email: `admin@senior-floors.com`
   - Senha: `admin123`
   - **⚠️ ALTERE IMEDIATAMENTE após primeiro login!**

2. **Permissões Sensíveis:**
   - `users.manage_permissions` - Permite gerenciar permissões de outros usuários
   - `users.delete` - Permite excluir usuários
   - `settings.edit` - Permite editar configurações do sistema

3. **Admin tem todas as permissões:**
   - Usuários com role "admin" têm todas as permissões automaticamente
   - Não é necessário configurar permissões individuais para admins

### Boas Práticas

1. **Princípio do Menor Privilégio:**
   - Dê apenas as permissões necessárias para cada usuário
   - Não dê permissões desnecessárias

2. **Revisão Periódica:**
   - Revise permissões de usuários periodicamente
   - Remova permissões não utilizadas

3. **Usuários Inativos:**
   - Desative usuários que não estão mais ativos
   - Não exclua, apenas desative (preserva histórico)

---

## 📋 Checklist de Implementação

- [ ] Executar migration `database/migration-add-permissions.sql`
- [ ] Verificar se tabelas `permissions` e `user_permissions` foram criadas
- [ ] Verificar se permissões padrão foram inseridas
- [ ] Fazer login com usuário admin padrão
- [ ] Alterar senha do admin padrão
- [ ] Criar primeiro usuário de teste
- [ ] Configurar permissões do usuário de teste
- [ ] Testar login com o novo usuário
- [ ] Verificar se permissões estão funcionando corretamente

---

## 🐛 Troubleshooting

### Erro: "Permission denied"

**Causa:** Usuário não tem a permissão necessária

**Solução:**
- Verifique se o usuário tem a permissão específica
- Verifique se o usuário está ativo
- Verifique se está logado corretamente

### Erro: "Table 'permissions' doesn't exist"

**Causa:** Migration não foi executada

**Solução:**
- Execute `database/migration-add-permissions.sql`
- Verifique se as tabelas foram criadas

### Permissões não aparecem

**Causa:** Permissões padrão não foram inseridas

**Solução:**
- Execute novamente a parte de INSERT do migration
- Verifique se há permissões na tabela `permissions`

### Admin não tem todas as permissões

**Causa:** Verificação de role não está funcionando

**Solução:**
- Verifique se o usuário tem `role = 'admin'` no banco
- Verifique se a função `hasPermission()` está verificando role corretamente

---

## 📚 Arquivos Criados

### Banco de Dados
- `database/migration-add-permissions.sql` - Script de migration
- `database/schema-permissions.sql` - Schema completo de permissões

### Configuração
- `config/permissions.php` - Sistema de verificação de permissões

### APIs
- `api/users/create.php` - Criar usuário
- `api/users/update.php` - Atualizar usuário
- `api/users/list.php` - Listar usuários
- `api/users/permissions.php` - Gerenciar permissões

### Interfaces Admin
- `admin-modules/users.php` - Listagem de usuários
- `admin-modules/user-detail.php` - Gerenciamento de usuário e permissões

---

**Última atualização:** Janeiro 2025
