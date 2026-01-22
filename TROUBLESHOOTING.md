# Guia de Troubleshooting - Formulário de Contato

## Problema: Mensagem de sucesso não aparece

Se você não está vendo a mensagem "formulário enviado", siga estes passos:

### 1. Verificar se o PHP está funcionando

1. Faça upload do arquivo `test-form-handler.php` para o servidor
2. Acesse: `https://seudominio.com/test-form-handler.php`
3. Você deve ver informações sobre o PHP em formato JSON
4. Se aparecer erro 404 ou 500, há um problema com o PHP no servidor

### 2. Verificar o Console do Navegador

1. Abra o site no navegador
2. Pressione F12 (ou Cmd+Option+I no Mac) para abrir as ferramentas de desenvolvedor
3. Vá na aba "Console"
4. Preencha e envie o formulário
5. Veja se aparecem erros em vermelho
6. Anote qualquer erro que aparecer

### 3. Verificar a Aba Network (Rede)

1. Nas ferramentas de desenvolvedor, vá na aba "Network" (Rede)
2. Limpe a lista (ícone de limpar)
3. Preencha e envie o formulário
4. Procure por `contact-form-handler.php` na lista
5. Clique nele e veja:
   - **Status**: Deve ser 200 (OK) ou outro código
   - **Response**: Deve mostrar JSON com `{"success": true, "message": "..."}`
   - Se aparecer erro 404: o arquivo não está no lugar certo
   - Se aparecer erro 500: há um erro no PHP

### 4. Verificar Localização dos Arquivos

Certifique-se de que:
- ✅ `contact-form-handler.php` está na mesma pasta que `index.html`
- ✅ Geralmente isso é a pasta `public_html` no Hostinger
- ✅ O arquivo tem permissões 644

### 5. Verificar Erros do PHP

1. Acesse o hPanel do Hostinger
2. Vá em **Files** > **File Manager**
3. Procure pelos arquivos de log:
   - `form-errors.log` - mostra erros do PHP
   - `form-submissions.log` - mostra todos os envios (mesmo se email falhar)
4. Se esses arquivos existirem, abra-os para ver o que está acontecendo

### 6. Testar o Formulário Manualmente

Crie um arquivo `test-submit.html` com este conteúdo:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Test Form</title>
</head>
<body>
    <form id="testForm">
        <input type="hidden" name="form-name" value="test-form">
        <input type="text" name="name" value="Test Name" required>
        <input type="tel" name="phone" value="1234567890" required>
        <input type="email" name="email" value="test@example.com" required>
        <input type="text" name="zipcode" value="12345" required>
        <button type="submit">Test Submit</button>
    </form>
    
    <div id="result"></div>
    
    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const response = await fetch('contact-form-handler.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();
                document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';
            } catch (error) {
                document.getElementById('result').innerHTML = 'Error: ' + error.message;
            }
        });
    </script>
</body>
</html>
```

1. Faça upload deste arquivo
2. Acesse no navegador
3. Clique em "Test Submit"
4. Veja o que aparece no resultado

### 7. Verificar Configurações do Hostinger

1. No hPanel, verifique se PHP está habilitado
2. Verifique a versão do PHP (deve ser 7.4 ou superior)
3. Verifique se há alguma restrição de segurança bloqueando o arquivo

### 8. Solução Rápida: Usar Formspree (Alternativa)

Se nada funcionar, você pode usar Formspree temporariamente:

1. Acesse https://formspree.io
2. Crie uma conta gratuita
3. Crie um novo formulário
4. Copie o endpoint (ex: `https://formspree.io/f/YOUR_FORM_ID`)
5. No arquivo `script.js`, substitua:
   ```javascript
   fetch('contact-form-handler.php', {
   ```
   Por:
   ```javascript
   fetch('https://formspree.io/f/YOUR_FORM_ID', {
   ```

## Checklist Rápido

- [ ] Arquivo `contact-form-handler.php` está na pasta `public_html`?
- [ ] Permissões do arquivo estão como 644?
- [ ] PHP está habilitado no Hostinger?
- [ ] Não há erros no Console do navegador?
- [ ] A resposta do servidor é JSON válido?
- [ ] Os arquivos de log foram criados?

## Contato para Suporte

Se nenhuma das soluções funcionar:
1. Anote todos os erros que aparecem no Console
2. Anote o que aparece na aba Network quando envia o formulário
3. Verifique os arquivos de log
4. Entre em contato com o suporte do Hostinger com essas informações
