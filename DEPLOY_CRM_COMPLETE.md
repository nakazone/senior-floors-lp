# Deploy do CRM Completo - Senior Floors

## 📋 Checklist de Deploy

### 1. Banco de Dados

#### 1.1. Executar Migration
```sql
-- Execute o arquivo: database/migration-v2-to-v3.sql
-- Isso adiciona o campo post_service_status na tabela projects
```

#### 1.2. Verificar Schema Completo (Opcional)
```sql
-- Se estiver criando do zero, execute: database/schema-v3-completo.sql
-- Se já tiver o schema v2, execute apenas a migration acima
```

### 2. Upload de Arquivos

#### 2.1. Novos Arquivos de API
```
api/
├── customers/
│   ├── create.php
│   ├── get.php
│   ├── list.php
│   ├── update.php
│   ├── notes.php
│   └── tags.php
├── projects/
│   ├── create.php
│   ├── get.php
│   ├── list.php
│   ├── update.php
│   ├── notes.php
│   └── tags.php
├── activities/
│   ├── create.php
│   └── list.php
├── coupons/
│   ├── create.php
│   ├── list.php
│   ├── use.php
│   └── update.php
├── assignment/
│   ├── assign.php
│   └── history.php
└── users/
    └── list.php
```

#### 2.2. Novos Módulos Admin
```
admin-modules/
├── customers.php
├── customer-detail.php
├── projects.php
├── project-detail.php
└── coupons.php
```

#### 2.3. Arquivos de Configuração
```
config/
└── tags.php
```

#### 2.4. Arquivos Atualizados
```
system.php (adicionados novos módulos no menu)
database/
├── schema-v3-completo.sql (novo schema completo)
└── migration-v2-to-v3.sql (migration script)
```

### 3. Verificações Pós-Deploy

#### 3.1. Testar Acesso aos Módulos
- [ ] Dashboard: `system.php?module=dashboard`
- [ ] CRM - Leads: `system.php?module=crm`
- [ ] Customers: `system.php?module=customers`
- [ ] Projects: `system.php?module=projects`
- [ ] Coupons: `system.php?module=coupons`

#### 3.2. Testar Funcionalidades

**Customers:**
- [ ] Criar novo customer
- [ ] Visualizar detalhes do customer
- [ ] Adicionar nota ao customer
- [ ] Adicionar tag ao customer
- [ ] Atribuir owner ao customer
- [ ] Filtrar customers por status/tipo/owner

**Projects:**
- [ ] Criar novo project
- [ ] Visualizar detalhes do project
- [ ] Atualizar status do project
- [ ] Atualizar post-service status
- [ ] Adicionar nota ao project
- [ ] Adicionar tag ao project
- [ ] Filtrar projects por status/tipo/post-service

**Coupons:**
- [ ] Criar novo coupon
- [ ] Ativar/desativar coupon
- [ ] Visualizar lista de coupons
- [ ] Verificar uso de coupons

**Activities:**
- [ ] Verificar timeline de atividades em leads/customers/projects
- [ ] Verificar logs automáticos de mudanças

**Assignment:**
- [ ] Atribuir lead a usuário
- [ ] Atribuir customer a usuário
- [ ] Atribuir project a usuário
- [ ] Ver histórico de atribuições

### 4. Configurações do Banco de Dados

Certifique-se de que o arquivo `config/database.php` está configurado corretamente:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'seu_database');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

### 5. Permissões de Arquivos

Certifique-se de que os diretórios têm permissões corretas:
```bash
chmod 755 api/
chmod 755 admin-modules/
chmod 755 config/
```

### 6. Testes de Integração

#### 6.1. Fluxo Completo
1. Lead chega pelo formulário → salvo em `leads` table
2. Converter lead para customer → usar API `api/customers/create.php`
3. Criar project para customer → usar API `api/projects/create.php`
4. Atualizar status do project → usar interface admin
5. Atualizar post-service status → usar interface admin
6. Adicionar atividades → automático via sistema

### 7. Documentação de API

#### Endpoints Disponíveis

**Customers:**
- `POST /api/customers/create.php` - Criar customer
- `GET /api/customers/get.php?id=X` - Buscar customer
- `GET /api/customers/list.php` - Listar customers
- `POST /api/customers/update.php` - Atualizar customer
- `POST /api/customers/notes.php` - Adicionar nota
- `POST /api/customers/tags.php` - Gerenciar tags

**Projects:**
- `POST /api/projects/create.php` - Criar project
- `GET /api/projects/get.php?id=X` - Buscar project
- `GET /api/projects/list.php` - Listar projects
- `POST /api/projects/update.php` - Atualizar project
- `POST /api/projects/notes.php` - Adicionar nota
- `POST /api/projects/tags.php` - Gerenciar tags

**Activities:**
- `POST /api/activities/create.php` - Criar activity
- `GET /api/activities/list.php` - Listar activities

**Coupons:**
- `POST /api/coupons/create.php` - Criar coupon
- `GET /api/coupons/list.php` - Listar coupons
- `POST /api/coupons/use.php` - Usar coupon
- `POST /api/coupons/update.php` - Atualizar coupon

**Assignment:**
- `POST /api/assignment/assign.php` - Atribuir lead/customer/project
- `GET /api/assignment/history.php` - Histórico de atribuições

**Users:**
- `GET /api/users/list.php` - Listar usuários

### 8. Troubleshooting

#### Problema: Módulos não aparecem no menu
**Solução:** Verifique se `system.php` foi atualizado com os novos módulos

#### Problema: Erro 500 ao acessar módulos
**Solução:** 
- Verifique logs do PHP
- Verifique se `config/database.php` está configurado
- Verifique se as tabelas do banco existem

#### Problema: Campo post_service_status não existe
**Solução:** Execute `database/migration-v2-to-v3.sql`

#### Problema: API retorna erro de método não permitido
**Solução:** Verifique se está usando POST para endpoints de criação/atualização

### 9. Próximos Passos (Opcional)

- [ ] Implementar autenticação de usuários
- [ ] Adicionar permissões por role
- [ ] Implementar notificações por email
- [ ] Adicionar relatórios avançados
- [ ] Implementar exportação de dados (CSV/Excel)
- [ ] Adicionar busca avançada
- [ ] Implementar dashboard com gráficos

### 10. Suporte

Em caso de problemas:
1. Verifique os logs do PHP (`error_log`)
2. Verifique os logs do banco de dados
3. Teste os endpoints individualmente
4. Verifique a configuração do banco de dados

---

**Data do Deploy:** _______________
**Versão:** 3.0
**Status:** ✅ Completo
