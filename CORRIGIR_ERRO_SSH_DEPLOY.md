# 🔧 Corrigir Erro SSH no Deploy - GitHub Actions

## ❌ Erro Encontrado

```
Run mkdir -p ~/.ssh
Error: Process completed with exit code 1.
```

## ✅ Correções Aplicadas

O workflow foi corrigido com as seguintes melhorias:

1. ✅ **Validação de Secrets** - Verifica se todos os secrets estão configurados antes de executar
2. ✅ **Melhor tratamento de erros** - Adiciona verificações e mensagens claras
3. ✅ **Teste de conexão SSH** - Testa a conexão antes de fazer o deploy
4. ✅ **Permissões corretas** - Garante que o diretório `.ssh` tenha permissões 700

---

## 🔍 Verificar Secrets no GitHub

### Passo 1: Acessar Secrets

1. Acesse: https://github.com/nakazone/senior-floors-system
2. Vá em **Settings** → **Secrets and variables** → **Actions**
3. Verifique se os seguintes secrets estão configurados:

### Secrets Necessários:

- ✅ `HOSTINGER_SSH_HOST` - Host SSH (ex: `ssh.yourdomain.com` ou IP)
- ✅ `HOSTINGER_SSH_USER` - Usuário SSH
- ✅ `HOSTINGER_SSH_KEY` - Chave SSH privada (conteúdo completo)
- ✅ `HOSTINGER_SSH_PORT` - Porta SSH (opcional, padrão: 22)
- ✅ `HOSTINGER_DOMAIN` - Nome do domínio (ex: `yourdomain.com`)

### Passo 2: Verificar Cada Secret

#### HOSTINGER_SSH_HOST
- Deve ser o hostname ou IP do servidor SSH
- Exemplo: `ssh.yourdomain.com` ou `123.456.789.0`

#### HOSTINGER_SSH_USER
- Deve ser o usuário SSH do Hostinger
- Geralmente é o mesmo usuário do FTP
- Exemplo: `u123456789`

#### HOSTINGER_SSH_KEY
- Deve ser a chave SSH privada completa
- Formato: Começa com `-----BEGIN OPENSSH PRIVATE KEY-----` ou `-----BEGIN RSA PRIVATE KEY-----`
- Termina com `-----END OPENSSH PRIVATE KEY-----` ou `-----END RSA PRIVATE KEY-----`
- ⚠️ **IMPORTANTE:** Inclua as linhas de início e fim!

#### HOSTINGER_SSH_PORT
- Porta SSH (geralmente 22)
- Se não configurado, usa 22 por padrão

#### HOSTINGER_DOMAIN
- Nome do domínio sem `http://` ou `https://`
- Exemplo: `senior-floors.com`

---

## 🧪 Testar Secrets

### Opção 1: Executar Workflow Manualmente

1. Acesse: https://github.com/nakazone/senior-floors-system/actions
2. Clique em **"Deploy to Hostinger (SSH) - Fixed"**
3. Clique em **"Run workflow"**
4. Selecione branch `main`
5. Clique em **"Run workflow"**
6. Veja os logs para identificar erros

### Opção 2: Verificar Logs do Último Deploy

1. Acesse: https://github.com/nakazone/senior-floors-system/actions
2. Clique no último workflow executado
3. Veja os logs de cada step
4. Procure por mensagens de erro específicas

---

## 🔑 Como Obter Chave SSH do Hostinger

### Método 1: Gerar Nova Chave SSH

1. **No seu computador local**, execute:
   ```bash
   ssh-keygen -t rsa -b 4096 -C "your_email@example.com"
   ```

2. **Pressione Enter** para aceitar o local padrão (`~/.ssh/id_rsa`)

3. **Digite uma senha** (ou deixe em branco)

4. **Copie a chave pública:**
   ```bash
   cat ~/.ssh/id_rsa.pub
   ```

5. **No Hostinger:**
   - Acesse o painel → **Advanced** → **SSH Access**
   - Cole a chave pública no campo apropriado
   - Salve

6. **Copie a chave privada** para o GitHub Secret:
   ```bash
   cat ~/.ssh/id_rsa
   ```
   - Copie TODO o conteúdo (incluindo `-----BEGIN` e `-----END`)
   - Cole no secret `HOSTINGER_SSH_KEY`

### Método 2: Usar Chave Existente

Se você já tem uma chave SSH configurada:

1. **Encontre a chave privada:**
   ```bash
   cat ~/.ssh/id_rsa
   ```

2. **Copie TODO o conteúdo** (incluindo linhas de início e fim)

3. **Cole no secret `HOSTINGER_SSH_KEY`** no GitHub

---

## ⚠️ Problemas Comuns

### Erro: "HOSTINGER_SSH_HOST secret is not set"

**Solução:** Configure o secret `HOSTINGER_SSH_HOST` no GitHub

### Erro: "Permission denied (publickey)"

**Causa:** Chave SSH não está autorizada no servidor

**Solução:**
1. Certifique-se de que a chave pública está no servidor Hostinger
2. Verifique se a chave privada no GitHub Secret está correta
3. Verifique se não há espaços extras ou quebras de linha incorretas

### Erro: "Connection refused"

**Causa:** SSH não está habilitado ou porta incorreta

**Solução:**
1. Verifique se SSH está habilitado no Hostinger
2. Verifique a porta SSH (geralmente 22)
3. Verifique se o host está correto

### Erro: "No such file or directory"

**Causa:** Caminho do servidor está incorreto

**Solução:**
1. Verifique o caminho no workflow
2. O caminho padrão é: `/home/USER/domains/DOMAIN/public_html/`
3. Ajuste se necessário

---

## 📋 Checklist de Verificação

- [ ] Todos os secrets estão configurados no GitHub
- [ ] `HOSTINGER_SSH_HOST` está correto
- [ ] `HOSTINGER_SSH_USER` está correto
- [ ] `HOSTINGER_SSH_KEY` contém a chave privada completa (com BEGIN e END)
- [ ] `HOSTINGER_DOMAIN` está correto (sem http://)
- [ ] Chave pública SSH está autorizada no Hostinger
- [ ] SSH está habilitado no Hostinger
- [ ] Workflow foi atualizado (último commit)

---

## 🚀 Próximos Passos

1. ✅ Verifique todos os secrets
2. ✅ Execute o workflow manualmente para testar
3. ✅ Veja os logs para identificar qualquer erro restante
4. ✅ Se funcionar, o próximo push automático deve funcionar

---

## 📝 Notas

- O workflow agora valida todos os secrets antes de executar
- Adiciona teste de conexão SSH antes do deploy
- Melhor tratamento de erros com mensagens claras
- Limpeza automática das chaves SSH após o deploy

---

**Última atualização:** Janeiro 2025
