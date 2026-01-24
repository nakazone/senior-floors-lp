# ✅ Correção: Caminho do CSV

## Problema Identificado

O `send-lead.php` estava salvando o CSV em:
```
/home/u485294289/domains/senior-floors.com/leads.csv
```

Mas deveria salvar em:
```
/home/u485294289/domains/senior-floors.com/public_html/leads.csv
```

## Causa

O `dirname(__DIR__)` estava indo um nível acima demais quando `send-lead.php` está em `public_html/lp/`.

## Correção Implementada

Agora o código:
1. Tenta usar `dirname(__DIR__)` primeiro
2. Verifica se o caminho contém `public_html`
3. Se não contém, usa `$_SERVER['DOCUMENT_ROOT']` como fallback

Isso garante que o CSV seja salvo em `public_html/leads.csv`, onde o CRM está lendo.

## Como Verificar

### 1. Teste Novamente
1. Preencha o formulário
2. Envie
3. Verifique se aparece no sistema

### 2. Verifique o Arquivo
**Via File Manager:**
1. Acesse `public_html/leads.csv`
2. Veja se o arquivo foi atualizado
3. Veja se tem o novo lead

### 3. Execute o Script de Diagnóstico
Acesse: `https://seudominio.com/debug-save-path.php`

Agora deve mostrar:
- ✅ Arquivo existe em `public_html/leads.csv`
- ✅ send-lead.php e CRM estão usando o mesmo arquivo

## Próximos Passos

1. **Aguarde o deploy** (ou faça upload manual do `send-lead.php` atualizado)
2. **Teste o formulário** novamente
3. **Verifique se aparece no sistema**

---

**Última atualização:** 23/01/2025
