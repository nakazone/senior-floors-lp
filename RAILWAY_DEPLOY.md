# 🚂 Deploy no Railway - Guia Passo a Passo

## Passo 1: Criar conta no Railway

1. Acesse https://railway.app
2. Clique em **"Login"** ou **"Start a New Project"**
3. Faça login com GitHub (recomendado) ou Google

## Passo 2: Conectar o repositório GitHub

1. No dashboard do Railway, clique em **"New Project"**
2. Escolha **"Deploy from GitHub repo"**
3. Autorize o Railway a acessar seus repositórios (se necessário)
4. Procure e selecione: **`nakazone/senior-floors-system`**
5. Clique em **"Deploy Now"**

## Passo 3: Configurar variáveis de ambiente

Após o Railway começar a fazer o deploy (pode falhar inicialmente por falta das variáveis):

1. No projeto, clique na aba **"Variables"** (ou no serviço → **"Variables"**)
2. Adicione as seguintes variáveis:

```
DB_HOST=seu_host_mysql
DB_NAME=seu_nome_banco
DB_USER=seu_usuario_mysql
DB_PASS=sua_senha_mysql
```

**Onde encontrar esses valores:**
- **Se usar Hostinger:** Painel Hostinger → Databases → MySQL → veja Host, Database, User, Password
- **Se usar Railway MySQL:** Adicione um MySQL addon primeiro (veja Passo 4 abaixo)

3. Clique em **"Add"** para cada variável

## Passo 4 (Opcional): Adicionar MySQL no Railway

Se você **não tem** um MySQL ainda:

1. No projeto Railway, clique em **"New"** → **"Database"** → **"Add MySQL"**
2. O Railway criará um MySQL e automaticamente adicionará variáveis como:
   - `MYSQL_HOST` → use como `DB_HOST`
   - `MYSQLDATABASE` → use como `DB_NAME`
   - `MYSQLUSER` → use como `DB_USER`
   - `MYSQLPASSWORD` → use como `DB_PASS`
3. Copie esses valores e adicione como variáveis `DB_*` no seu serviço Node.js

## Passo 5: Verificar o deploy

1. Após adicionar as variáveis, o Railway vai **redeployar automaticamente**
2. Aguarde alguns minutos (build + deploy)
3. Vá na aba **"Settings"** do serviço
4. Role até **"Domains"** ou **"Generate Domain"**
5. Clique em **"Generate Domain"** para criar uma URL pública
6. A URL será algo como: `https://senior-floors-system-production.up.railway.app`

## Passo 6: Testar a API

Abra no navegador ou use curl:

```bash
# Health check
curl https://senior-floors-system-production.up.railway.app/api/health

# Deve retornar: {"ok":true,"service":"senior-floors-system","time":"..."}
```

## Passo 7: Configurar a LP (Vercel)

Na Vercel, adicione a variável:

```
SYSTEM_API_URL=https://senior-floors-system-production.up.railway.app
```

Assim a LP poderá enviar leads para o System no Railway.

---

## ❌ Problemas comuns

### Deploy falha com erro de build
- Verifique se o `package.json` tem `"start": "node index.js"`
- Verifique se o Node.js versão está correta (`engines.node >= 18`)

### Erro de conexão com banco
- Verifique se as variáveis `DB_*` estão corretas
- Teste a conexão localmente primeiro com um `.env`
- Se usar Hostinger, verifique se o IP do Railway está liberado no firewall do MySQL

### App não responde na URL
- Verifique se gerou um domínio público (Settings → Generate Domain)
- Verifique os logs do Railway (aba "Deployments" → clique no deploy → "View Logs")

### Porta não configurada
- O Railway define `PORT` automaticamente
- Não precisa definir manualmente (o código já usa `process.env.PORT`)

---

## 📝 Checklist

- [ ] Conta Railway criada
- [ ] Repositório GitHub conectado
- [ ] Variáveis `DB_*` configuradas
- [ ] Deploy concluído com sucesso
- [ ] Domínio público gerado
- [ ] `/api/health` retorna OK
- [ ] Variável `SYSTEM_API_URL` configurada na Vercel
