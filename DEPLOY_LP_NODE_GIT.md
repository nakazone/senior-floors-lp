# Deploy apenas da Landing Page (Node.js) para o Git

Este guia explica como publicar **só a versão Node.js da Landing Page** num repositório Git separado (sem o sistema PHP, admin, CRM, etc.).

## Passo 1: Gerar o pacote da LP Node

No repositório principal (senior-floors-landing), execute:

```bash
chmod +x scripts/export-lp-node.sh
./scripts/export-lp-node.sh
```

Isso cria a pasta **`lp-node-export/`** com:

- `index.html`, `script.js`, `styles.css`, `assets/`
- `server/` (backend Node: send-lead, receive-lead, db-check)
- `netlify.toml`, `package.json`, `.gitignore`, `README.md`

A pasta `lp-node-export/` **não** é commitada no repositório principal (está no `.gitignore`).

## Passo 2: Enviar para um novo repositório Git

### Opção A: Novo repositório no GitHub/GitLab

1. Crie um repositório vazio (ex.: `senior-floors-lp-node`).
2. No seu computador:

```bash
cd lp-node-export
git init
git add .
git commit -m "Senior Floors LP (Node.js)"
git branch -M main
git remote add origin https://github.com/SEU_USER/senior-floors-lp-node.git
git push -u origin main
```

(Substitua `SEU_USER/senior-floors-lp-node` pela URL do seu repositório.)

### Opção B: Usar um remote com outro nome no mesmo projeto

Se quiser manter o export dentro do mesmo clone e enviar só essa pasta para outro remote:

```bash
./scripts/export-lp-node.sh
cd lp-node-export
git init
git add .
git commit -m "Senior Floors LP (Node.js)"
git remote add lp-node https://github.com/SEU_USER/senior-floors-lp-node.git
git push -u lp-node main
```

## Atualizar o repositório da LP depois

Sempre que alterar a LP ou o servidor Node no projeto principal:

1. Rode de novo o export: `./scripts/export-lp-node.sh`
2. Entre na pasta do repo da LP, puxe as mudanças (ou copie o conteúdo de `lp-node-export` para o clone do repo da LP) e faça commit + push:

```bash
cd lp-node-export
# Se já tiver .git de um clone do repo remoto, apenas:
git add -A
git status
git commit -m "Update LP from main project"
git push
```

Se estiver a usar um clone separado do repositório da LP, pode copiar o conteúdo de `lp-node-export` para esse clone e depois fazer commit e push nesse clone.

## O que fica de fora

O export **não** inclui:

- PHP (system.php, send-lead.php, admin-modules, api/*.php, etc.)
- Configurações sensíveis (config/database.php, .env)
- Documentação do sistema completo (apenas o README da LP Node)
- Testes e ferramentas (form-test-lp.html, diagnostico-banco.php, etc.)
- GitHub Actions do projeto principal

O repositório gerado contém apenas o necessário para servir a LP em Node.js e (opcionalmente) em Netlify ou outro host.
