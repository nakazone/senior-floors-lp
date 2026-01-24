# 🔧 Solução: Informações Não Aparecem no Sistema

## Problema
- ✅ Formulário envia
- ✅ Email recebido
- ❌ Não aparece no sistema (CRM)

## Possíveis Causas

### 1. **Caminho do CSV Incorreto**
O `send-lead.php` pode estar salvando em um local diferente do que o CRM está lendo.

**Verificar:**
- `send-lead.php` salva em: `dirname(__DIR__) . '/leads.csv'`
- `CRM` lê de: `__DIR__ . '/../leads.csv'`

Se `send-lead.php` está em `public_html/lp/`:
- `dirname(__DIR__)` = `public_html/`
- Caminho: `public_html/leads.csv` ✅

Se `CRM` está em `public_html/admin-modules/`:
- `__DIR__ . '/../'` = `public_html/`
- Caminho: `public_html/leads.csv` ✅

**Deve estar correto, mas vamos verificar!**

### 2. **Banco de Dados Não Configurado**
Se o banco não está configurado:
- `send-lead.php` tenta salvar no banco → falha silenciosamente
- Depois salva no CSV → deve funcionar
- Mas se o CSV também falhar, nada é salvo

### 3. **Permissões de Arquivo**
O servidor pode não ter permissão para escrever no arquivo.

## Solução Imediata

### PASSO 1: Executar Script de Diagnóstico

1. **Acesse no navegador:**
   ```
   https://seudominio.com/debug-save-path.php
   ```

2. **O script vai mostrar:**
   - Onde `send-lead.php` está tentando salvar
   - Onde o CRM está tentando ler
   - Se os arquivos existem
   - Se há diferença de caminhos
   - Status do banco de dados

### PASSO 2: Verificar Manualmente

**Via File Manager do Hostinger:**

1. Acesse `public_html/`
2. Procure por `leads.csv`
3. Veja se o arquivo existe
4. Veja a data de modificação (deve ser recente)
5. Abra o arquivo e veja se tem os leads

**Se o arquivo não existe:**
- Problema de permissões
- Caminho incorreto

**Se o arquivo existe mas está vazio:**
- Problema ao escrever
- Verificar logs de erro

### PASSO 3: Verificar Logs

**Arquivos para verificar:**
- `public_html/form-submissions.log` - Log de todas as submissões
- `public_html/email-status.log` - Status dos emails
- Logs de erro do PHP (se disponível)

**Via File Manager:**
- Abra cada arquivo e veja as últimas linhas

### PASSO 4: Testar Salvamento Manual

Crie um arquivo de teste `test-save.php`:

```php
<?php
$log_dir = dirname(__DIR__);
$log_file = $log_dir . '/leads.csv';

$test_data = date('Y-m-d H:i:s') . ",test,Test User,555-1234,test@test.com,12345,Test message\n";

if (file_put_contents($log_file, $test_data, FILE_APPEND | LOCK_EX)) {
    echo "✅ Arquivo salvo com sucesso em: $log_file";
} else {
    echo "❌ Erro ao salvar arquivo em: $log_file";
    echo "<br>Diretório existe? " . (is_dir($log_dir) ? 'Sim' : 'Não');
    echo "<br>Diretório tem permissão de escrita? " . (is_writable($log_dir) ? 'Sim' : 'Não');
}
?>
```

Acesse: `https://seudominio.com/test-save.php`

## Correções Possíveis

### Correção 1: Garantir Mesmo Caminho

Se os caminhos estão diferentes, vamos forçar o mesmo caminho:

**No `send-lead.php`:**
```php
// Usar caminho absoluto baseado no DOCUMENT_ROOT
$log_dir = $_SERVER['DOCUMENT_ROOT'];
$log_file = $log_dir . '/leads.csv';
```

**No `crm.php`:**
```php
// Usar mesmo caminho
$CSV_FILE = $_SERVER['DOCUMENT_ROOT'] . '/leads.csv';
```

### Correção 2: Verificar Permissões

**Via File Manager:**
1. Clique com botão direito em `public_html/`
2. Verifique permissões (deve ser 755)
3. Se `leads.csv` existe, verifique permissões (deve ser 644 ou 666)

**Via SSH (se tiver acesso):**
```bash
chmod 755 public_html/
chmod 666 public_html/leads.csv
```

### Correção 3: Configurar Banco de Dados

Se o banco não está configurado, configure seguindo `CONFIGURAR_BANCO_AGORA.md`.

## Próximos Passos

1. **Execute o script de diagnóstico:** `debug-save-path.php`
2. **Me envie os resultados** do diagnóstico
3. **Verifique manualmente** se `leads.csv` existe
4. **Teste o salvamento manual** com `test-save.php`

Com essas informações, posso identificar exatamente qual é o problema!

---

**Última atualização:** 23/01/2025
