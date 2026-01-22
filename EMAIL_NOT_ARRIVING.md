# Email Não Está Chegando - Diagnóstico Completo

## ✅ Confirmado: SMTP Aceita o Email

Os logs mostram que o SMTP do Google **aceita** o email para entrega:
```
Email sent successfully to leads@senior-floors.com
```

Mas o email não está chegando. Isso pode significar:

## 🔍 Possíveis Causas

### 1. Google Workspace Está Rejeitando Silenciosamente

Mesmo que o SMTP aceite, o Google pode estar:
- Rejeitando emails de contas não verificadas
- Aplicando políticas de segurança
- Bloqueando emails de servidores externos

### 2. Problema com a Conta `leads@senior-floors.com`

- A conta pode não existir
- A conta pode estar desativada
- A conta pode ter restrições de recebimento

### 3. Problema com a Conta `contact@senior-floors.com`

- A conta pode não ter permissão para enviar
- A conta pode estar marcada como spammer
- A App Password pode estar incorreta

## 🧪 Testes para Diagnosticar

### Teste 1: Verificar se `leads@senior-floors.com` Existe

1. Acesse o Google Admin Console
2. Vá em **Usuários**
3. Procure por `leads@senior-floors.com`
4. Verifique se:
   - A conta existe
   - A conta está ativa
   - A conta não está suspensa

### Teste 2: Enviar Email Manualmente

1. Abra o Gmail de `contact@senior-floors.com`
2. Envie um email manualmente para `leads@senior-floors.com`
3. Assunto: "Test Manual"
4. Verifique se chegou

**Se o email manual chegou:**
- O problema é com o código/envio automático

**Se o email manual NÃO chegou:**
- Há um problema com a conta `leads@senior-floors.com`
- Verifique no Google Admin Console

### Teste 3: Usar `test-email-direct.php`

1. Faça upload do arquivo `test-email-direct.php`
2. Acesse: `https://seudominio.com/test-email-direct.php`
3. Senha: `test123`
4. Envie um email de teste
5. Verifique os logs detalhados

### Teste 4: Verificar Logs Detalhados

1. Acesse `check-email-status.php`
2. Veja a resposta completa do SMTP
3. Procure por mensagens de erro específicas

## 🔧 Soluções

### Solução 1: Verificar Google Workspace Admin

1. Acesse o Google Admin Console
2. Vá em **Segurança** > **Regras de roteamento de email**
3. Verifique se há regras bloqueando emails
4. Vá em **Segurança** > **Configurações de email**
5. Verifique políticas de spam/quarentena

### Solução 2: Usar Email Diferente para Teste

Tente enviar para outro email do Google Workspace:
- `contact@senior-floors.com` (você mesmo)
- Outro email que você sabe que funciona

Se funcionar, o problema é específico com `leads@senior-floors.com`

### Solução 3: Verificar App Password

1. No Google Account de `contact@senior-floors.com`
2. Vá em **Senhas de app**
3. Verifique se a App Password ainda está ativa
4. Se necessário, crie uma nova App Password
5. Atualize no `contact-form-handler.php`

### Solução 4: Usar Porta 465 com SSL

Tente mudar a configuração SMTP:

```php
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');
```

Alguns servidores bloqueiam a porta 587.

### Solução 5: Verificar Firewall do Servidor

O servidor Hostinger pode estar bloqueando conexões SMTP externas:
- Entre em contato com o suporte do Hostinger
- Peça para verificar se a porta 587 (ou 465) está aberta
- Verifique se há firewall bloqueando

## 📊 Verificar Logs Detalhados

O arquivo `email-status.log` agora mostra a resposta completa do SMTP. Procure por:

- `250 2.0.0 OK` = Email aceito para entrega
- `550` = Email rejeitado
- `553` = Endereço de email inválido
- `554` = Transação falhou

## ✅ Checklist de Diagnóstico

- [ ] Conta `leads@senior-floors.com` existe no Google Workspace?
- [ ] Conta `leads@senior-floors.com` está ativa?
- [ ] Testou enviar email manualmente?
- [ ] Email manual chegou?
- [ ] App Password está correta?
- [ ] Verificou Google Admin Console por regras bloqueando?
- [ ] Testou enviar para outro email?
- [ ] Verificou logs detalhados do SMTP?
- [ ] Contatou suporte do Hostinger sobre firewall?

## 🆘 Se Nada Funcionar

### Opção 1: Usar Serviço de Terceiros

Use Formspree ou EmailJS que são mais confiáveis:

1. **Formspree**: https://formspree.io
   - Gratuito até 50 envios/mês
   - Muito confiável
   - Fácil de configurar

2. **EmailJS**: https://www.emailjs.com
   - Gratuito até 200 envios/mês
   - Funciona direto do JavaScript

### Opção 2: Usar CSV (Já Funcionando)

Todos os leads estão sendo salvos em `leads.csv`:
- Acesse via `view-leads.php`
- Baixe via FTP
- Configure notificações depois

### Opção 3: Configurar Webhook

Configure um webhook que envia notificação quando novo lead é salvo no CSV.

## 💡 Próximos Passos Recomendados

1. **Imediato**: Teste enviar email manualmente
2. **Curto prazo**: Verifique Google Admin Console
3. **Médio prazo**: Considere usar Formspree se o problema persistir
4. **Longo prazo**: Configure notificações automáticas

---

**Lembre-se**: Todos os leads estão sendo salvos em `leads.csv`, então nenhum lead está sendo perdido mesmo se o email não funcionar!
