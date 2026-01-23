# 🔐 Como Configurar Secrets - Passo a Passo

**Erro:** `HOSTINGER_SSH_HOST secret is not set`

---

## 🎯 Solução Rápida

Você precisa configurar os **Secrets** no GitHub. Siga estes passos:

---

## 📍 Passo 1: Acessar Secrets do GitHub

1. **Acesse este link:**
   ```
   https://github.com/nakazone/senior-floors-system/settings/secrets/actions
   ```

2. Ou navegue manualmente:
   - Acesse: https://github.com/nakazone/senior-floors-system
   - Clique em **Settings** (no topo do repositório)
   - No menu lateral esquerdo, clique em **Secrets and variables**
   - Clique em **Actions**

---

## 📝 Passo 2: Adicionar Cada Secret

Para cada secret abaixo, faça:

1. Clique no botão **"New repository secret"** (canto superior direito)
2. Digite o **Name** exatamente como mostrado
3. Cole o **Value** (valor)
4. Clique em **"Add secret"**

---

## 🔑 Secrets que Você Precisa Configurar

### 1. **HOSTINGER_SSH_HOST**

**Name:** `HOSTINGER_SSH_HOST`

**Value:** O hostname ou IP do seu servidor Hostinger

**Como encontrar:**
- Acesse o painel do Hostinger
- Vá em **FTP** ou **SSH**
- Procure por **Host** ou **Server**
- Exemplos:
  - `ftp.hostinger.com`
  - `ssh.hostinger.com`
  - `123.456.789.0` (IP do servidor)

**⚠️ IMPORTANTE:** Sem `http://` ou `https://`, apenas o hostname ou IP

---

### 2. **HOSTINGER_SSH_USER**

**Name:** `HOSTINGER_SSH_USER`

**Value:** Seu usuário FTP/SSH

**Como encontrar:**
- No painel do Hostinger → **FTP** → **Username**
- Geralmente começa com `u` seguido de números
- Exemplo: `u123456789`

---

### 3. **HOSTINGER_SSH_KEY**

**Name:** `HOSTINGER_SSH_KEY`

**Value:** Sua chave SSH **PRIVADA** completa

**Como obter:**

#### Opção A: Se você já tem chave SSH

No seu computador, execute:
```bash
cat ~/.ssh/id_rsa
```

Copie **TODO** o conteúdo, incluindo:
```
-----BEGIN OPENSSH PRIVATE KEY-----
...
(muito texto aqui)
...
-----END OPENSSH PRIVATE KEY-----
```

#### Opção B: Se você não tem chave SSH

1. **Gere uma nova chave:**
```bash
ssh-keygen -t rsa -b 4096 -C "seu-email@example.com"
# Pressione Enter para aceitar o local padrão
# Digite uma senha (ou deixe em branco)
```

2. **Copie a chave privada:**
```bash
cat ~/.ssh/id_rsa
# Copie TODO o conteúdo
```

3. **Copie a chave pública para o servidor:**
```bash
ssh-copy-id -p 22 usuario@hostinger.com
```

Ou manualmente:
```bash
cat ~/.ssh/id_rsa.pub
# Cole no painel do Hostinger → SSH → Authorized Keys
```

---

### 4. **HOSTINGER_DOMAIN**

**Name:** `HOSTINGER_DOMAIN`

**Value:** Seu domínio

**Exemplo:** `seudominio.com`

**Como encontrar:**
- No painel do Hostinger → **Domínios**
- Use o domínio principal (sem `www.`)

---

### 5. **HOSTINGER_SSH_PORT** (Opcional)

**Name:** `HOSTINGER_SSH_PORT`

**Value:** `22`

**Nota:** Se não configurar, usa 22 automaticamente. Só configure se for diferente.

---

## ✅ Checklist

Após configurar, verifique:

- [ ] `HOSTINGER_SSH_HOST` configurado
- [ ] `HOSTINGER_SSH_USER` configurado
- [ ] `HOSTINGER_SSH_KEY` configurado (chave privada completa)
- [ ] `HOSTINGER_DOMAIN` configurado
- [ ] Chave pública está no servidor Hostinger

---

## 🧪 Testar o Deploy

Após configurar todos os secrets:

1. Acesse: https://github.com/nakazone/senior-floors-system/actions
2. Clique em **"Deploy to Hostinger (SSH)"**
3. Clique em **"Run workflow"** (botão no canto superior direito)
4. Clique em **"Run workflow"** novamente no popup
5. Veja os logs:
   - ✅ Se aparecer "✅ All required secrets are configured" = SUCESSO
   - ❌ Se aparecer "❌ Error" = Verifique qual secret está faltando

---

## ⚠️ Problemas Comuns

### "Could not resolve hostname"
- ✅ Verifique se `HOSTINGER_SSH_HOST` está correto
- ✅ Sem espaços antes/depois
- ✅ Sem `http://` ou `https://`

### "Permission denied"
- ✅ Verifique se a chave **pública** está no servidor
- ✅ Verifique se `HOSTINGER_SSH_KEY` é a chave **PRIVADA** (não pública)

### "Connection refused"
- ✅ Verifique se o SSH está habilitado no Hostinger
- ✅ Verifique se a porta está correta (geralmente 22)

---

## 📞 Precisa de Ajuda?

Se não conseguir encontrar as informações:

1. **Acesse o painel do Hostinger**
2. **Procure por:**
   - FTP → Host, Username
   - SSH → Host, Port
   - Domínios → Seu domínio

3. **Para a chave SSH:**
   - Se não tem, gere uma nova (instruções acima)
   - Se já tem, copie do seu computador

---

**Última atualização:** 23/01/2025
