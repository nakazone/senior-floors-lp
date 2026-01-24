# ✅ Correção Final: Caminhos do CSV

## Problema Identificado

- **send-lead.php** estava salvando em: `/home/u485294289/domains/senior-floors.com/leads.csv` (root do domínio)
- **CRM** estava lendo de: `/home/u485294289/domains/senior-floors.com/public_html/leads.csv` (dentro de public_html)

**Resultado:** Arquivos em lugares diferentes! ❌

## Estrutura Real

```
/home/u485294289/domains/senior-floors.com/
  ├── public_html/
  │   ├── lp/
  │   │   └── send-lead.php
  │   ├── admin-modules/
  │   │   └── crm.php
  │   └── leads.csv ← DEVE ESTAR AQUI
  └── leads.csv ← send-lead.php estava salvando aqui (ERRADO)
```

## Correção Implementada

### send-lead.php
Agora usa lógica inteligente:
1. **Primeiro:** Tenta `$_SERVER['DOCUMENT_ROOT']` (mais confiável)
2. **Segundo:** Usa `dirname(__DIR__)` se estiver em `public_html/lp/`
3. **Terceiro:** Busca o diretório `public_html` manualmente

### admin-modules/crm.php
Agora usa a mesma lógica:
1. **Primeiro:** Tenta `$_SERVER['DOCUMENT_ROOT']`
2. **Segundo:** Usa `dirname(__DIR__)` se estiver em `public_html/admin-modules/`

**Resultado:** Ambos usam o mesmo caminho! ✅

## Como Verificar

### 1. Execute o Script de Diagnóstico
```
https://seudominio.com/debug-save-path.php
```

Agora deve mostrar:
- ✅ send-lead.php salva em: `.../public_html/leads.csv`
- ✅ CRM lê de: `.../public_html/leads.csv`
- ✅ **MESMO ARQUIVO!**

### 2. Teste o Formulário
1. Preencha o formulário
2. Envie
3. Verifique se aparece no sistema

### 3. Verifique o Arquivo
**Via File Manager:**
1. Acesse `public_html/leads.csv`
2. Veja se tem o novo lead
3. Veja a data de modificação (deve ser recente)

## Se Ainda Não Funcionar

### Verificar DOCUMENT_ROOT
Crie um arquivo `test-docroot.php`:
```php
<?php
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
?>
```

Execute e me envie o resultado.

### Mover Arquivo Existente
Se o arquivo `leads.csv` está no root do domínio:
1. Via File Manager, mova de `/home/u485294289/domains/senior-floors.com/leads.csv`
2. Para `/home/u485294289/domains/senior-floors.com/public_html/leads.csv`

---

**Última atualização:** 24/01/2025
