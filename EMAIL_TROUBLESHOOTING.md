# Troubleshooting - Email não chegou na caixa de entrada

## ✅ Email foi enviado com sucesso, mas não chegou?

Se o log mostra `✅ Email sent successfully` mas você não recebeu o email, siga estes passos:

### 1. Verificar Status do Email

1. Faça upload do arquivo `check-email-status.php` para o servidor
2. Acesse: `https://seudominio.com/check-email-status.php`
3. A senha padrão é: `change-this-password-123` (altere antes de fazer upload!)
4. Veja o status detalhado de cada envio

### 2. Verificar no Gmail

#### Pasta de Spam
- Abra o Gmail de `leads@senior-floors.com`
- Vá em **Spam** ou **Lixo eletrônico**
- Procure por emails de `contact@senior-floors.com`
- Se encontrar, marque como "Não é spam"

#### Pasta "Todos os emails"
- No Gmail, clique em **Todos os emails** (no menu lateral)
- Procure por emails de `contact@senior-floors.com`
- Ou procure pelo assunto: "New Lead from Senior Floors Website"

#### Filtros do Gmail
- Vá em **Configurações** > **Filtros e endereços bloqueados**
- Verifique se há filtros que podem estar escondendo os emails
- Verifique se `contact@senior-floors.com` não está bloqueado

#### Pesquisar no Gmail
- Use a busca: `from:contact@senior-floors.com`
- Ou: `subject:"New Lead from Senior Floors"`
- Ou: `leads@senior-floors.com` (para ver todos os emails recebidos)

### 3. Verificar Configurações do Google Workspace

#### Verificar se o email existe
- Confirme que `leads@senior-floors.com` existe no Google Workspace
- Teste enviando um email manualmente do Gmail para `leads@senior-floors.com`

#### Verificar permissões
- No Google Admin Console, verifique se `contact@senior-floors.com` tem permissão para enviar emails
- Verifique se não há restrições de envio

### 4. Verificar Logs Detalhados

O arquivo `email-status.log` mostra informações detalhadas:

```
✅ Email sent successfully to leads@senior-floors.com
   From: contact@senior-floors.com
   Subject: New Lead from Senior Floors Website - Hero Form
   SMTP Response: 250 2.0.0 OK
```

Se você vê isso, o email FOI enviado com sucesso pelo servidor SMTP do Google.

### 5. Possíveis Causas

#### A. Email está em Spam
- **Solução**: Marque como "Não é spam" e crie um filtro para sempre enviar para a caixa de entrada

#### B. Atraso no envio
- **Solução**: Aguarde alguns minutos (pode levar até 5-10 minutos)

#### C. Filtros do Gmail
- **Solução**: Verifique e ajuste os filtros

#### D. Email foi deletado automaticamente
- **Solução**: Verifique a lixeira do Gmail

#### E. Problema com o Google Workspace
- **Solução**: Entre em contato com o suporte do Google Workspace

### 6. Teste Manual

Para confirmar que o email funciona:

1. Abra o Gmail de `contact@senior-floors.com`
2. Envie um email manualmente para `leads@senior-floors.com`
3. Verifique se chegou
4. Se chegou, o problema pode ser com o código
5. Se não chegou, há um problema com a conta `leads@senior-floors.com`

### 7. Solução Temporária

Enquanto resolve o problema do email:

1. **Todos os leads estão salvos em `leads.csv`**
2. Acesse via `view-leads.php` para ver todos os leads
3. Baixe o CSV via FTP
4. Configure notificações depois

### 8. Criar Filtro no Gmail (Recomendado)

Para garantir que os emails sempre cheguem na caixa de entrada:

1. No Gmail de `leads@senior-floors.com`
2. Vá em **Configurações** > **Filtros e endereços bloqueados**
3. Clique em **Criar um novo filtro**
4. Em **De**, digite: `contact@senior-floors.com`
5. Clique em **Criar filtro**
6. Marque:
   - ✅ Nunca enviar para Spam
   - ✅ Sempre marcá-lo como importante
   - ✅ Aplicar o rótulo: "Leads" (crie o rótulo se necessário)
7. Clique em **Criar filtro**

### 9. Verificar Arquivo de Log

Se o log mostra `✅ Email sent successfully`, o email FOI enviado. O problema está no Gmail, não no código.

Verifique:
- `email-status.log` - mostra status detalhado
- `form-submissions.log` - mostra todos os envios
- `leads.csv` - mostra todos os leads salvos

## ✅ Checklist

- [ ] Verificou pasta de spam?
- [ ] Verificou "Todos os emails"?
- [ ] Verificou filtros do Gmail?
- [ ] Testou enviar email manualmente?
- [ ] Verificou logs em `check-email-status.php`?
- [ ] Criou filtro no Gmail para garantir entrega?
- [ ] Verificou se `leads@senior-floors.com` existe?

## 🆘 Se Nada Funcionar

1. Verifique os logs detalhados em `check-email-status.php`
2. Teste enviar email manualmente
3. Entre em contato com o suporte do Google Workspace
4. Use `view-leads.php` para ver os leads enquanto resolve o problema

**Lembre-se**: Todos os leads estão sendo salvos em `leads.csv`, então nenhum lead será perdido mesmo se o email não funcionar!
