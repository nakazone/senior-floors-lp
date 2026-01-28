# 🔧 Corrigir: Leads Não Aparecem no Sistema

## 🔍 Diagnóstico

### Passo 1: Verificar se Leads Estão no Banco

1. **Acesse:** `https://seudominio.com/verificar-leads-banco.php`
2. **Veja o resultado:**
   - ✅ Se aparecer leads = Estão sendo salvos
   - ❌ Se não aparecer = Problema no salvamento

### Passo 2: Verificar Logs

1. **Acesse File Manager**
2. **Procure por:** `lead-db-save.log`
3. **Veja as últimas entradas:**
   - ✅ "Lead saved to database" = Funcionando
   - ❌ "Database error" = Problema identificado

---

## 🐛 Problemas Comuns e Soluções

### Problema 1: Tabela `leads` não existe

**Sintoma:** Log mostra "Table 'leads' does not exist"

**Solução:**
1. Execute o schema SQL: `database/schema-v3-completo.sql`
2. Ou execute: `database/schema-v2-completo.sql`
3. Verifique se a tabela foi criada

### Problema 2: Banco não configurado

**Sintoma:** Log mostra "Database not configured"

**Solução:**
1. Configure `config/database.php`
2. Verifique credenciais do banco
3. Teste a conexão

### Problema 3: Caminho do database.php incorreto

**Sintoma:** Log mostra "Database config file not found"

**Solução:**
- O arquivo deve estar em: `public_html/config/database.php`
- Verifique se existe e tem permissões corretas

### Problema 4: Leads salvos apenas no CSV

**Sintoma:** Leads aparecem no CSV mas não no banco

**Solução:**
1. Verifique se a tabela `leads` existe
2. Verifique se o banco está configurado
3. Veja o log `lead-db-save.log` para erros

### Problema 5: CRM lendo apenas CSV

**Sintoma:** CRM mostra "Fonte de dados: CSV File"

**Solução:**
1. Execute o schema SQL para criar a tabela
2. Faça um teste enviando um formulário
3. Verifique se aparece no banco

---

## ✅ Solução Passo a Passo

### 1. Verificar Tabela Existe

```sql
-- Execute no phpMyAdmin
SHOW TABLES LIKE 'leads';
```

Se não existir, execute:
```sql
-- Execute: database/schema-v3-completo.sql
```

### 2. Verificar Banco Configurado

Acesse: `https://seudominio.com/verificar-leads-banco.php`

Deve mostrar:
- ✅ Banco de dados configurado
- ✅ Tabela leads existe

### 3. Testar Salvamento

1. **Preencha um formulário** na landing page
2. **Envie o formulário**
3. **Acesse:** `verificar-leads-banco.php`
4. **Verifique se o lead apareceu**

### 4. Verificar Logs

1. **Acesse File Manager**
2. **Procure:** `lead-db-save.log`
3. **Veja as últimas linhas:**
   - Deve mostrar "✅ Lead saved to database"

---

## 🔧 Correções Aplicadas

### 1. Caminho do database.php corrigido
- Agora tenta múltiplos caminhos
- Verifica se arquivo existe antes de usar

### 2. Verificação de tabela
- Verifica se tabela `leads` existe antes de inserir
- Loga erro se tabela não existir

### 3. Logging melhorado
- Cria arquivo `lead-db-save.log` com detalhes
- Mostra sucesso ou erro claramente

### 4. Script de verificação
- `verificar-leads-banco.php` para diagnosticar
- Mostra status completo do sistema

---

## 📋 Checklist

- [ ] Tabela `leads` existe no banco
- [ ] Banco de dados está configurado (`config/database.php`)
- [ ] Teste enviar formulário
- [ ] Verificar `verificar-leads-banco.php`
- [ ] Verificar log `lead-db-save.log`
- [ ] Leads aparecem no CRM (`system.php?module=crm`)
- [ ] CRM mostra "MySQL Database" como fonte

---

## 🚀 Próximos Passos

1. **Execute a verificação:** `verificar-leads-banco.php`
2. **Veja o resultado** e identifique o problema
3. **Siga as soluções** acima conforme necessário
4. **Teste novamente** enviando um formulário

---

**Última atualização:** Janeiro 2025
