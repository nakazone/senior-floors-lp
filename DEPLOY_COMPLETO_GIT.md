# Deploy completo pelo Git

Este guia faz o **deploy completo** do projeto para o Hostinger via Git: você faz push na branch `main` e o GitHub Actions envia os arquivos por FTP.

---

## Pré-requisitos (uma vez só)

### 1. Repositório no GitHub

- Repo criado (ex.: `senior-floors-system`)
- Projeto local conectado: `git remote -v` deve mostrar `origin` apontando para o GitHub

### 2. Secrets no GitHub (FTP)

No repositório: **Settings** → **Secrets and variables** → **Actions** → **New repository secret**.

Crie estes secrets:

| Nome | Valor |
|------|--------|
| `HOSTINGER_FTP_HOST` | Host FTP (ex.: `ftp.senior-floors.com` ou o que a Hostinger mostrar) |
| `HOSTINGER_FTP_USER` | Usuário FTP |
| `HOSTINGER_FTP_PASSWORD` | Senha FTP |

Sem esses três, o workflow **Deploy to Hostinger** (FTP) falha.

### 3. Onde os arquivos vão parar

O workflow envia tudo para **`/public_html/`** no servidor FTP. Confirme no painel da Hostinger qual é a raiz do site (geralmente `public_html` do domínio principal).

- Se o painel (senior-floors.com) e a LP (lp.senior-floors.com) estão no **mesmo** Hostinger: o deploy vai para a raiz desse hosting; a LP pode estar em um subdomínio (ex.: `public_html` ou pasta `lp`).
- Se são **dois** hostings diferentes: hoje só há um workflow (um FTP). Seria preciso outro workflow ou outro server-dir para o segundo servidor.

---

## Deploy completo (passo a passo)

### Opção A: Script (recomendado)

No terminal, na pasta do projeto:

```bash
cd /Users/naka/senior-floors-landing
./deploy-now.sh
```

O script faz: `git add .` → `git commit` → `git push origin main`. O push dispara o GitHub Actions (FTP).

### Opção B: Comandos manuais

```bash
cd /Users/naka/senior-floors-landing

# 1. Ver o que mudou
git status

# 2. Adicionar tudo
git add .

# 3. Commit
git commit -m "Deploy completo: LP, send-lead, CORS, form para banco"

# 4. Push (dispara o deploy automático)
git push origin main
```

---

## Depois do push

1. Abra **https://github.com/SEU_USUARIO/SEU_REPO/actions**
2. Clique no workflow **"Deploy to Hostinger"** que estiver rodando (ou na última execução).
3. Veja os logs:
   - **Verde** = deploy concluído; arquivos em `/public_html/`.
   - **Vermelho** = erro (credenciais FTP, host, etc.).

---

## O que NÃO vai no deploy (por segurança)

O workflow **não** envia (conforme `.gitignore` e `exclude` do workflow):

- `config/database.php`
- `admin-config.php`
- `config/smtp.php`
- `config/system-api-url.php`
- `*.log`, `leads.csv`
- `PHPMailer/`
- `.git`, `.github`, `node_modules`

Esses arquivos precisam existir **no servidor** (criados/ajustados manualmente ou em outro deploy).

---

## Resumo rápido

| Ação | Comando |
|------|--------|
| Deploy completo | `./deploy-now.sh` ou `git add . && git commit -m "Deploy" && git push origin main` |
| Ver status | `git status` |
| Ver remote | `git remote -v` |
| Ver Actions | GitHub → Aba **Actions** |

Se o workflow FTP falhar, confira os três secrets (FTP host, user, password) e o caminho do site no Hostinger (`/public_html/`).
