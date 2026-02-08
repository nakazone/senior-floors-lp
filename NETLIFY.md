# Deploy da LP no Netlify

A **Landing Page** (HTML, CSS, JS, imagens) pode ser hospedada no **Netlify**. O **backend** (PHP ou Node: envio de leads, CRM, banco) continua no **Hostinger** (na raiz ou em `/newSFSystemJS`).

## Arquitetura

- **Netlify:** serve `index.html`, `styles.css`, `script.js`, `assets/` e demais estáticos.  
  Domínio exemplo: `https://seu-site.netlify.app` ou `https://lp.senior-floors.com` (custom domain).
- **Hostinger:** executa `send-lead.php`, `system.php`, API receive-lead, MySQL, etc.  
  Exemplo: `https://senior-floors.com` ou `https://senior-floors.com/newSFSystemJS/`.

Os formulários da LP (configurados em `index.html`) já enviam para uma URL fixa do backend (ex.: `https://senior-floors.com/send-lead.php`). Não é necessário mudar nada no Netlify para isso funcionar, desde que essa URL aponte para o Hostinger.

## Configuração no Netlify

1. **Conectar o repositório**
   - Netlify Dashboard → **Add new site** → **Import an existing project**.
   - Conecte o GitHub (ou GitLab/Bitbucket) e escolha o repositório deste projeto.
   - Branch: `main` (ou a que você usar para deploy).

2. **Build settings** (já definidos em `netlify.toml`)
   - **Build command:** pode ficar vazio ou o que está no `netlify.toml`.
   - **Publish directory:** `.` (raiz do repositório).
   - O Netlify vai publicar todos os arquivos; **arquivos PHP não são executados** (são servidos como estáticos). O que importa é que a LP (HTML/JS) aponte para o backend no Hostinger.

3. **Domínio**
   - Use o subdomínio padrão (`*.netlify.app`) ou configure um domínio customizado (ex.: `lp.senior-floors.com`) em **Domain management**.

4. **Variáveis de ambiente (opcional)**
   - Em **Site settings** → **Environment variables** você pode definir, por exemplo:
     - `BACKEND_URL` = `https://senior-floors.com`  
     - Se o backend estiver em `/newSFSystemJS`: `BACKEND_URL` = `https://senior-floors.com/newSFSystemJS`  
   - Por padrão a LP não usa essas variáveis; as URLs do formulário estão em `index.html`. Se quiser que a URL do backend venha de variável, é preciso um passo de build que altere `index.html` (ex.: script que substitui um placeholder por `process.env.BACKEND_URL`).

## URLs do formulário (index.html)

As URLs para onde os formulários enviam são definidas em **`index.html`**:

```html
<script>
  window.SENIOR_FLOORS_FORM_URL = 'https://senior-floors.com/send-lead.php';
  window.SENIOR_FLOORS_RECEIVE_LEAD_URL = 'https://senior-floors.com/system.php?api=receive-lead';
</script>
```

- Se o backend estiver na **raiz** do domínio (ex.: `https://senior-floors.com/`), deixe como acima.
- Se o backend estiver em **/newSFSystemJS** (deploy apenas nesse path), altere para:
  - `SENIOR_FLOORS_FORM_URL = 'https://senior-floors.com/newSFSystemJS/send-lead.php'`
  - `SENIOR_FLOORS_RECEIVE_LEAD_URL = 'https://senior-floors.com/newSFSystemJS/system.php?api=receive-lead'`

Faça essa alteração no repositório e um novo deploy no Netlify vai usar as novas URLs.

## CORS

O backend no Hostinger (`send-lead.php` e `system.php?api=receive-lead`) já envia headers CORS que permitem requisições de qualquer origem (`Access-Control-Allow-Origin: *`). Por isso a LP no Netlify (outro domínio) consegue enviar o formulário para o Hostinger sem erro de CORS.

## Resumo do fluxo

1. Usuário acessa a LP no **Netlify** (ex.: `https://lp.senior-floors.com`).
2. Preenche o formulário e clica em enviar.
3. O JavaScript envia um `POST` para `SENIOR_FLOORS_FORM_URL` (Hostinger).
4. O Hostinger processa o lead (e-mail, CSV, banco, etc.) e responde em JSON.
5. A LP mostra a mensagem de sucesso ou erro.

## O que não sobe para o Netlify (recomendado)

Por segurança e para evitar expor arquivos desnecessários, você pode **não** incluir no repositório que o Netlify usa:

- `config/database.php`, `config/smtp.php`, `admin-config.php` (já no `.gitignore`).
- Pastas só de backend, se no futuro separar LP e backend em repositórios diferentes.

Com o repositório atual, o Netlify faz deploy de tudo; arquivos PHP não são executados, apenas servidos como ficheiros. Para um setup mais limpo, pode criar um repositório ou pasta só com os arquivos da LP e apontar o Netlify para ela (por exemplo, **Publish directory** = pasta onde esteja só `index.html`, `script.js`, `styles.css`, `assets/`).

## Referências

- **Backend na raiz:** `DEPLOYMENT.md`, `CONFIG_BANCO_CHECKLIST.md`.
- **Backend em /newSFSystemJS:** `DEPLOY_NEWSFSYSTEMJS.md`.
- **Config do Netlify:** `netlify.toml` na raiz do projeto.
