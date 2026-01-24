# 🔄 Forçar Uso do CSV no CRM

## Problema
O sistema está consumindo do banco de dados em vez do CSV.

## Solução Implementada

Agora o CRM tem uma opção para **forçar o uso do CSV**, mesmo se o banco de dados estiver configurado e tiver dados.

## Como Usar

### Opção 1: Via Link no CRM
1. Acesse `system.php?module=crm`
2. Veja a mensagem "Fonte de dados: MySQL Database"
3. Clique no link **"🔀 Usar CSV"**
4. O CRM vai recarregar usando o CSV

### Opção 2: Via URL Direta
Acesse:
```
system.php?module=crm&force_csv=1
```

Isso força o CRM a usar o CSV em vez do banco de dados.

### Opção 3: Voltar para Banco de Dados
Se estiver usando CSV e quiser voltar para o banco:
- Clique no link **"🔀 Usar Banco de Dados"** no CRM
- Ou acesse: `system.php?module=crm` (sem o parâmetro `force_csv`)

## Por Que Isso Acontece?

O CRM prioriza o banco de dados porque:
1. **Banco é mais eficiente** para grandes volumes de dados
2. **Banco permite** funcionalidades avançadas (status, tags, notas)
3. **Banco é mais confiável** para produção

Mas se você quer usar CSV (por exemplo, para testar ou se o banco não está atualizado), agora pode forçar.

## Verificar Qual Está Sendo Usado

No topo do CRM, você verá:
- **"📊 Fonte de dados: MySQL Database"** - Usando banco
- **"📊 Fonte de dados: CSV File"** - Usando CSV

## Próximos Passos

1. **Aguarde o deploy** das correções
2. **Acesse o CRM** e clique em "Usar CSV"
3. **Verifique** se os leads aparecem

---

**Última atualização:** 24/01/2025
