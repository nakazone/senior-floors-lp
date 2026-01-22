# Setup PHPMailer Manual no Hostinger

## ✅ Análise do Código do ChatGPT

O código fornecido é **bom e vai funcionar**, mas fiz algumas adaptações:

### ✅ O que mantive:
- PHPMailer manual (sem Composer) - perfeito para Hostinger
- SMTP do Gmail com App Password
- Validação de dados
- Sanitização de inputs

### 🔧 O que adaptei:
- ✅ Mantive resposta JSON (não redirecionamento) para funcionar com JavaScript atual
- ✅ Adicionei campo `zipcode` e `form-name` que seus formulários usam
- ✅ Mantive salvamento em CSV (backup)
- ✅ Usei `contact@senior-floors.com` como remetente (não `leads@`)
- ✅ Enviando para `leads@senior-floors.com` como destinatário
- ✅ Email HTML formatado + texto simples
- ✅ Logs detalhados para debug

## 📋 Passo a Passo Completo

### 1. Baixar PHPMailer

1. Acesse: https://github.com/PHPMailer/PHPMailer
2. Clique em **Code** > **Download ZIP**
3. Extraia o arquivo ZIP
4. Você precisa da pasta `PHPMailer` com estes arquivos:
   - `Exception.php`
   - `PHPMailer.php`
   - `SMTP.php`

### 2. Upload para o Servidor

Via FTP ou File Manager do Hostinger:

1. Crie uma pasta chamada `PHPMailer` na pasta `public_html`
2. Faça upload dos 3 arquivos:
   - `PHPMailer/Exception.php`
   - `PHPMailer/PHPMailer.php`
   - `PHPMailer/SMTP.php`

Estrutura final:
```
public_html/
├── index.html
├── script.js
├── send-lead.php
└── PHPMailer/
    ├── Exception.php
    ├── PHPMailer.php
    └── SMTP.php
```

### 3. Configurar App Password

1. Acesse: https://myaccount.google.com/apppasswords
   - Faça login com `contact@senior-floors.com`
2. Crie uma App Password:
   - App: "Outro (nome personalizado)"
   - Nome: "Senior Floors PHPMailer"
3. Copie a senha (16 caracteres, sem espaços)

### 4. Configurar send-lead.php

1. Abra o arquivo `send-lead.php`
2. Encontre a linha 67:
   ```php
   define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE');
   ```
3. Substitua por:
   ```php
   define('SMTP_PASS', 'SUA_APP_PASSWORD_AQUI'); // Cole os 16 caracteres aqui
   ```

### 5. Atualizar JavaScript

1. Abra o arquivo `script.js`
2. Encontre (linha ~199 para hero form e ~270 para contact form):
   ```javascript
   fetch('contact-form-handler.php', {
   ```
3. Substitua por:
   ```javascript
   fetch('send-lead.php', {
   ```
4. Faça isso para **ambos os formulários**

### 6. Testar

1. Preencha o formulário no site
2. Envie
3. Verifique se o email chegou em `leads@senior-floors.com`
4. Verifique `check-email-status.php` para ver os logs

## ✅ Vantagens desta Solução

- ✅ **PHPMailer é muito confiável** - usado por milhões de sites
- ✅ **Funciona sem Composer** - perfeito para Hostinger
- ✅ **Emails HTML formatados** - mais profissional
- ✅ **Logs detalhados** - fácil de debugar
- ✅ **CSV backup** - leads sempre salvos
- ✅ **Compatível com seu código atual** - só mudar o endpoint

## 🔍 Verificar se Funcionou

Após configurar, os logs devem mostrar:
```
✅ Email sent successfully using PHPMailer
   To: leads@senior-floors.com
   From: contact@senior-floors.com
```

## ❌ Troubleshooting

### Erro: "Class 'PHPMailer\PHPMailer\PHPMailer' not found"

**Solução**: Verifique se os arquivos PHPMailer estão na pasta correta:
- `public_html/PHPMailer/PHPMailer.php` deve existir
- Verifique permissões (644 para arquivos)

### Erro: "SMTP password not configured"

**Solução**: Você não atualizou a App Password no arquivo. Substitua `YOUR_APP_PASSWORD_HERE` pela App Password real.

### Erro: "SMTP connect() failed"

**Solução**: 
- Verifique se a App Password está correta
- Verifique se a verificação em duas etapas está ativada
- Tente usar porta 465 com SSL (mude `ENCRYPTION_STARTTLS` para `ENCRYPTION_SMTPS` e porta para `465`)

### Email não chega

**Solução**:
- Verifique pasta de spam
- Verifique logs em `email-status.log`
- Teste enviar email manualmente do Gmail

## 📊 Comparação com Solução Anterior

| Aspecto | SMTP Manual | PHPMailer |
|---------|-------------|-----------|
| Confiabilidade | Média | Alta |
| Facilidade | Difícil | Fácil |
| Debug | Limitado | Excelente |
| Manutenção | Complexa | Simples |

## ✅ Checklist Final

- [ ] PHPMailer baixado e extraído
- [ ] Pasta PHPMailer criada no servidor
- [ ] 3 arquivos PHP uploadados (Exception.php, PHPMailer.php, SMTP.php)
- [ ] App Password criada para contact@senior-floors.com
- [ ] send-lead.php atualizado com App Password
- [ ] script.js atualizado para usar send-lead.php
- [ ] Formulário testado
- [ ] Email recebido em leads@senior-floors.com

---

**Esta solução é muito mais confiável que SMTP manual e deve funcionar perfeitamente!** 🚀
