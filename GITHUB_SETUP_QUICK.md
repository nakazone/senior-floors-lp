# 🚀 Setup Rápido - GitHub + Deploy Automático

## ⚡ Passos Rápidos (5 minutos)

### 1. Criar Repositório no GitHub

1. Acesse: https://github.com/new
2. Nome: `senior-floors-system`
3. Visibilidade: **Private** ✅
4. **NÃO** marque "Add README" (já temos)
5. Clique **Create repository**

### 2. Conectar ao GitHub

```bash
cd /Users/naka/senior-floors-landing

# Adicionar remote (substitua SEU_USUARIO)
git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git

# Verificar
git remote -v
```

### 3. Primeiro Push

```bash
# Adicionar tudo
git add .

# Commit
git commit -m "Initial commit: Senior Floors System"

# Push
git push -u origin main
```

### 4. Configurar Secrets (GitHub)

1. No GitHub: **Settings** → **Secrets and variables** → **Actions**
2. Clique **New repository secret**

#### Opção A: FTP (Mais Simples)

Adicione 3 secrets:
- `HOSTINGER_FTP_HOST` = `ftp.seudominio.com` (ou IP)
- `HOSTINGER_FTP_USER` = seu usuário FTP
- `HOSTINGER_FTP_PASSWORD` = sua senha FTP

#### Opção B: SSH (Mais Seguro)

Adicione 5 secrets:
- `HOSTINGER_SSH_HOST` = `ssh.seudominio.com`
- `HOSTINGER_SSH_USER` = seu usuário SSH
- `HOSTINGER_SSH_KEY` = sua chave SSH privada
- `HOSTINGER_SSH_PORT` = `22`
- `HOSTINGER_DOMAIN` = `seudominio.com`

### 5. Escolher Método de Deploy

**Para usar FTP:**
- Delete: `.github/workflows/deploy-hostinger-ssh.yml`

**Para usar SSH:**
- Delete: `.github/workflows/deploy-hostinger.yml`

### 6. Testar Deploy

```bash
# Fazer uma pequena mudança
echo "# Test" >> README.md

# Commit e push
git add README.md
git commit -m "Test deploy"
git push origin main
```

### 7. Verificar

1. GitHub → **Actions** tab
2. Veja o workflow rodando
3. ✅ Verde = Sucesso!

## 📝 Próximos Commits

Agora é só:

```bash
git add .
git commit -m "Descrição das mudanças"
git push origin main
```

**Deploy automático acontece!** 🎉

## ⚠️ Importante

Arquivos que **NÃO** vão para o servidor (por segurança):
- `config/database.php` (configure manualmente)
- `admin-config.php` (configure manualmente)
- `*.log` (arquivos de log)
- `leads.csv` (dados)

## 🔍 Onde Encontrar Credenciais FTP/SSH?

**Hostinger cPanel:**
- FTP: **FTP Accounts** → Ver credenciais
- SSH: **SSH Access** → Ver informações

## ❓ Problemas?

Veja `DEPLOYMENT.md` para troubleshooting completo.

---

**Pronto!** Agora todo push para `main` faz deploy automático! 🚀
