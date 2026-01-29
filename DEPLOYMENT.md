# 🚀 Deploy Automático para Hostinger via GitHub

Este projeto está configurado para fazer deploy automático para o Hostinger sempre que você fizer push para a branch `main` no GitHub.

> **Deploy das novas funcionalidades (Responsável, Histórico, Pipeline drag-drop):** veja **[DEPLOY_NOVAS_FUNCIONALIDADES.md](DEPLOY_NOVAS_FUNCIONALIDADES.md)** para lista de arquivos e migrations a executar no servidor.

## 📋 Pré-requisitos

1. ✅ Conta no GitHub
2. ✅ Repositório criado no GitHub
3. ✅ Acesso FTP ou SSH do Hostinger
4. ✅ GitHub Actions habilitado no repositório

## 🔧 Configuração Inicial

### Passo 1: Criar Repositório no GitHub

1. Acesse [GitHub](https://github.com/new)
2. Crie um novo repositório:
   - Nome: `senior-floors-system` (ou outro)
   - Visibilidade: **Private** (recomendado)
   - Não inicialize com README (já temos arquivos)

### Passo 2: Conectar Repositório Local ao GitHub

```bash
cd /Users/naka/senior-floors-landing

# Adicionar remote (substitua USERNAME e REPO_NAME)
git remote add origin https://github.com/USERNAME/REPO_NAME.git

# Ou se preferir SSH:
# git remote add origin git@github.com:USERNAME/REPO_NAME.git

# Verificar remote
git remote -v
```

### Passo 3: Fazer Primeiro Push

```bash
# Adicionar todos os arquivos
git add .

# Commit inicial
git commit -m "Initial commit: Senior Floors System"

# Push para GitHub
git push -u origin main
```

### Passo 4: Configurar Secrets no GitHub

1. Acesse seu repositório no GitHub
2. Vá em **Settings** → **Secrets and variables** → **Actions**
3. Clique em **New repository secret**

#### Para FTP (Método 1):

Adicione os seguintes secrets:

- `HOSTINGER_FTP_HOST` - Ex: `ftp.yourdomain.com` ou IP
- `HOSTINGER_FTP_USER` - Seu usuário FTP
- `HOSTINGER_FTP_PASSWORD` - Sua senha FTP

#### Para SSH (Método 2 - Recomendado):

Adicione os seguintes secrets:

- `HOSTINGER_SSH_HOST` - Ex: `ssh.yourdomain.com` ou IP
- `HOSTINGER_SSH_USER` - Seu usuário SSH
- `HOSTINGER_SSH_KEY` - Sua chave SSH privada
- `HOSTINGER_SSH_PORT` - Porta SSH (geralmente 22)
- `HOSTINGER_DOMAIN` - Nome do domínio (para path)

### Passo 5: Escolher Método de Deploy

O projeto tem 2 workflows configurados:

1. **FTP Deploy** (`.github/workflows/deploy-hostinger.yml`)
   - Mais simples
   - Requer apenas credenciais FTP

2. **SSH Deploy** (`.github/workflows/deploy-hostinger-ssh.yml`)
   - Mais seguro
   - Mais rápido
   - Requer chave SSH

**Para usar apenas um método**, delete o arquivo do outro método ou desabilite no GitHub Actions.

## 🔄 Como Funciona

1. Você faz alterações localmente
2. Faz commit: `git add . && git commit -m "Descrição"`
3. Faz push: `git push origin main`
4. GitHub Actions detecta o push
5. Workflow executa automaticamente
6. Arquivos são enviados para Hostinger
7. Deploy completo! ✅

## 📁 Arquivos Excluídos do Deploy

Os seguintes arquivos **NÃO** serão enviados (por segurança):

- `.git/` e `.github/`
- `*.log` (arquivos de log)
- `leads.csv` (dados sensíveis)
- `config/database.php` (credenciais)
- `admin-config.php` (credenciais)
- `PHPMailer/` (se instalado manualmente)
- Arquivos de teste

## 🔍 Verificar Deploy

1. Acesse **Actions** no seu repositório GitHub
2. Veja o workflow executando
3. Clique no workflow para ver logs
4. ✅ Verde = Sucesso
5. ❌ Vermelho = Erro (verifique logs)

## ⚠️ Primeira Vez

Na primeira vez, você precisará:

1. **Fazer upload manual** dos arquivos de configuração:
   - `config/database.php` (com suas credenciais)
   - `admin-config.php` (se usar)
   - `leads.csv` (se já tiver dados)

2. **Configurar permissões** no Hostinger:
   - Arquivos: 644
   - Diretórios: 755
   - `leads.csv`: 666 (para escrita)

## 🛠️ Troubleshooting

### Erro: "FTP connection failed"
- Verifique credenciais FTP
- Verifique se FTP está habilitado no Hostinger
- Tente usar IP ao invés de domínio

### Erro: "SSH connection failed"
- Verifique chave SSH
- Verifique permissões da chave (deve ser 600)
- Verifique se SSH está habilitado no Hostinger

### Arquivos não aparecem no servidor
- Verifique o caminho `server-dir` no workflow
- Verifique permissões de escrita
- Verifique logs do GitHub Actions

### Deploy muito lento
- Use SSH ao invés de FTP
- Exclua mais arquivos no `.gitignore`
- Use `.ftpignore` para excluir arquivos grandes

## 📝 Comandos Úteis

```bash
# Ver status
git status

# Adicionar arquivos
git add .

# Commit
git commit -m "Descrição das mudanças"

# Push (dispara deploy automático)
git push origin main

# Ver histórico
git log --oneline

# Ver remote
git remote -v
```

## 🔐 Segurança

- ✅ Secrets são criptografados no GitHub
- ✅ Arquivos sensíveis não são commitados
- ✅ `.gitignore` protege dados
- ✅ Use repositório **Private** para código proprietário

## 📚 Próximos Passos

Após configurar:

1. ✅ Teste fazendo um pequeno commit
2. ✅ Verifique se o deploy funcionou
3. ✅ Configure notificações (opcional)
4. ✅ Documente credenciais localmente (não no Git!)

---

**Dúvidas?** Verifique os logs do GitHub Actions ou consulte a documentação do Hostinger.
