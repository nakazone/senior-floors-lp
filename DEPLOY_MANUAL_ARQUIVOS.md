# Deploy manual (quando o GitHub Actions FTP falha)

Se o deploy automático der **Timeout** ou falhar, envie estes arquivos para o Hostinger (FTP ou Gerenciador de Arquivos) em **public_html/** (ou na pasta raiz do site):

## Form enviando direto para o system (banco garantido)

| Arquivo | O que faz |
|---------|-----------|
| **index.html** | Formulários com `action="system.php?api=receive-lead"` |
| **script.js** | Envio via fetch para `system.php?api=receive-lead` |
| **system.php** | API receive-lead com CORS, save no banco e email opcional |
| **config/smtp.php.example** | Exemplo para copiar como `config/smtp.php` (email opcional) |

## Opcional (se ainda não estiverem no servidor)

- **admin-modules/crm.php** (usa sempre MySQL quando configurado)
- **.github/workflows/deploy-hostinger.yml** (FTP simples, sem FTPS)

## Passos

1. No Hostinger: **Gerenciador de Arquivos** ou **FTP** (FileZilla).
2. Navegue até **public_html/** (ou a pasta onde está o site).
3. Envie/sobrescreva os arquivos da tabela acima.
4. Se quiser email pelo system: copie `config/smtp.php.example` para `config/smtp.php` e preencha a senha SMTP (Google App Password).

Depois disso, o formulário da LP envia direto para **system.php?api=receive-lead** e o lead é salvo no banco do CRM.
