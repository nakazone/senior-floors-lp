# Configuração para Google Workspace - Senior Floors

## 📧 Configuração Atual

- **Remetente (From)**: `contact@senior-floors.com` (Google Workspace)
- **Destinatário (To)**: `leads@senior-floors.com` (Google Workspace)
- **Método**: SMTP do Google Workspace

## ✅ Vantagens desta Configuração

- ✅ Google para Google = mais confiável
- ✅ Menos chance de ir para spam
- ✅ Não depende do SMTP do Hostinger
- ✅ Emails chegam mais rápido

## 📋 Passo a Passo

### 1. Criar App Password para `contact@senior-floors.com`

⚠️ **IMPORTANTE**: Você precisa criar uma "App Password" para a conta `contact@senior-floors.com`.

#### Passos:

1. Acesse o Google Admin Console ou faça login em: https://myaccount.google.com/security
   - Use a conta `contact@senior-floors.com`
2. Vá em: **Segurança** > **Verificação em duas etapas**
3. **Ative a Verificação em duas etapas** (se ainda não tiver)
   - Isso é obrigatório para criar App Passwords
4. Depois de ativar, vá em: **Senhas de app** ou acesse: https://myaccount.google.com/apppasswords
5. Selecione:
   - **App**: Escolha "Outro (nome personalizado)"
   - **Nome**: Digite "Senior Floors Contact Form"
6. Clique em **Gerar**
7. **COPIE A SENHA** que aparece (16 caracteres)
   - Exemplo: `abcd efgh ijkl mnop` → use `abcdefghijklmnop` (sem espaços)

### 2. Configurar o Arquivo PHP

1. Abra o arquivo `contact-form-handler.php`
2. Encontre estas linhas (por volta da linha 60-65):

```php
define('SMTP_USER', 'contact@senior-floors.com'); // Google Workspace email (sender)
define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE'); // App Password for contact@senior-floors.com
```

3. Substitua `YOUR_APP_PASSWORD_HERE` pela App Password que você copiou
   - Cole os 16 caracteres sem espaços
   - Exemplo: `abcdefghijklmnop`

4. Salve o arquivo
5. Faça upload para o servidor

### 3. Testar

1. Preencha o formulário no site
2. Envie
3. Verifique se o email chegou em `leads@senior-floors.com`
4. Verifique também a pasta de spam (primeira vez pode ir para lá)

## 🔍 Verificar se Está Funcionando

### Verificar Logs

O arquivo `email-status.log` mostra o status de cada envio:
- `Email sent successfully to leads@senior-floors.com` = funcionou! ✅
- `SMTP Authentication failed` = problema com App Password
- `SMTP Connection failed` = problema de conexão
- `SMTP not configured` = App Password não foi configurada

### Verificar Leads Salvos

Mesmo se o email falhar, todos os leads são salvos em `leads.csv`. Você pode:
- Ver via `view-leads.php`
- Baixar via FTP

## ❌ Problemas Comuns

### "SMTP Authentication failed"

**Soluções:**
- Verifique se está usando a **App Password** (não a senha normal)
- Verifique se copiou corretamente (16 caracteres, sem espaços)
- Verifique se a verificação em duas etapas está ativada na conta `contact@senior-floors.com`
- Verifique se está usando a App Password da conta correta (`contact@senior-floors.com`)

### "SMTP Connection failed"

**Soluções:**
- Verifique sua conexão com a internet
- Verifique se o servidor Hostinger permite conexões SMTP externas (porta 587)
- Tente usar a porta 465 com SSL em vez de 587 com TLS (mude `SMTP_PORT` para `465` e `SMTP_SECURE` para `'ssl'`)

### Email não chega em `leads@senior-floors.com`

**Soluções:**
- Verifique a pasta de spam
- Verifique se `leads@senior-floors.com` existe e está funcionando
- Teste enviar um email manualmente do Gmail para `leads@senior-floors.com`
- Verifique os logs em `email-status.log`
- Verifique se o email `contact@senior-floors.com` tem permissão para enviar emails

### "SMTP not configured"

**Solução:**
- Você não atualizou a App Password no arquivo PHP
- Substitua `YOUR_APP_PASSWORD_HERE` pela App Password real

## 🔐 Segurança

⚠️ **IMPORTANTE**: O arquivo PHP contém uma senha. Mantenha-o seguro:
- Não compartilhe o arquivo publicamente
- Use permissões 644 no arquivo
- Considere mover as credenciais para um arquivo de configuração separado (fora do public_html)
- A App Password é específica para este uso - se comprometida, você pode revogá-la e criar uma nova

## 📝 Resumo da Configuração

```
Formulário no Site
    ↓
contact-form-handler.php (Hostinger)
    ↓
SMTP do Google (smtp.gmail.com)
    ↓
Usando: contact@senior-floors.com (com App Password)
    ↓
Enviando para: leads@senior-floors.com
```

## ✅ Checklist

- [ ] Verificação em duas etapas ativada em `contact@senior-floors.com`
- [ ] App Password criada para `contact@senior-floors.com`
- [ ] App Password copiada (16 caracteres, sem espaços)
- [ ] `contact-form-handler.php` atualizado com App Password
- [ ] Arquivo enviado para o servidor
- [ ] Formulário testado
- [ ] Email recebido em `leads@senior-floors.com`
- [ ] Verificado pasta de spam (primeira vez)

## 🆘 Precisa de Ajuda?

Se ainda não funcionar:
1. Verifique os logs em `email-status.log`
2. Teste enviar um email manualmente do Gmail (`contact@senior-floors.com`) para `leads@senior-floors.com`
3. Verifique se a App Password está correta
4. Tente usar porta 465 com SSL
5. Entre em contato com o suporte do Google Workspace se necessário

## 💡 Dica Extra

Se quiser, você também pode configurar um filtro no Gmail de `leads@senior-floors.com` para:
- Marcar emails de `contact@senior-floors.com` como importantes
- Criar uma label automática
- Encaminhar para outros emails
