# 🔧 Como Corrigir o Erro do Deploy

**Erro encontrado:**
1. ❌ `exclude` não é um parâmetro válido para `appleboy/scp-action`
2. ❌ Erro de conexão SSH: "can't connect without a private SSH key or password"

---

## ✅ Solução Aplicada

O workflow foi corrigido para:
1. ✅ Remover o parâmetro `exclude` (não suportado)
2. ✅ Usar comandos `rm` para limpar arquivos antes do deploy
3. ✅ Usar `scp` diretamente ao invés da ação (mais controle)
4. ✅ Configurar SSH key corretamente

---

## 🔍 Verificar Secrets no GitHub

Certifique-se de que os seguintes Secrets estão configurados:

1. **`HOSTINGER_SSH_HOST`**
   - Exemplo: `ftp.hostinger.com` ou IP do servidor

2. **`HOSTINGER_SSH_USER`**
   - Exemplo: `u123456789`

3. **`HOSTINGER_SSH_KEY`**
   - ⚠️ **IMPORTANTE:** Deve ser a chave SSH **PRIVADA** completa
   - Inclua as linhas `-----BEGIN OPENSSH PRIVATE KEY-----` e `-----END OPENSSH PRIVATE KEY-----`
   - Ou `-----BEGIN RSA PRIVATE KEY-----` e `-----END RSA PRIVATE KEY-----`

4. **`HOSTINGER_SSH_PORT`** (opcional)
   - Padrão: `22`
   - Se não configurado, usa 22

5. **`HOSTINGER_DOMAIN`**
   - Exemplo: `seudominio.com`

---

## 📝 Como Adicionar/Verificar Secrets

1. Acesse: https://github.com/nakazone/senior-floors-system/settings/secrets/actions

2. Para cada secret:
   - Clique em "New repository secret"
   - Digite o nome (ex: `HOSTINGER_SSH_KEY`)
   - Cole o valor
   - Clique em "Add secret"

3. **Para a chave SSH:**
   - A chave deve ser a **PRIVADA** (id_rsa)
   - Copie o conteúdo completo, incluindo as linhas BEGIN/END
   - Se tiver senha, você precisará usar `passphrase` também

---

## 🚀 Testar o Deploy

Após corrigir os Secrets:

1. Acesse: https://github.com/nakazone/senior-floors-system/actions
2. Clique em "Deploy to Hostinger (SSH)"
3. Clique em "Run workflow" → "Run workflow"
4. Veja os logs para verificar se funcionou

---

## 🔑 Como Obter a Chave SSH

### Se você já tem acesso SSH:

```bash
# No seu computador local
cat ~/.ssh/id_rsa
# Copie todo o conteúdo (incluindo BEGIN/END)
```

### Se não tem chave SSH:

1. **Gerar nova chave:**
```bash
ssh-keygen -t rsa -b 4096 -C "seu-email@example.com"
```

2. **Copiar chave pública para o servidor:**
```bash
ssh-copy-id usuario@hostinger.com
```

3. **Copiar chave privada para GitHub Secrets:**
```bash
cat ~/.ssh/id_rsa
# Copie todo o conteúdo para HOSTINGER_SSH_KEY
```

---

## ⚠️ Problemas Comuns

### 1. "can't connect without a private SSH key"
- ✅ Verifique se `HOSTINGER_SSH_KEY` está configurado
- ✅ Certifique-se de que é a chave **PRIVADA** (não pública)
- ✅ Inclua as linhas BEGIN/END

### 2. "Permission denied"
- ✅ Verifique se a chave pública está no servidor
- ✅ Verifique permissões da chave (deve ser 600)

### 3. "Host key verification failed"
- ✅ O workflow agora ignora isso com `StrictHostKeyChecking=no`

### 4. Arquivos não aparecem no servidor
- ✅ Verifique o caminho `target` no workflow
- ✅ Verifique permissões do diretório no servidor

---

## 📋 Checklist

- [ ] Secrets configurados no GitHub
- [ ] `HOSTINGER_SSH_KEY` é a chave privada completa
- [ ] Chave pública está no servidor Hostinger
- [ ] Workflow corrigido (sem `exclude`)
- [ ] Testar deploy manual via "Run workflow"

---

**Última atualização:** 23/01/2025
