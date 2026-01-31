# Erros comuns no deploy (GitHub Actions → Hostinger SSH)

## 1. "SSH key file does not look valid" ou "Permission denied (publickey)"

**Causa:** A chave SSH no secret **HOSTINGER_SSH_KEY** está sem quebras de linha ou foi colada errada.

**Solução:**
- No GitHub: **Settings → Secrets and variables → Actions** → edite **HOSTINGER_SSH_KEY**.
- Cole a chave **inteira**, incluindo:
  - `-----BEGIN OPENSSH PRIVATE KEY-----` (ou `-----BEGIN RSA PRIVATE KEY-----`)
  - Todas as linhas do meio
  - `-----END OPENSSH PRIVATE KEY-----` (ou `-----END RSA PRIVATE KEY-----`)
- **Importante:** ao colar no GitHub, preserve as quebras de linha (uma linha por linha).  
  Se colar tudo em uma linha, use `\n` entre cada linha (ex.: `-----BEGIN...-----\nMIIE...\n-----END...-----`).
- O workflow agora converte `\n` em quebra de linha; se ainda falhar, use o formato com uma linha por linha no secret.

---

## 2. "HOSTINGER_SSH_HOST / HOSTINGER_SSH_USER / ... is not set"

**Causa:** Algum secret obrigatório não foi criado.

**Solução:** Em **Settings → Secrets and variables → Actions**, crie:
- **HOSTINGER_SSH_HOST** — ex.: `srv123.hostinger.com` (painel Hostinger → SSH → host)
- **HOSTINGER_SSH_USER** — ex.: `u123456789`
- **HOSTINGER_SSH_KEY** — conteúdo do arquivo da chave privada (.pem ou id_rsa)
- **HOSTINGER_DOMAIN** — ex.: `senior-floors.com` (domínio do site)

Opcional:
- **HOSTINGER_SSH_PORT** — porta SSH (padrão 22)

---

## 3. "SSH connection test failed" (mas os secrets existem)

**Causas possíveis:**
- Host ou usuário errados (conferir no painel Hostinger, seção SSH).
- Chave privada não corresponde à chave pública cadastrada no Hostinger.
- Firewall ou porta SSH bloqueada.

**Solução:** No Hostinger, em **SSH**, confira o host, o usuário e se a chave pública do par que você usa está cadastrada. Teste no seu computador: `ssh -i sua_chave_privada USUARIO@HOST`.

---

## 4. Erro no passo "Deploy via SCP" (path, permission, etc.)

**Causa:** O caminho no servidor pode ser diferente. O workflow usa:
`/home/USUARIO/domains/DOMINIO/public_html/`

No Hostinger o caminho pode variar (ex.: `public_html` na raiz do usuário).

**Solução:** Confira no painel Hostinger o caminho real da pasta do site (FTP ou Gerenciador de Arquivos). Se for outro, precisamos ajustar o workflow para usar um **secret** com o caminho completo (ex.: **HOSTINGER_REMOTE_PATH**).

---

## 5. Deploy termina com sucesso mas o site não atualiza

**Causas:** Cache do navegador, CDN ou arquivos que não foram sobrescritos.

**Solução:** Limpe o cache do navegador (Ctrl+Shift+R). Se usar CDN, invalide o cache. No servidor, confira data/hora dos arquivos em `public_html` para ver se foram atualizados.

---

## Suporte config/smtp.php (e-mail)

O erro **não é** no código do `send-lead.php` que carrega o `config/smtp.php`: esse trecho só lê um arquivo opcional e define constantes. Se o deploy falhar, a mensagem virá em um dos passos do workflow (Validate Secrets, Setup SSH key, Test SSH Connection, Deploy via SCP).  
Para o e-mail funcionar depois do deploy, crie no servidor o arquivo **config/smtp.php** (copiando de **config/smtp.php.example**) e preencha **SMTP_PASS** com a Google App Password.
