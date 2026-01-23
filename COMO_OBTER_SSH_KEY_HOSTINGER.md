# 🔑 Como Obter/Configurar SSH Key no Hostinger - Passo a Passo

**Guia completo para configurar SSH no Hostinger e obter a chave para GitHub Actions**

---

## 📋 Opções Disponíveis

Você tem **2 opções**:

1. **Opção A:** Gerar chave SSH no seu computador e adicionar no Hostinger (RECOMENDADO)
2. **Opção B:** Usar chave SSH gerada pelo próprio Hostinger (se disponível)

---

## ✅ OPÇÃO A: Gerar Chave SSH no Seu Computador (RECOMENDADO)

Esta é a melhor opção porque você terá controle total sobre a chave.

### Passo 1: Gerar Chave SSH no Seu Computador

1. **Abra o Terminal** (Mac) ou Prompt de Comando/PowerShell (Windows)

2. **Execute o comando:**
```bash
ssh-keygen -t rsa -b 4096 -C "seu-email@example.com"
```

3. **Quando perguntar onde salvar:**
   - Pressione **Enter** para aceitar o local padrão (`~/.ssh/id_rsa`)

4. **Quando perguntar por senha:**
   - Você pode deixar em branco (pressione Enter) OU
   - Digite uma senha forte (mais seguro)

5. **Confirme a senha** (se tiver digitado uma)

**Resultado:** Duas chaves serão criadas:
- `~/.ssh/id_rsa` → **Chave PRIVADA** (não compartilhe!)
- `~/.ssh/id_rsa.pub` → **Chave PÚBLICA** (pode compartilhar)

---

### Passo 2: Copiar Chave Pública para o Hostinger

#### Método 1: Via Painel do Hostinger (Mais Fácil)

1. **Acesse o painel do Hostinger:**
   - https://hpanel.hostinger.com
   - Faça login

2. **Vá em SSH Access:**
   - No menu lateral, procure por **"SSH Access"** ou **"SSH"**
   - Ou vá em **"Advanced"** → **"SSH Access"**

3. **Copie sua chave pública:**
   - No seu computador, execute:
   ```bash
   cat ~/.ssh/id_rsa.pub
   ```
   - Copie **TODO** o conteúdo (começa com `ssh-rsa` ou `ssh-ed25519`)

4. **Cole no Hostinger:**
   - No painel do Hostinger → SSH Access
   - Clique em **"Add SSH Key"** ou **"Manage SSH Keys"**
   - Cole a chave pública no campo
   - Dê um nome (ex: "Meu Computador" ou "GitHub Actions")
   - Clique em **"Add"** ou **"Save"**

#### Método 2: Via Terminal (Avançado)

```bash
# Copiar chave pública para o servidor
ssh-copy-id -p 22 usuario@hostinger.com

# Ou manualmente:
cat ~/.ssh/id_rsa.pub | ssh usuario@hostinger.com "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

---

### Passo 3: Copiar Chave PRIVADA para GitHub Secrets

⚠️ **IMPORTANTE:** Esta é a chave **PRIVADA**, nunca compartilhe!

1. **No seu computador, execute:**
```bash
cat ~/.ssh/id_rsa
```

2. **Copie TODO o conteúdo**, incluindo:
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn
NhAAAAAwEAAQAAAYEAy...
(muito mais texto aqui)
...
-----END OPENSSH PRIVATE KEY-----
```

3. **Cole no GitHub Secret:**
   - Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions
   - Clique em **"New repository secret"**
   - **Name:** `HOSTINGER_SSH_KEY`
   - **Secret:** Cole a chave privada completa
   - Clique em **"Add secret"**

---

## 🔄 OPÇÃO B: Usar Chave SSH do Hostinger (Se Disponível)

Alguns planos do Hostinger permitem gerar chaves SSH diretamente no painel.

### Passo 1: Gerar Chave no Hostinger

1. **Acesse o painel do Hostinger:**
   - https://hpanel.hostinger.com

2. **Vá em SSH Access:**
   - Menu lateral → **"SSH Access"** ou **"Advanced"** → **"SSH Access"**

3. **Gere nova chave:**
   - Clique em **"Generate SSH Key"** ou **"Create SSH Key"**
   - Dê um nome (ex: "GitHub Actions")
   - Clique em **"Generate"**

4. **Baixe a chave privada:**
   - O Hostinger mostrará a chave privada
   - **IMPORTANTE:** Baixe e salve em local seguro
   - Você só verá esta chave uma vez!

### Passo 2: Adicionar Chave Privada no GitHub

1. **Copie a chave privada** que você baixou do Hostinger

2. **Cole no GitHub Secret:**
   - Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions
   - Clique em **"New repository secret"**
   - **Name:** `HOSTINGER_SSH_KEY`
   - **Secret:** Cole a chave privada completa
   - Clique em **"Add secret"**

---

## ✅ Verificar se Funcionou

### Teste 1: Testar Conexão SSH

No seu computador, execute:
```bash
ssh -p 22 usuario@hostinger.com
```

Se conectar sem pedir senha = ✅ Funcionou!

### Teste 2: Testar Deploy no GitHub

1. Acesse: https://github.com/nakazone/senior-floors-system/actions
2. Clique em **"Deploy to Hostinger (SSH)"**
3. Clique em **"Run workflow"** → **"Run workflow"**
4. Veja os logs:
   - ✅ "SSH connection successful" = Funcionou!
   - ❌ "Permission denied" = Verifique se a chave pública está no servidor

---

## 🔍 Encontrar Informações SSH no Hostinger

### Como Encontrar o Host SSH:

1. **Acesse o painel do Hostinger**
2. **Vá em FTP Accounts** ou **SSH Access**
3. **Procure por:**
   - **Host:** `ftp.hostinger.com` ou `ssh.hostinger.com`
   - **Port:** `22` (geralmente)
   - **Username:** `u123456789` (seu usuário)

### Como Encontrar o Usuário:

1. **Painel Hostinger** → **FTP Accounts**
2. **Username** geralmente começa com `u` seguido de números
3. Exemplo: `u123456789`

---

## ⚠️ Problemas Comuns

### "Permission denied (publickey)"
- ✅ Verifique se a chave **pública** está no Hostinger
- ✅ Verifique se a chave **privada** está no GitHub Secret
- ✅ Verifique se o usuário está correto

### "Could not resolve hostname"
- ✅ Verifique se `HOSTINGER_SSH_HOST` está correto
- ✅ Sem espaços antes/depois
- ✅ Sem `http://` ou `https://`

### "Connection refused"
- ✅ Verifique se o SSH está habilitado no seu plano Hostinger
- ✅ Alguns planos precisam habilitar SSH manualmente
- ✅ Contate o suporte do Hostinger se necessário

---

## 📝 Checklist Final

- [ ] Chave SSH gerada (pública e privada)
- [ ] Chave pública adicionada no Hostinger
- [ ] Chave privada adicionada no GitHub Secret `HOSTINGER_SSH_KEY`
- [ ] `HOSTINGER_SSH_HOST` configurado no GitHub
- [ ] `HOSTINGER_SSH_USER` configurado no GitHub
- [ ] `HOSTINGER_DOMAIN` configurado no GitHub
- [ ] Teste de conexão SSH funcionando
- [ ] Deploy no GitHub Actions funcionando

---

## 🆘 Precisa de Ajuda?

Se não conseguir:

1. **Verifique se seu plano Hostinger suporta SSH:**
   - Acesse o painel → SSH Access
   - Se não aparecer, pode não estar disponível no seu plano

2. **Contate o suporte do Hostinger:**
   - Eles podem ajudar a habilitar SSH
   - Eles podem fornecer as informações de host/usuário

3. **Use FTP como alternativa:**
   - Se SSH não funcionar, podemos configurar deploy via FTP

---

**Última atualização:** 23/01/2025
