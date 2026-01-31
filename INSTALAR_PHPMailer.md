# Instalar PHPMailer (e-mail no send-lead.php)

Quando aparece **phpmailer_not_installed**, o send-lead.php não encontra a pasta **PHPMailer** com os 3 arquivos. Siga estes passos no servidor (Hostinger).

---

## 1. Onde colocar a pasta PHPMailer

A pasta **PHPMailer** deve ficar **na mesma pasta** onde está o **send-lead.php**:

- Se `send-lead.php` está em **public_html/** → crie **public_html/PHPMailer/**
- Se `send-lead.php` está em **public_html/lp/** → crie **public_html/lp/PHPMailer/**

---

## 2. Baixar os arquivos

1. Acesse: **https://github.com/PHPMailer/PHPMailer**
2. Clique em **Code** → **Download ZIP**
3. Extraia o ZIP no seu computador
4. Entre na pasta extraída e vá em **src/** (ou **src/PHPMailer/**)
5. Você precisa destes **3 arquivos**:
   - **Exception.php**
   - **PHPMailer.php**
   - **SMTP.php**

---

## 3. Enviar para o servidor

No **Gerenciador de Arquivos** ou **FTP** do Hostinger:

1. Crie a pasta **PHPMailer** na mesma pasta do send-lead.php (ver passo 1).
2. Envie os 3 arquivos para dentro de **PHPMailer/**:
   - `PHPMailer/Exception.php`
   - `PHPMailer/PHPMailer.php`
   - `PHPMailer/SMTP.php`

Estrutura final (exemplo com send-lead em public_html/):

```
public_html/
├── send-lead.php
├── index.html
└── PHPMailer/
    ├── Exception.php
    ├── PHPMailer.php
    └── SMTP.php
```

Se no GitHub a pasta for **src/PHPMailer/** (e não só src/), copie o **conteúdo** dessa pasta (os 3 arquivos) para a pasta PHPMailer no servidor.

---

## 4. Configurar a senha SMTP (Google App Password)

1. Abra **send-lead.php** no servidor.
2. Procure a linha:
   ```php
   define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE');
   ```
3. Troque por sua **App Password** do Gmail (conta usada em SMTP_USER, ex.: contact@senior-floors.com).
4. Para criar a App Password: **Google Account** → **Segurança** → **Verificação em 2 etapas** (ativar) → **Senhas de app** → gerar uma para "Mail".

---

## 5. Testar

Envie um lead de teste pelo formulário ou por **form-test-lp.html**. A resposta deve mostrar **email_sent: true**.

---

**Resumo:** Crie a pasta **PHPMailer** ao lado do **send-lead.php**, coloque os 3 arquivos dentro e configure **SMTP_PASS** no send-lead.php.
