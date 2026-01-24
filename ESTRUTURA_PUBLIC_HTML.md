# 📁 Estrutura public_html Aninhada

## Problema Identificado

No Hostinger, há uma estrutura aninhada:
```
/home/u485294289/domains/senior-floors.com/
  └── public_html/
      └── public_html/  ← Estrutura aninhada!
          ├── lp/
          │   └── send-lead.php
          ├── admin-modules/
          │   └── crm.php
          └── leads.csv (deveria estar aqui)
```

Isso causa problemas porque:
- `send-lead.php` está em `public_html/public_html/lp/`
- Quando faz `dirname(__DIR__)`, vai para `public_html/public_html/`
- Mas o `DOCUMENT_ROOT` aponta para `public_html/public_html/` (correto)
- Então precisamos usar `DOCUMENT_ROOT` em vez de `dirname(__DIR__)`

## Correções Implementadas

### 1. send-lead.php
- Agora usa `$_SERVER['DOCUMENT_ROOT']` como prioridade
- Fallback inteligente se `DOCUMENT_ROOT` não estiver disponível
- Detecta estrutura aninhada automaticamente

### 2. admin-modules/crm.php
- Agora usa `$_SERVER['DOCUMENT_ROOT']` para ler o CSV
- Garante que lê do mesmo lugar que `send-lead.php` salva

## Como Verificar

### 1. Verificar Estrutura
**Via File Manager:**
1. Acesse `public_html/`
2. Veja se há outra pasta `public_html/` dentro
3. Confirme onde estão os arquivos:
   - `send-lead.php` deve estar em `public_html/public_html/lp/`
   - `crm.php` deve estar em `public_html/public_html/admin-modules/`
   - `leads.csv` deve estar em `public_html/public_html/`

### 2. Verificar DOCUMENT_ROOT
Execute:
```php
<?php
echo $_SERVER['DOCUMENT_ROOT'];
?>
```

Deve mostrar: `/home/u485294289/domains/senior-floors.com/public_html/public_html`

### 3. Testar
1. Preencha o formulário
2. Envie
3. Verifique se aparece no sistema

## Se Ainda Não Funcionar

### Opção 1: Mover Arquivos
Se possível, mova os arquivos para a estrutura correta:
```
public_html/
  ├── lp/
  │   └── send-lead.php
  ├── admin-modules/
  │   └── crm.php
  └── leads.csv
```

### Opção 2: Usar Caminho Absoluto
Se a estrutura aninhada for necessária, podemos usar caminho absoluto fixo:
```php
$log_file = '/home/u485294289/domains/senior-floors.com/public_html/public_html/leads.csv';
```

Mas isso não é recomendado pois quebra se mudar de servidor.

### Opção 3: Configurar Variável
Criar um arquivo de configuração com o caminho:
```php
// config/paths.php
define('CSV_FILE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/leads.csv');
```

## Próximos Passos

1. **Aguarde o deploy** das correções
2. **Teste o formulário** novamente
3. **Verifique se aparece no sistema**

---

**Última atualização:** 23/01/2025
