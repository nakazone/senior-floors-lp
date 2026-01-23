# 📋 Checklist de Secrets - O que está faltando?

**Data:** 23 de Janeiro de 2025

---

## 🔍 Secrets Necessários

Baseado no workflow `.github/workflows/deploy-hostinger-ssh.yml`, você precisa configurar os seguintes secrets:

---

## ✅ Secrets Obrigatórios

### 1. **HOSTINGER_SSH_HOST** ⚠️ FALTANDO

**Status:** ❌ Não configurado (causa o erro atual)

**O que é:** Hostname ou IP do servidor Hostinger

**Como encontrar:**
- Acesse o painel do Hostinger
- Vá em **FTP** ou **SSH Access**
- Procure por **Host** ou **Server**
- Exemplos:
  - `ftp.hostinger.com`
  - `ssh.hostinger.com`
  - `123.456.789.0` (IP do servidor)

**Como configurar:**
1. Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions
2. Clique em **"New repository secret"**
3. **Name:** `HOSTINGER_SSH_HOST`
4. **Secret:** Cole o hostname ou IP
5. Clique em **"Add secret"**

---

### 2. **HOSTINGER_SSH_USER** ⚠️ FALTANDO

**Status:** ❌ Não configurado

**O que é:** Seu usuário SSH/FTP do Hostinger

**Como encontrar:**
- No painel do Hostinger → **FTP Accounts**
- Procure por **Username**
- Geralmente começa com `u` seguido de números
- Exemplo: `u123456789`

**Como configurar:**
1. Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions
2. Clique em **"New repository secret"**
3. **Name:** `HOSTINGER_SSH_USER`
4. **Secret:** Cole o usuário
5. Clique em **"Add secret"**

---

### 3. **HOSTINGER_SSH_KEY** ⚠️ FALTANDO

**Status:** ❌ Não configurado

**O que é:** Chave SSH **PRIVADA** completa

**Como obter:**
- Veja o guia: `COMO_OBTER_SSH_KEY_HOSTINGER.md`
- Ou execute: `cat ~/.ssh/id_rsa` (no seu computador)
- Copie TODO o conteúdo, incluindo `-----BEGIN` e `-----END`

**Como configurar:**
1. Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions
2. Clique em **"New repository secret"**
3. **Name:** `HOSTINGER_SSH_KEY`
4. **Secret:** Cole a chave privada completa
5. Clique em **"Add secret"**

---

### 4. **HOSTINGER_DOMAIN** ⚠️ FALTANDO

**Status:** ❌ Não configurado

**O que é:** Seu domínio

**Como encontrar:**
- No painel do Hostinger → **Domínios**
- Use o domínio principal (sem `www.`)
- Exemplo: `seudominio.com`

**Como configurar:**
1. Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions
2. Clique em **"New repository secret"**
3. **Name:** `HOSTINGER_DOMAIN`
4. **Secret:** Cole o domínio
5. Clique em **"Add secret"**

---

## 🔧 Secret Opcional

### 5. **HOSTINGER_SSH_PORT** (Opcional)

**Status:** ⚠️ Opcional (usa 22 se não configurar)

**O que é:** Porta SSH

**Valor padrão:** `22`

**Como configurar:**
- Só configure se for diferente de 22
- Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions
- **Name:** `HOSTINGER_SSH_PORT`
- **Secret:** `22` (ou a porta que você usa)

---

## 📊 Resumo

| Secret | Status | Obrigatório | Onde Encontrar |
|--------|--------|-------------|----------------|
| `HOSTINGER_SSH_HOST` | ❌ Faltando | ✅ Sim | Painel Hostinger → FTP/SSH → Host |
| `HOSTINGER_SSH_USER` | ❌ Faltando | ✅ Sim | Painel Hostinger → FTP → Username |
| `HOSTINGER_SSH_KEY` | ❌ Faltando | ✅ Sim | Gerar no computador (ver guia) |
| `HOSTINGER_DOMAIN` | ❌ Faltando | ✅ Sim | Painel Hostinger → Domínios |
| `HOSTINGER_SSH_PORT` | ⚠️ Opcional | ❌ Não | Padrão: 22 |

---

## 🎯 Ação Necessária

**Você precisa configurar 4 secrets obrigatórios:**

1. ✅ `HOSTINGER_SSH_HOST`
2. ✅ `HOSTINGER_SSH_USER`
3. ✅ `HOSTINGER_SSH_KEY`
4. ✅ `HOSTINGER_DOMAIN`

---

## 📝 Como Configurar Todos de Uma Vez

1. **Acesse os Secrets:**
   ```
   https://github.com/nakazone/senior-floors-system/settings/secrets/actions
   ```

2. **Para cada secret:**
   - Clique em **"New repository secret"**
   - Digite o **Name** exatamente como mostrado acima
   - Cole o **Value** (valor)
   - Clique em **"Add secret"**

3. **Repita para todos os 4 secrets obrigatórios**

---

## ✅ Verificar se Está Configurado

Após configurar, teste:

1. Acesse: https://github.com/nakazone/senior-floors-system/actions
2. Clique em **"Deploy to Hostinger (SSH)"**
3. Clique em **"Run workflow"** → **"Run workflow"**
4. Veja os logs:
   - ✅ Se aparecer "✅ All required secrets are configured" = SUCESSO!
   - ❌ Se aparecer "❌ Error: [SECRET] secret is not set" = Ainda falta configurar

---

## 🆘 Precisa de Ajuda?

- **Para SSH Key:** Veja `COMO_OBTER_SSH_KEY_HOSTINGER.md`
- **Para outros secrets:** Veja `COMO_CONFIGURAR_SECRETS_PASSO_A_PASSO.md`

---

**Última atualização:** 23/01/2025
