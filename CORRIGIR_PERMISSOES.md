# 🔧 Corrigir Permissões do Arquivo CSV

## Problema
O servidor não tem permissão para escrever no arquivo `leads.csv`.

## Solução Rápida

### Opção 1: Via File Manager (Mais Fácil)

1. **Acesse File Manager no painel Hostinger**

2. **Navegue até `public_html/`**

3. **Corrigir permissões do diretório:**
   - Clique com botão direito em `public_html/`
   - Selecione "Change Permissions" ou "Alterar Permissões"
   - Defina como **755** (ou 775)
   - Clique em "OK" ou "Salvar"

4. **Criar/Corrigir arquivo `leads.csv`:**
   - Se o arquivo `leads.csv` **não existe**:
     - Clique em "New File" ou "Novo Arquivo"
     - Nome: `leads.csv`
     - Conteúdo inicial:
       ```
       Date,Form,Name,Phone,Email,ZipCode,Message
       ```
     - Salve o arquivo
   
   - Se o arquivo `leads.csv` **já existe**:
     - Clique com botão direito em `leads.csv`
     - Selecione "Change Permissions" ou "Alterar Permissões"
     - Defina como **666** (ou 644)
     - Clique em "OK" ou "Salvar"

### Opção 2: Via SSH (Se Tiver Acesso)

```bash
# Conectar via SSH
ssh usuario@servidor

# Navegar até public_html
cd public_html

# Corrigir permissões do diretório
chmod 755 .

# Criar arquivo se não existir
touch leads.csv

# Corrigir permissões do arquivo
chmod 666 leads.csv

# Verificar permissões
ls -la leads.csv
```

### Opção 3: Testar Permissões

1. **Acesse o script de teste:**
   ```
   https://seudominio.com/test-permissions.php
   ```

2. **O script vai:**
   - Verificar se o diretório tem permissão de escrita
   - Tentar criar/escrever no arquivo
   - Mostrar exatamente qual é o problema

3. **Siga as instruções** que aparecerem no script

## Verificar se Funcionou

1. **Teste o formulário:**
   - Preencha e envie
   - Verifique se aparece no sistema

2. **Verifique o arquivo:**
   - Abra `public_html/leads.csv` no File Manager
   - Veja se tem o novo lead

3. **Execute o script de diagnóstico:**
   ```
   https://seudominio.com/debug-save-path.php
   ```
   - Deve mostrar: ✅ Arquivo existe e tem permissão de escrita

## Permissões Explicadas

- **755** (diretório): 
  - Proprietário: leitura, escrita, execução (7)
  - Grupo: leitura, execução (5)
  - Outros: leitura, execução (5)

- **666** (arquivo):
  - Proprietário: leitura, escrita (6)
  - Grupo: leitura, escrita (6)
  - Outros: leitura, escrita (6)

- **644** (arquivo - mais seguro):
  - Proprietário: leitura, escrita (6)
  - Grupo: leitura (4)
  - Outros: leitura (4)

## Se Ainda Não Funcionar

1. **Verifique o usuário do PHP:**
   - Execute `test-permissions.php`
   - Veja qual usuário está rodando o PHP

2. **Contate o suporte Hostinger:**
   - Explique que precisa de permissão de escrita em `public_html/leads.csv`
   - Mencione que é para um script PHP de formulário

3. **Alternativa: Usar banco de dados:**
   - Configure o banco de dados (ver `CONFIGURAR_BANCO_AGORA.md`)
   - O sistema vai usar o banco em vez do CSV

---

**Última atualização:** 23/01/2025
