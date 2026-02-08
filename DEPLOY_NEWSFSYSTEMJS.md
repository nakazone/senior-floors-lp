# Deploy para /newSFSystemJS

Este documento descreve o deploy do projeto para o path **/newSFSystemJS** no servidor (ex.: `https://senior-floors.com/newSFSystemJS/`).

## Repositório Git (newSFSystemJS)

Se você quiser usar um repositório Git chamado **newSFSystemJS** (por exemplo no GitHub):

```bash
# Adicionar remote apontando para o repo newSFSystemJS
git remote add newSFSystemJS https://github.com/SEU_USER/newSFSystemJS.git
# ou SSH:
# git remote add newSFSystemJS git@github.com:SEU_USER/newSFSystemJS.git

# Enviar a branch main para esse remote
git push -u newSFSystemJS main
```

Para publicar apenas este projeto nesse repo (substituindo o conteúdo do repo pelo desta pasta):

```bash
git remote add newSFSystemJS https://github.com/SEU_USER/newSFSystemJS.git
git push -u newSFSystemJS main --force
```

O workflow de deploy (abaixo) usa os **secrets do repositório onde está rodando**. Se o workflow estiver no repo **newSFSystemJS**, configure os secrets nesse repositório no GitHub.

## Workflow

- **Arquivo:** `.github/workflows/deploy-newSFSystemJS.yml`
- **Trigger:** push na branch `main` ou execução manual em **Actions** → **Deploy to /newSFSystemJS (Hostinger)** → **Run workflow**
- **Destino no servidor:**  
  `public_html/newSFSystemJS/`  
  (mesmos secrets do deploy principal: `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_KEY`, `HOSTINGER_DOMAIN`, opcionalmente `HOSTINGER_SSH_PORT`)

## O que é deployado

- Todo o conteúdo do repositório (exceto `.git`, `.github`, `node_modules`, arquivos de teste e logs) é enviado para `public_html/newSFSystemJS/`.
- Após o deploy, o site fica acessível em:  
  **https://SEU_DOMINIO/newSFSystemJS/**  
  (ex.: `https://senior-floors.com/newSFSystemJS/`)

## Configuração no GitHub

Os **secrets** usados são os mesmos do deploy para a raiz:

| Secret               | Obrigatório | Descrição                          |
|----------------------|------------|-------------------------------------|
| HOSTINGER_SSH_HOST   | Sim        | Host SSH (ex.: `srv123.hostinger.com`) |
| HOSTINGER_SSH_USER   | Sim        | Usuário SSH                        |
| HOSTINGER_SSH_KEY    | Sim        | Chave privada SSH (conteúdo completo) |
| HOSTINGER_DOMAIN     | Sim        | Domínio (ex.: `senior-floors.com`) |
| HOSTINGER_SSH_PORT   | Não        | Porta SSH (padrão: 22)            |

Não é necessário configurar nada extra para o path: o workflow usa o subpath fixo `newSFSystemJS`.

## Dois deploys na mesma main

Se você mantiver também o workflow **Deploy to Hostinger (SSH) - Fixed** (deploy para `public_html/`), cada push na `main` vai rodar **os dois**:

1. Deploy na raiz → `https://senior-floors.com/`
2. Deploy em /newSFSystemJS → `https://senior-floors.com/newSFSystemJS/`

Se quiser deploy **apenas** em /newSFSystemJS:

- Desabilite ou apague o workflow `deploy-hostinger-ssh-fixed.yml`, ou
- Remova o trigger `push: branches: [main]` desse workflow e use só o `deploy-newSFSystemJS.yml`.

## Netlify (LP) + Hostinger (/newSFSystemJS)

Se você usar **Netlify** para a LP e **Hostinger** só para o backend em `/newSFSystemJS`:

- No Netlify: conecte o mesmo repositório; o Netlify publica a LP (e o resto dos arquivos como estáticos). Veja **NETLIFY.md**.
- Em **index.html**, defina as URLs do backend para o path `/newSFSystemJS`:
  - `SENIOR_FLOORS_FORM_URL = 'https://senior-floors.com/newSFSystemJS/send-lead.php'`
  - `SENIOR_FLOORS_RECEIVE_LEAD_URL = 'https://senior-floors.com/newSFSystemJS/system.php?api=receive-lead'`
- O deploy do **backend** em `/newSFSystemJS` continua sendo feito pelo workflow **Deploy to /newSFSystemJS** (GitHub Actions → Hostinger). O Netlify não executa PHP; ele só hospeda a LP.

## Ajustes na aplicação para rodar em /newSFSystemJS

- **LP / formulários:** Se o `action` ou a URL no JavaScript apontar para a raiz (ex.: `/send-lead.php`), no subpath use `/newSFSystemJS/send-lead.php`. Em `index.html`: `window.SENIOR_FLOORS_FORM_URL = 'https://senior-floors.com/newSFSystemJS/send-lead.php'` e `window.SENIOR_FLOORS_RECEIVE_LEAD_URL = 'https://senior-floors.com/newSFSystemJS/system.php?api=receive-lead'`.
- **PHP:** `system.php`, `send-lead.php`, etc. funcionam por caminho relativo; se você abrir `https://senior-floors.com/newSFSystemJS/system.php`, o próprio PHP já usa o subpath.
- **Node (server/):** Se rodar o backend Node atrás de um proxy em `/newSFSystemJS`, configure o base path no Express ou no reverse proxy (ex.: Nginx `location /newSFSystemJS/`).

## Executar o deploy

1. **Automático:** faça `git push origin main`.
2. **Manual:** no repositório no GitHub → **Actions** → **Deploy to /newSFSystemJS (Hostinger)** → **Run workflow** → **Run workflow**.

## Verificação rápida

Após o deploy, abra no navegador:

- `https://SEU_DOMINIO/newSFSystemJS/` → deve carregar a index (LP ou redirecionamento).
- `https://SEU_DOMINIO/newSFSystemJS/system.php` → painel (se existir e estiver configurado).
- `https://SEU_DOMINIO/newSFSystemJS/api/db-check` ou equivalente, se estiver usando a API Node em esse path.

Se algo retornar 404, confira no Hostinger se a pasta `public_html/newSFSystemJS` foi criada e se os arquivos estão dentro dela.
