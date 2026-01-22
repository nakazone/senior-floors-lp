# 🚀 Como Fazer Push no Git - Passo a Passo

## 📍 Você está aqui: Terminal do Mac

O push é feito pelo **Terminal** (linha de comando). Vou te guiar passo a passo!

## ✅ Passo 1: Criar Repositório no GitHub (PRIMEIRO!)

**Antes de fazer push, você precisa criar o repositório no GitHub:**

1. Abra seu navegador
2. Acesse: **https://github.com/new**
3. Faça login (se necessário)
4. Preencha:
   - **Repository name**: `senior-floors-system`
   - **Description**: (opcional) "Sistema Senior Floors"
   - **Visibility**: Marque **Private** ✅
   - **NÃO marque** "Add a README file"
   - **NÃO marque** "Add .gitignore"
   - **NÃO marque** "Choose a license"
5. Clique em **Create repository**

## ✅ Passo 2: Copiar URL do Repositório

Depois de criar, o GitHub vai mostrar uma página com instruções.

**Copie a URL** que aparece. Será algo como:
- `https://github.com/SEU_USUARIO/senior-floors-system.git`

**OU** se você já tem o repo criado:
1. Vá para seu repositório no GitHub
2. Clique no botão verde **Code**
3. Copie a URL HTTPS

## ✅ Passo 3: Abrir Terminal

1. Pressione `Cmd + Espaço` (Spotlight)
2. Digite: `Terminal`
3. Pressione Enter

## ✅ Passo 4: Navegar para o Projeto

No Terminal, digite:

```bash
cd /Users/naka/senior-floors-landing
```

Pressione Enter.

## ✅ Passo 5: Conectar ao GitHub

Agora conecte seu projeto local ao GitHub:

```bash
git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git
```

**⚠️ IMPORTANTE:** Substitua `SEU_USUARIO` pelo seu username do GitHub!

**Exemplo:**
- Se seu username é `joaosilva`, seria:
  ```bash
  git remote add origin https://github.com/joaosilva/senior-floors-system.git
  ```

## ✅ Passo 6: Verificar Conexão

Verifique se conectou corretamente:

```bash
git remote -v
```

Deve mostrar algo como:
```
origin  https://github.com/SEU_USUARIO/senior-floors-system.git (fetch)
origin  https://github.com/SEU_USUARIO/senior-floors-system.git (push)
```

## ✅ Passo 7: Fazer Push!

Agora sim, faça o push:

```bash
git push -u origin main
```

O GitHub pode pedir suas credenciais:
- **Username**: Seu username do GitHub
- **Password**: Use um **Personal Access Token** (não sua senha normal)

### Como criar Personal Access Token:

1. GitHub → **Settings** → **Developer settings**
2. **Personal access tokens** → **Tokens (classic)**
3. **Generate new token (classic)**
4. Dê um nome: `senior-floors-deploy`
5. Marque: `repo` (todas as permissões)
6. Clique **Generate token**
7. **COPIE O TOKEN** (você só vê uma vez!)
8. Use esse token como senha no Terminal

## ✅ Passo 8: Verificar Sucesso

Se tudo deu certo, você verá:

```
Enumerating objects: 59, done.
Counting objects: 100% (59/59), done.
...
To https://github.com/SEU_USUARIO/senior-floors-system.git
 * [new branch]      main -> main
Branch 'main' set up to track 'remote/origin/main'.
```

## 🎉 Pronto!

Agora seus arquivos estão no GitHub!

## 📝 Próximos Pushes (Mais Simples)

Depois do primeiro push, é mais fácil:

```bash
# 1. Adicionar mudanças
git add .

# 2. Fazer commit
git commit -m "Descrição das mudanças"

# 3. Fazer push
git push
```

## ❓ Problemas Comuns

### Erro: "remote origin already exists"
```bash
git remote remove origin
git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git
```

### Erro: "authentication failed"
- Use Personal Access Token, não sua senha
- Veja como criar acima

### Erro: "repository not found"
- Verifique se o nome do repo está correto
- Verifique se você tem acesso ao repositório

## 🆘 Precisa de Ajuda?

Me diga:
1. Você já criou o repositório no GitHub?
2. Qual é seu username do GitHub?
3. Qual erro aparece quando tenta fazer push?
