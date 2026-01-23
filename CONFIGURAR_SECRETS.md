# 🔐 Como Configurar os Secrets do GitHub Actions

**Erro encontrado:** `Could not resolve hostname` - Secrets não configurados

---

## ⚠️ Problema

O erro indica que os **Secrets** não estão configurados no GitHub. Você precisa configurar os seguintes secrets para o deploy funcionar.

---

## ✅ Solução: Configurar Secrets

### 1. Acesse os Secrets do GitHub:

**URL:** https://github.com/nakazone/senior-floors-system/settings/secrets/actions

Ou:
1. Acesse: https://github.com/nakazone/senior-floors-system
2. Clique em **Settings** (Configurações)
3. No menu lateral, clique em **Secrets and variables** → **Actions**
4. Clique em **New repository secret**

---

## 📋 Secrets Necessários

Configure os seguintes secrets:

### 1. **HOSTINGER_SSH_HOST**
- **O que é:** Hostname ou IP do servidor Hostinger
- **Exemplos:**
  - `ftp.hostinger.com`
  - `ssh.hostinger.com`
  - `123.456.789.0` (IP do servidor)
- **Como encontrar:**
  - No painel do Hostinger → FTP → Host
  - Ou no painel → SSH → Host

### 2. **HOSTINGER_SSH_USER**
- **O que é:** Usuário SSH/FTP do Hostinger
- **Exemplo:** `u123456789`
- **Como encontrar:**
  - No painel do Hostinger → FTP → Username
  - Geralmente começa com `u` seguido de números

### 3. **HOSTINGER_SSH_KEY**
- **O que é:** Chave SSH **PRIVADA** completa
- **Formato:**
  ```
  -----BEGIN OPENSSH PRIVATE KEY-----
  b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn
  ...
  -----END OPENSSH PRIVATE KEY-----
  ```
- **Como obter:**
  - Se você já tem chave SSH local:
    ```bash
    cat ~/.ssh/id_rsa
    # Copie TODO o conteúdo
    ```
  - Se não tem, gere uma nova:
    ```bash
    ssh-keygen -t rsa -b 4096 -C "seu-email@example.com"
    cat ~/.ssh/id_rsa
    ```

### 4. **HOSTINGER_SSH_PORT** (Opcional)
- **O que é:** Porta SSH
- **Padrão:** `22`
- **Se não configurar:** Usa 22 automaticamente

### 5. **HOSTINGER_DOMAIN**
- **O que é:** Domínio do seu site
- **Exemplo:** `seudominio.com`
- **Como encontrar:**
  - No painel do Hostinger → Domínios

---

## 🔑 Passo a Passo para Configurar

### Passo 1: Gerar Chave SSH (se não tiver)

```bash
# No seu computador
ssh-keygen -t rsa -b 4096 -C "seu-email@example.com"
# Pressione Enter para aceitar o local padrão
# Digite uma senha (ou deixe em branco)
```

### Passo 2: Copiar Chave Pública para o Servidor

```bash
# Copiar chave pública para o Hostinger
ssh-copy-id -p 22 usuario@hostinger.com

# Ou manualmente:
cat ~/.ssh/id_rsa.pub
# Cole o conteúdo no painel do Hostinger → SSH → Authorized Keys
```

### Passo 3: Copiar Chave Privada

```bash
# Copiar chave PRIVADA (não a pública!)
cat ~/.ssh/id_rsa
# Copie TODO o conteúdo, incluindo BEGIN e END
```

### Passo 4: Adicionar Secrets no GitHub

1. Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions

2. Para cada secret:
   - Clique em **New repository secret**
   - **Name:** Digite o nome exato (ex: `HOSTINGER_SSH_HOST`)
   - **Secret:** Cole o valor
   - Clique em **Add secret**

3. Repita para todos os secrets:
   - `HOSTINGER_SSH_HOST`
   - `HOSTINGER_SSH_USER`
   - `HOSTINGER_SSH_KEY` (chave privada completa)
   - `HOSTINGER_DOMAIN`
   - `HOSTINGER_SSH_PORT` (opcional, padrão 22)

---

## ✅ Verificar Configuração

Após configurar os secrets, o workflow agora vai:
1. ✅ Validar se todos os secrets estão configurados
2. ✅ Testar a conexão SSH antes do deploy
3. ✅ Mostrar mensagens de erro claras se algo estiver faltando

---

## 🧪 Testar o Deploy

1. Acesse: https://github.com/nakazone/senior-floors-system/actions
2. Clique em **Deploy to Hostinger (SSH)**
3. Clique em **Run workflow** → **Run workflow**
4. Veja os logs:
   - Se aparecer "✅ All required secrets are configured" = OK
   - Se aparecer "❌ Error" = Verifique qual secret está faltando

---

## ⚠️ Problemas Comuns

### 1. "Could not resolve hostname"
- ✅ Verifique se `HOSTINGER_SSH_HOST` está configurado
- ✅ Verifique se o hostname está correto (sem espaços, sem http://)

### 2. "Permission denied"
- ✅ Verifique se a chave pública está no servidor
- ✅ Verifique se `HOSTINGER_SSH_KEY` é a chave **PRIVADA** (não pública)

### 3. "Connection refused"
- ✅ Verifique se `HOSTINGER_SSH_PORT` está correto
- ✅ Verifique se o SSH está habilitado no Hostinger

### 4. "No such file or directory"
- ✅ Verifique se `HOSTINGER_DOMAIN` está correto
- ✅ Verifique se o caminho do domínio existe no servidor

---

## 📝 Checklist

- [ ] `HOSTINGER_SSH_HOST` configurado
- [ ] `HOSTINGER_SSH_USER` configurado
- [ ] `HOSTINGER_SSH_KEY` configurado (chave privada completa)
- [ ] `HOSTINGER_DOMAIN` configurado
- [ ] `HOSTINGER_SSH_PORT` configurado (ou deixar padrão 22)
- [ ] Chave pública está no servidor Hostinger
- [ ] Testar deploy manual via "Run workflow"

---

**Última atualização:** 23/01/2025
