# 🔧 Como Corrigir Erro "Could not resolve hostname"

**Erro:** `ssh: Could not resolve hostname ***: Name or service not known`

---

## 🔍 Problema Identificado

O erro indica que o secret `HOSTINGER_SSH_HOST` está:
- ❌ Vazio
- ❌ Com valor incorreto
- ❌ Com espaços ou caracteres inválidos
- ❌ Com formato errado (ex: `http://` ou `https://`)

---

## ✅ Solução: Verificar e Corrigir o Secret

### Passo 1: Verificar o Secret Atual

1. **Acesse os Secrets:**
   ```
   https://github.com/nakazone/senior-floors-system/settings/secrets/actions
   ```

2. **Procure por `HOSTINGER_SSH_HOST`**
   - Se não existir = precisa criar
   - Se existir = verifique o valor

---

### Passo 2: Encontrar o Hostname Correto no Hostinger

#### Opção A: Via Painel do Hostinger

1. **Acesse o painel:**
   - https://hpanel.hostinger.com
   - Faça login

2. **Vá em FTP Accounts:**
   - Menu lateral → **"FTP Accounts"** ou **"FTP"**
   - Procure por **"Host"** ou **"Server"**

3. **Copie o hostname:**
   - Exemplos válidos:
     - `ftp.hostinger.com`
     - `ssh.hostinger.com`
     - `ftp.yourdomain.com`
     - `123.456.789.0` (IP do servidor)

#### Opção B: Via SSH Access

1. **No painel Hostinger:**
   - Menu lateral → **"SSH Access"** ou **"Advanced"** → **"SSH Access"**

2. **Procure por:**
   - **Host:** ou **Server:**
   - Copie o valor exato

#### Opção C: Via Email do Hostinger

- Verifique emails do Hostinger com informações de FTP/SSH
- Geralmente contém o hostname do servidor

---

### Passo 3: Formato Correto do Hostname

**✅ Formato Válido:**
```
ftp.hostinger.com
```

**❌ Formatos Inválidos:**
```
http://ftp.hostinger.com          ❌ (não use http://)
https://ftp.hostinger.com         ❌ (não use https://)
ftp://ftp.hostinger.com           ❌ (não use ftp://)
ftp.hostinger.com/                ❌ (sem barra no final)
 ftp.hostinger.com                ❌ (sem espaços)
ftp.hostinger.com:22              ❌ (sem porta, use HOSTINGER_SSH_PORT)
```

---

### Passo 4: Atualizar o Secret

#### Se o Secret Já Existe:

1. **Acesse:**
   ```
   https://github.com/nakazone/senior-floors-system/settings/secrets/actions
   ```

2. **Clique em `HOSTINGER_SSH_HOST`**

3. **Clique em "Update"** (ou ícone de lápis)

4. **Cole o hostname correto:**
   - Sem espaços antes/depois
   - Sem `http://` ou `https://`
   - Apenas o hostname ou IP

5. **Clique em "Update secret"**

#### Se o Secret Não Existe:

1. **Acesse:**
   ```
   https://github.com/nakazone/senior-floors-system/settings/secrets/actions
   ```

2. **Clique em "New repository secret"**

3. **Name:** `HOSTINGER_SSH_HOST`

4. **Secret:** Cole o hostname correto

5. **Clique em "Add secret"**

---

## 🧪 Testar Após Corrigir

1. **Acesse:**
   ```
   https://github.com/nakazone/senior-floors-system/actions
   ```

2. **Clique em "Deploy to Hostinger (SSH)"**

3. **Clique em "Run workflow"** → **"Run workflow"**

4. **Veja os logs:**
   - ✅ Se aparecer "SSH connection successful" = FUNCIONOU!
   - ❌ Se ainda der erro = Verifique outros secrets

---

## 🔍 Verificar Outros Secrets

Se o hostname estiver correto mas ainda der erro, verifique:

### 1. HOSTINGER_SSH_USER

**Formato correto:**
```
u123456789
```

**Erros comuns:**
- Espaços antes/depois
- Caracteres especiais incorretos

### 2. HOSTINGER_SSH_KEY

**Deve ser:**
- Chave privada completa
- Com linhas `-----BEGIN` e `-----END`
- Sem espaços extras no início/fim

### 3. HOSTINGER_DOMAIN

**Formato correto:**
```
seudominio.com
```

**Erros comuns:**
- `www.seudominio.com` (sem www)
- `http://seudominio.com` (sem http://)
- Espaços

---

## 📋 Checklist de Verificação

- [ ] `HOSTINGER_SSH_HOST` configurado
- [ ] Hostname sem `http://` ou `https://`
- [ ] Hostname sem espaços
- [ ] Hostname sem porta (use `HOSTINGER_SSH_PORT` separado)
- [ ] `HOSTINGER_SSH_USER` configurado
- [ ] `HOSTINGER_SSH_KEY` configurado (chave privada completa)
- [ ] `HOSTINGER_DOMAIN` configurado
- [ ] Testar deploy novamente

---

## 🆘 Se Ainda Não Funcionar

### Verificar no Hostinger:

1. **SSH está habilitado?**
   - Alguns planos precisam habilitar SSH manualmente
   - Contate suporte do Hostinger se necessário

2. **Hostname está correto?**
   - Tente usar o IP do servidor ao invés do hostname
   - Exemplo: `123.456.789.0`

3. **Porta está correta?**
   - Verifique se não é porta diferente de 22
   - Configure `HOSTINGER_SSH_PORT` se necessário

---

## 💡 Dica: Usar IP ao Invés de Hostname

Se o hostname não resolver, você pode usar o IP do servidor:

1. **Encontre o IP:**
   - No painel Hostinger → **FTP** → pode mostrar o IP
   - Ou use ferramentas online: `ping ftp.hostinger.com`

2. **Use o IP no secret:**
   - `HOSTINGER_SSH_HOST` = `123.456.789.0` (IP do servidor)

---

**Última atualização:** 23/01/2025
