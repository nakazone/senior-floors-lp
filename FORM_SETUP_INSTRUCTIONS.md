# Instruções para Configurar o Formulário de Contato

## Problema: Emails não estão chegando

Se os emails não estão chegando em `leads@senior-floors.com`, siga estas instruções:

## Solução 1: Configurar SMTP (Recomendado)

O arquivo `contact-form-handler-smtp.php` usa SMTP, que é mais confiável no Hostinger.

### Passos:

1. **Obter credenciais SMTP do Hostinger:**
   - Acesse o hPanel do Hostinger
   - Vá em **Email Accounts** (Contas de Email)
   - Crie uma conta de email (ex: `noreply@senior-floors.com`) se ainda não tiver
   - Anote as configurações SMTP:
     - Servidor SMTP: geralmente `smtp.hostinger.com` ou `smtp.titan.email`
     - Porta: geralmente `587` (TLS) ou `465` (SSL)
     - Usuário: seu email completo
     - Senha: senha da conta de email

2. **Editar o arquivo `contact-form-handler-smtp.php`:**
   - Abra o arquivo
   - Atualize as constantes no topo:
     ```php
     define('SMTP_HOST', 'smtp.hostinger.com');
     define('SMTP_PORT', 587);
     define('SMTP_USER', 'noreply@senior-floors.com');
     define('SMTP_PASS', 'SUA_SENHA_AQUI');
     define('SMTP_SECURE', 'tls');
     ```

3. **Substituir o arquivo atual:**
   - Faça backup do `contact-form-handler.php` atual
   - Renomeie `contact-form-handler-smtp.php` para `contact-form-handler.php`
   - Faça upload do novo arquivo para o servidor

## Solução 2: Verificar Configurações do PHP mail()

Se preferir usar o método atual (`contact-form-handler.php`):

1. **Verificar se o email do remetente existe:**
   - No hPanel, crie a conta de email `noreply@senior-floors.com`
   - Isso é importante para evitar que os emails sejam marcados como spam

2. **Verificar logs de erro:**
   - Após fazer upload, verifique se os arquivos `form-errors.log` e `form-submissions.log` foram criados
   - Esses arquivos mostram se há erros e salvam os leads mesmo se o email falhar

3. **Testar o formulário:**
   - Preencha o formulário no site
   - Verifique se aparece mensagem de sucesso
   - Verifique a pasta de spam do `leads@senior-floors.com`

## Solução 3: Usar Serviço de Email de Terceiros

Se nenhuma das soluções acima funcionar, você pode usar:

### Opção A: Formspree (Gratuito até 50 envios/mês)
1. Acesse https://formspree.io
2. Crie uma conta gratuita
3. Crie um novo formulário
4. Copie o endpoint fornecido
5. Atualize o JavaScript em `script.js` para usar o endpoint do Formspree

### Opção B: EmailJS (Gratuito até 200 envios/mês)
1. Acesse https://www.emailjs.com
2. Crie uma conta gratuita
3. Configure o serviço de email
4. Atualize o JavaScript para usar EmailJS

## Verificação Rápida

1. ✅ O arquivo `contact-form-handler.php` está na pasta `public_html`?
2. ✅ As permissões do arquivo estão como 644?
3. ✅ A conta de email `noreply@senior-floors.com` existe no Hostinger?
4. ✅ O email `leads@senior-floors.com` existe e está funcionando?
5. ✅ Você verificou a pasta de spam?

## Teste do Formulário

Para testar se o formulário está funcionando:

1. Preencha todos os campos obrigatórios
2. Clique em "Enviar"
3. Verifique se aparece a mensagem de sucesso
4. Verifique o email `leads@senior-floors.com` (incluindo spam)
5. Verifique os arquivos de log (se existirem):
   - `form-submissions.log` - mostra todos os envios
   - `form-errors.log` - mostra erros (se houver)

## Suporte

Se ainda não funcionar:
- Verifique os logs de erro do PHP no hPanel
- Entre em contato com o suporte do Hostinger
- Considere usar um serviço de terceiros (Formspree ou EmailJS)
