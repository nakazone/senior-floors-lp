# 📤 Como Fazer Upload do executar-migration.php

## 🎯 Objetivo

Fazer upload do arquivo `executar-migration.php` para o servidor Hostinger para executar a migration do banco de dados.

---

## 📋 Opção 1: Via File Manager do Hostinger (Mais Fácil)

### Passo 1: Baixar o Arquivo

1. **No seu computador**, o arquivo está em:
   ```
   /Users/naka/senior-floors-landing/executar-migration.php
   ```

2. **Ou baixe do GitHub:**
   - Acesse: https://github.com/nakazone/senior-floors-system
   - Navegue até o arquivo `executar-migration.php`
   - Clique em "Raw" (botão no topo direito)
   - Salve o arquivo (Ctrl+S ou Cmd+S)

### Passo 2: Acessar File Manager

1. **Acesse o painel Hostinger:**
   - https://hpanel.hostinger.com
   - Faça login

2. **Procure por "File Manager"** no menu
   - Geralmente em "Files" ou "Advanced"

3. **Navegue até:** `public_html/`

### Passo 3: Fazer Upload

1. **Clique em "Upload"** (botão no topo)

2. **Selecione o arquivo:**
   - Clique em "Choose File" ou arraste o arquivo
   - Selecione `executar-migration.php`

3. **Aguarde o upload completar**

4. **Verifique se o arquivo apareceu** na lista de arquivos

### Passo 4: Executar

1. **Acesse no navegador:**
   ```
   https://seudominio.com/executar-migration.php
   ```

2. **Siga as instruções na tela**

3. **Após executar, DELETE o arquivo** por segurança

---

## 📋 Opção 2: Via FTP

### Passo 1: Conectar via FTP

1. **Use um cliente FTP** (FileZilla, Cyberduck, etc.)

2. **Conecte ao servidor:**
   - Host: `ftp.yourdomain.com` ou IP
   - Usuário: Seu usuário FTP
   - Senha: Sua senha FTP
   - Porta: 21

### Passo 2: Navegar e Fazer Upload

1. **Navegue até:** `/public_html/`

2. **Arraste o arquivo** `executar-migration.php` para o servidor

3. **Aguarde o upload completar**

### Passo 3: Executar

1. **Acesse:** `https://seudominio.com/executar-migration.php`

2. **Siga as instruções na tela**

3. **DELETE o arquivo após usar**

---

## 📋 Opção 3: Via SSH (Avançado)

### Passo 1: Conectar via SSH

```bash
ssh usuario@ssh.yourdomain.com
```

### Passo 2: Navegar e Criar Arquivo

```bash
cd ~/domains/yourdomain.com/public_html
```

### Passo 3: Criar Arquivo

1. **Copie o conteúdo** do arquivo `executar-migration.php`

2. **Crie o arquivo:**
   ```bash
   nano executar-migration.php
   ```

3. **Cole o conteúdo** (Ctrl+Shift+V ou Cmd+V)

4. **Salve:** Ctrl+X, depois Y, depois Enter

### Passo 4: Executar

1. **Acesse:** `https://seudominio.com/executar-migration.php`

2. **Ou execute via linha de comando:**
   ```bash
   php executar-migration.php
   ```

---

## 📋 Opção 4: Copiar Conteúdo Direto (Mais Rápido)

Se você não conseguir fazer upload do arquivo, pode criar diretamente no servidor:

### Passo 1: Acessar File Manager

1. **No Hostinger**, vá em File Manager
2. **Navegue até:** `public_html/`
3. **Clique em "New File"** ou **"Create File"**

### Passo 2: Criar Arquivo

1. **Nome do arquivo:** `executar-migration.php`

2. **Clique em "Edit"** para editar

3. **Copie TODO o conteúdo** do arquivo local e cole

4. **Salve o arquivo**

### Passo 3: Executar

1. **Acesse:** `https://seudominio.com/executar-migration.php`

---

## 📝 Conteúdo do Arquivo

Se você precisar copiar o conteúdo manualmente, o arquivo completo está em:
- GitHub: https://github.com/nakazone/senior-floors-system/blob/main/executar-migration.php
- Local: `/Users/naka/senior-floors-landing/executar-migration.php`

---

## ✅ Verificação

Após fazer upload, verifique:

1. ✅ Arquivo existe em `public_html/executar-migration.php`
2. ✅ Permissões estão corretas (644)
3. ✅ Consegue acessar via navegador
4. ✅ Página carrega sem erros

---

## 🔒 Segurança

⚠️ **IMPORTANTE:** Após executar a migration:

1. **DELETE o arquivo** `executar-migration.php` do servidor
2. **Não deixe o arquivo no servidor** por segurança
3. **Ele contém informações sobre a estrutura do banco**

---

## 🆘 Problemas Comuns

### Erro: "File not found"

**Causa:** Arquivo não está no local correto

**Solução:**
- Verifique se está em `public_html/executar-migration.php`
- Não coloque em subpastas

### Erro: "Permission denied"

**Causa:** Permissões incorretas

**Solução:**
- Via File Manager: Clique com botão direito → Permissions → 644
- Via SSH: `chmod 644 executar-migration.php`

### Erro: "Database not configured"

**Causa:** `config/database.php` não está configurado

**Solução:**
- Configure o arquivo `config/database.php` primeiro
- Veja: `CONFIGURAR_BANCO_AGORA.md`

---

## 📋 Checklist

- [ ] Arquivo baixado/copiado
- [ ] Upload feito para `public_html/`
- [ ] Arquivo acessível via navegador
- [ ] Migration executada com sucesso
- [ ] Arquivo deletado após uso

---

**Última atualização:** Janeiro 2025
