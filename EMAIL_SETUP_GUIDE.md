# Guia de Configuração de Email - Senior Floors

## Problema: Emails não estão chegando em leads@senior-floors.com

O formulário está funcionando e salvando os leads, mas os emails não estão sendo enviados. Isso é comum em servidores compartilhados como Hostinger.

## ✅ Solução Implementada

O código agora **SEMPRE salva os leads** em um arquivo CSV (`leads.csv`) mesmo se o email falhar. **Nenhum lead será perdido!**

## 📋 Como Ver os Leads Salvos

### Opção 1: Via FTP/File Manager

1. Acesse seu servidor via FTP ou File Manager do Hostinger
2. Procure pelo arquivo `leads.csv` na pasta `public_html`
3. Baixe o arquivo e abra no Excel ou Google Sheets
4. Todos os leads estarão lá em formato CSV

### Opção 2: Via Navegador (Recomendado)

1. Faça upload do arquivo `view-leads.php` para o servidor
2. Acesse: `https://seudominio.com/view-leads.php`
3. A senha padrão é: `change-this-password-123`
4. **IMPORTANTE**: Altere a senha no arquivo antes de fazer upload!
5. Você verá todos os leads em uma tabela organizada
6. Pode baixar o CSV diretamente da página

## 🔧 Como Fazer os Emails Funcionarem

### Método 1: Criar Conta de Email no Hostinger (Recomendado)

1. Acesse o hPanel do Hostinger
2. Vá em **Email Accounts**
3. Crie uma conta de email: `noreply@senior-floors.com`
4. Anote a senha
5. Teste enviando um email manualmente para `leads@senior-floors.com`
6. Se funcionar, o formulário também deve funcionar

### Método 2: Usar SMTP (Mais Confiável)

1. No hPanel, vá em **Email Accounts**
2. Crie ou use uma conta de email existente
3. Anote as configurações SMTP:
   - Servidor: `smtp.hostinger.com` ou `smtp.titan.email`
   - Porta: `587` (TLS) ou `465` (SSL)
   - Usuário: seu email completo
   - Senha: senha da conta

4. Use o arquivo `contact-form-handler-smtp.php`:
   - Edite o arquivo e atualize as configurações SMTP
   - Renomeie para `contact-form-handler.php`
   - Faça upload

### Método 3: Usar Serviço de Terceiros (Mais Fácil)

#### Opção A: Formspree (Gratuito até 50/mês)

1. Acesse https://formspree.io
2. Crie uma conta gratuita
3. Crie um novo formulário
4. Configure para enviar para `leads@senior-floors.com`
5. Copie o endpoint (ex: `https://formspree.io/f/YOUR_ID`)
6. No arquivo `script.js`, substitua:
   ```javascript
   fetch('contact-form-handler.php', {
   ```
   Por:
   ```javascript
   fetch('https://formspree.io/f/YOUR_ID', {
   ```

#### Opção B: EmailJS (Gratuito até 200/mês)

1. Acesse https://www.emailjs.com
2. Crie uma conta gratuita
3. Configure o serviço de email
4. Atualize o JavaScript para usar EmailJS

## 📊 Verificar Status dos Emails

O arquivo `email-status.log` mostra o status de cada tentativa de envio:
- `Sent` = email foi enviado com sucesso
- `Failed` = email falhou (mas o lead foi salvo no CSV)

## ✅ Checklist

- [ ] Arquivo `leads.csv` está sendo criado?
- [ ] Você consegue ver os leads via `view-leads.php`?
- [ ] Conta de email `noreply@senior-floors.com` existe no Hostinger?
- [ ] Conta de email `leads@senior-floors.com` existe e está funcionando?
- [ ] Você verificou a pasta de spam?

## 🆘 Importante

**Mesmo que os emails não funcionem, todos os leads estão sendo salvos no arquivo `leads.csv`!**

Você pode:
1. Baixar o CSV via FTP
2. Ver os leads via `view-leads.php`
3. Importar para Excel/Google Sheets
4. Configurar notificações automáticas depois

## 📞 Próximos Passos

1. **Imediato**: Verifique o arquivo `leads.csv` para ver os leads já salvos
2. **Curto prazo**: Configure SMTP ou use Formspree para emails automáticos
3. **Longo prazo**: Configure notificações por email quando novos leads chegarem
