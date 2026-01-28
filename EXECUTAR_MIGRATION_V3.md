# 🗄️ Executar Migration v2→v3 - Passo a Passo

## 📋 O Que Esta Migration Faz

Esta migration adiciona o campo `post_service_status` na tabela `projects` para suportar o módulo de Pós-Atendimento.

**Campo adicionado:**
- `post_service_status` - Status de pós-atendimento com valores:
  - `installation_scheduled` - Instalação Agendada
  - `installation_completed` - Instalação Concluída
  - `follow_up_sent` - Follow-up Enviado
  - `review_requested` - Avaliação Solicitada
  - `warranty_active` - Garantia Ativa

---

## ✅ PASSO 1: Acessar phpMyAdmin

1. **Acesse seu painel Hostinger:**
   - https://hpanel.hostinger.com
   - Faça login

2. **Procure por "phpMyAdmin"** no menu:
   - Geralmente em "Advanced" ou "Databases"
   - Clique para abrir

---

## ✅ PASSO 2: Selecionar Banco de Dados

1. **No menu lateral esquerdo**, encontre seu banco de dados
   - Exemplo: `u123456789_senior_floors_db`
   - Clique no nome do banco para selecioná-lo

2. **Verifique se a tabela `projects` existe:**
   - Você deve ver a tabela na lista
   - Se não existir, você precisa executar o schema completo primeiro

---

## ✅ PASSO 3: Executar Migration SQL

### Opção A: Via Aba SQL (Recomendado)

1. **Clique na aba "SQL"** no topo do phpMyAdmin

2. **Cole o seguinte código SQL:**

```sql
-- ============================================
-- Migration: v2.0 → v3.0
-- Adiciona campo post_service_status na tabela projects
-- ============================================

-- Adicionar campo post_service_status na tabela projects
ALTER TABLE `projects` 
ADD COLUMN `post_service_status` ENUM(
    'installation_scheduled',
    'installation_completed', 
    'follow_up_sent',
    'review_requested',
    'warranty_active'
) DEFAULT NULL COMMENT 'Status de pós-atendimento' AFTER `status`;

-- Adicionar índice para o novo campo
CREATE INDEX `idx_post_service_status` ON `projects`(`post_service_status`);
```

3. **Clique em "Go"** ou **"Executar"** (botão no canto inferior direito)

4. **✅ Verifique o resultado:**
   - Deve aparecer: "2 queries executed successfully"
   - Ou mensagem de sucesso similar

### Opção B: Se o Campo Já Existe (Verificação)

Se você receber um erro dizendo que o campo já existe, isso significa que a migration já foi executada. Você pode verificar:

1. **Clique na tabela `projects`** no menu lateral
2. **Clique na aba "Structure"** (Estrutura)
3. **Procure por `post_service_status`** na lista de colunas
4. Se existir, a migration já foi executada! ✅

---

## ✅ PASSO 4: Verificar se Funcionou

### Verificação Rápida:

1. **No phpMyAdmin, clique na tabela `projects`**
2. **Clique na aba "Structure"**
3. **Procure por `post_service_status`** na lista de colunas
4. **Deve aparecer:**
   - Nome: `post_service_status`
   - Tipo: `enum(...)`
   - Null: `Yes`
   - Padrão: `NULL`

### Verificação no Sistema:

1. **Acesse:** `https://seudominio.com/system.php?module=projects`
2. **Crie ou edite um project**
3. **Verifique se aparece o campo "Post-Service Status"** no formulário
4. **Deve ter as opções:**
   - Installation Scheduled
   - Installation Completed
   - Follow-up Sent
   - Review Requested
   - Warranty Active

---

## ⚠️ Problemas Comuns

### Erro: "Table 'projects' doesn't exist"

**Causa:** A tabela `projects` ainda não foi criada.

**Solução:**
1. Execute primeiro o schema completo: `database/schema-v3-completo.sql`
2. Ou execute o schema v2: `database/schema-v2-completo.sql`
3. Depois execute esta migration

### Erro: "Duplicate column name 'post_service_status'"

**Causa:** O campo já existe (migration já foi executada).

**Solução:**
- Não precisa fazer nada! A migration já foi aplicada.
- Pule para o Passo 4 para verificar

### Erro: "Access denied"

**Causa:** Usuário MySQL não tem permissões suficientes.

**Solução:**
1. No painel Hostinger → MySQL Databases
2. Verifique se o usuário tem **ALL PRIVILEGES** no banco
3. Se não tiver, adicione as permissões

---

## 📋 Checklist

- [ ] phpMyAdmin acessado
- [ ] Banco de dados selecionado
- [ ] Tabela `projects` existe
- [ ] Código SQL copiado e colado
- [ ] Migration executada com sucesso
- [ ] Campo `post_service_status` verificado na estrutura
- [ ] Campo aparece no sistema admin

---

## 🎯 Próximos Passos

Após executar a migration:

1. ✅ Teste criar um novo project
2. ✅ Teste atualizar o post-service status de um project existente
3. ✅ Verifique se o filtro por post-service status funciona
4. ✅ Teste todas as funcionalidades do módulo Projects

---

## 📝 Notas Importantes

- ⚠️ **Backup:** Se você tem dados importantes, faça backup antes de executar migrations
- ✅ **Seguro:** Esta migration apenas adiciona um campo novo, não modifica dados existentes
- 🔄 **Reversível:** Se precisar reverter, você pode remover o campo manualmente (mas não é necessário)

---

**Última atualização:** Janeiro 2025
