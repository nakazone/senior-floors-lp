#!/usr/bin/env bash
# Exporta apenas a Landing Page (versão Node.js) para um diretório que pode ser
# usado como repositório Git separado. Uso: ./scripts/export-lp-node.sh

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
OUT_DIR="$ROOT_DIR/lp-node-export"

echo "Exporting Node.js LP to: $OUT_DIR"

rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR"

# LP estáticos
cp "$ROOT_DIR/index.html" "$OUT_DIR/"
cp "$ROOT_DIR/script.js" "$OUT_DIR/"
cp "$ROOT_DIR/styles.css" "$OUT_DIR/"
cp -r "$ROOT_DIR/assets" "$OUT_DIR/"

# Servidor Node (LP + API send-lead / receive-lead)
cp -r "$ROOT_DIR/server" "$OUT_DIR/"

# Netlify (opcional)
cp "$ROOT_DIR/netlify.toml" "$OUT_DIR/" 2>/dev/null || true

# manifest se existir
cp "$ROOT_DIR/manifest.json" "$OUT_DIR/" 2>/dev/null || true

# .gitignore para o repositório da LP Node
cat > "$OUT_DIR/.gitignore" << 'GITIGNORE'
node_modules/
server/node_modules/
.env
.env.local
*.log
.DS_Store
leads.csv
GITIGNORE

# package.json na raiz (inicia o server)
cat > "$OUT_DIR/package.json" << 'PKG'
{
  "name": "senior-floors-lp-node",
  "version": "1.0.0",
  "description": "Senior Floors Landing Page (Node.js)",
  "private": true,
  "scripts": {
    "start": "cd server && npm install && npm start",
    "dev": "cd server && npm run dev"
  },
  "engines": { "node": ">=18" }
}
PKG

# README para o repo da LP Node
cat > "$OUT_DIR/README.md" << 'README'
# Senior Floors – Landing Page (Node.js)

Repositório contendo **apenas** a Landing Page da Senior Floors na versão Node.js: HTML, CSS, JS, assets e servidor Node (formulários, receive-lead, CSV, opcional e-mail/MySQL).

## Conteúdo

- `index.html`, `script.js`, `styles.css`, `assets/` – LP estática
- `server/` – Backend Node (Express): `/send-lead`, `/api/receive-lead`, `/api/db-check`, serve a LP
- `netlify.toml` – Configuração Netlify (publicar a LP; formulários podem apontar para outro backend)

## Desenvolvimento local

```bash
npm install
npm start
```

Abre em `http://localhost:3000`. O formulário envia para o próprio servidor (`/send-lead`).

## Deploy

- **Netlify:** Conecte este repositório; publish = `.` (ou use `netlify.toml`). Configure a URL do backend em `index.html` se o backend estiver noutro domínio.
- **Outro host Node:** `npm start` ou configure o processo para rodar `node server/index.js` com a raiz do projeto como diretório de trabalho.
- **Só estáticos:** Use apenas `index.html`, `script.js`, `styles.css`, `assets/` e aponte o action do formulário para a URL do seu backend (ex.: Hostinger).

## Variáveis de ambiente (server)

Em `server/` crie `.env` a partir de `server/env.example` (DB_*, SMTP_*, PORT) se quiser gravar leads no MySQL ou enviar e-mail.
README

echo "Done. Export folder: $OUT_DIR"
echo ""
echo "To push this as a new Git repo:"
echo "  cd $OUT_DIR"
echo "  git init"
echo "  git add ."
echo "  git commit -m 'Senior Floors LP (Node.js)'"
echo "  git remote add origin <your-repo-url>"
echo "  git branch -M main && git push -u origin main"
echo ""
