# 🗄️ Executar Schema Completo do Banco de Dados

## ⚠️ Situação Atual

Você está tentando executar a migration v2→v3, mas a tabela `projects` não existe ainda.

**Isso significa que você precisa executar o schema completo primeiro!**

---

## 📋 O Que Fazer

### Opção 1: Executar Schema Completo via phpMyAdmin (Recomendado)

#### Passo 1: Acessar phpMyAdmin

1. **Acesse o painel Hostinger:**
   - https://hpanel.hostinger.com
   - Faça login

2. **Procure por "phpMyAdmin"** no menu
   - Geralmente em "Advanced" ou "Databases"

3. **Clique para abrir phpMyAdmin**

#### Passo 2: Selecionar Banco de Dados

1. **No menu lateral esquerdo**, encontre seu banco de dados
   - Exemplo: `u123456789_senior_floors_db`
   - Clique no nome do banco para selecioná-lo

#### Passo 3: Executar Schema SQL

1. **Clique na aba "SQL"** no topo do phpMyAdmin

2. **Abra o arquivo:** `database/schema-v3-completo.sql`
   - Localização: `/Users/naka/senior-floors-landing/database/schema-v3-completo.sql`
   - Ou baixe do GitHub: https://github.com/nakazone/senior-floors-system/blob/main/database/schema-v3-completo.sql

3. **Copie TODO o conteúdo** do arquivo SQL

4. **Cole no phpMyAdmin** (na aba SQL)

5. **Clique em "Go"** ou **"Executar"**

6. **✅ Aguarde a execução** (pode levar alguns segundos)

7. **Verifique o resultado:**
   - Deve aparecer: "X queries executed successfully"
   - Ou mensagens de sucesso para cada tabela criada

#### Passo 4: Verificar Tabelas Criadas

1. **No menu lateral esquerdo**, você deve ver as seguintes tabelas:
   - ✅ `leads`
   - ✅ `customers`
   - ✅ `projects` ← **Esta é a que estava faltando!**
   - ✅ `activities`
   - ✅ `assignment_history`
   - ✅ `coupons`
   - ✅ `coupon_usage`
   - ✅ `lead_tags`
   - ✅ `customer_tags`
   - ✅ `project_tags`
   - ✅ `lead_notes`
   - ✅ `customer_notes`
   - ✅ `project_notes`
   - ✅ `users`

2. **Se todas as tabelas aparecerem**, o schema foi executado com sucesso! ✅

---

### Opção 2: Executar via Import (Alternativa)

#### Passo 1: Baixar Arquivo SQL

1. **Baixe o arquivo:** `database/schema-v3-completo.sql`
   - Do GitHub: https://github.com/nakazone/senior-floors-system/raw/main/database/schema-v3-completo.sql
   - Ou copie do seu computador local

#### Passo 2: Importar no phpMyAdmin

1. **No phpMyAdmin**, selecione seu banco de dados

2. **Clique na aba "Import"** (no topo)

3. **Clique em "Choose File"**

4. **Selecione o arquivo** `schema-v3-completo.sql`

5. **Clique em "Go"** ou **"Import"**

6. **✅ Aguarde a importação** completar

---

## 🔍 Verificar se Funcionou

### Verificação Rápida:

1. **No phpMyAdmin**, clique na tabela `projects`
2. **Clique na aba "Structure"**
3. **Você deve ver todas as colunas**, incluindo:
   - `id`
   - `customer_id`
   - `name`
   - `status`
   - `post_service_status` ← Este campo já vem no schema v3!
   - E outros campos...

### Verificação no Sistema:

1. **Acesse:** `https://seudominio.com/system.php?module=projects`
2. **Deve carregar sem erros**
3. **Você pode criar um novo project**

---

## ⚠️ Se Você Já Tem Dados

### Se você já tem a tabela `leads` com dados:

**Não se preocupe!** O schema usa `CREATE TABLE IF NOT EXISTS`, então:
- ✅ Tabelas existentes não serão sobrescritas
- ✅ Dados existentes serão preservados
- ✅ Apenas tabelas novas serão criadas

### Se você tem schema v2:

O schema v3 é compatível e inclui tudo do v2 + novas tabelas.

---

## 📋 Checklist

- [ ] phpMyAdmin acessado
- [ ] Banco de dados selecionado
- [ ] Arquivo `schema-v3-completo.sql` aberto
- [ ] Conteúdo copiado e colado no phpMyAdmin
- [ ] SQL executado com sucesso
- [ ] Tabela `projects` verificada na lista
- [ ] Todas as 14 tabelas aparecem
- [ ] Sistema admin funciona sem erros

---

## 🎯 Depois de Executar o Schema

Após executar o schema completo:

1. ✅ **Agora você pode executar a migration** (se necessário)
   - Mas na verdade, o schema v3 já inclui o campo `post_service_status`!
   - Então você pode pular a migration

2. ✅ **Teste criar um customer:**
   - `system.php?module=customers`
   - Clique em "New Customer"

3. ✅ **Teste criar um project:**
   - `system.php?module=projects`
   - Clique em "New Project"

4. ✅ **Teste criar um coupon:**
   - `system.php?module=coupons`
   - Clique em "New Coupon"

---

## 🆘 Problemas Comuns

### Erro: "Table already exists"

**Causa:** Algumas tabelas já existem

**Solução:**
- Não é um problema! O `IF NOT EXISTS` evita erros
- Continue a execução

### Erro: "Access denied"

**Causa:** Usuário não tem permissões

**Solução:**
- No Hostinger → MySQL Databases
- Verifique se o usuário tem **ALL PRIVILEGES**
- Se não tiver, adicione as permissões

### Erro: "Unknown database"

**Causa:** Banco de dados não existe

**Solução:**
- Crie o banco de dados primeiro
- Veja: `CONFIGURAR_BANCO_AGORA.md`

### Erro: "Syntax error"

**Causa:** SQL mal formatado

**Solução:**
- Certifique-se de copiar TODO o conteúdo do arquivo
- Não copie apenas uma parte
- Verifique se não há caracteres estranhos

---

## 📝 Próximos Passos

Após executar o schema completo:

1. ✅ Verifique se todas as tabelas foram criadas
2. ✅ Teste os módulos no sistema admin
3. ✅ Configure usuários na tabela `users` (se necessário)
4. ✅ Comece a usar o CRM completo!

---

**Última atualização:** Janeiro 2025
