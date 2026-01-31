# Configurar SYSTEM_API_URL (lead chegar no CRM)

Quando aparece **system_sent: false** — "Lead não chegou no CRM", o send-lead.php não consegue chamar o system (receive-lead). É preciso definir a **URL completa** do system no servidor.

---

## Este projeto (Senior Floors)

- **LP (formulário):** `https://lp.senior-floors.com`
- **Painel (CRM):** `https://senior-floors.com/system.php`
- **SYSTEM_API_URL** (no servidor da LP): `https://senior-floors.com/system.php?api=receive-lead`

O send-lead.php já usa essa URL por padrão. Se criar **config/system-api-url.php** no servidor da LP (copiando de system-api-url.php.example), a URL já vem preenchida.

---

## O que é a URL do system?

É o **mesmo endereço** que você usa no navegador para abrir o painel (CRM). Exemplos:

- `https://senior-floors.com/system.php`
- `https://www.senior-floors.com/system.php`
- `https://app.senior-floors.com/system.php`

A URL que o send-lead precisa é essa + **?api=receive-lead**, por exemplo:  
`https://senior-floors.com/system.php?api=receive-lead`

---

## Como configurar (escolha uma opção)

### Opção 1: Arquivo de configuração (recomendado)

1. No servidor (Hostinger: Gerenciador de Arquivos ou FTP), vá na pasta **config/** (na mesma pasta onde está o send-lead.php ou na raiz do site).
2. Copie o arquivo **system-api-url.php.example** e renomeie a cópia para **system-api-url.php**.
3. Abra **system-api-url.php** e troque a linha:
   ```php
   define('SYSTEM_API_URL', 'https://SEU_DOMINIO/system.php?api=receive-lead');
   ```
   Coloque **exatamente** a URL onde você abre o painel, com `?api=receive-lead` no final. Exemplo:
   ```php
   define('SYSTEM_API_URL', 'https://senior-floors.com/system.php?api=receive-lead');
   ```
4. Salve o arquivo.

Assim o send-lead carrega essa URL e não é preciso editar o send-lead.php (que pode ser sobrescrito no deploy).

---

### Opção 2: Editar send-lead.php

1. No servidor, abra **send-lead.php** (na pasta onde está o formulário, ex.: public_html/ ou public_html/lp/).
2. Procure a linha:
   ```php
   define('SYSTEM_API_URL', ''); // Ex.: 'https://senior-floors.com/system.php?api=receive-lead'
   ```
3. Troque por (use a URL do seu painel):
   ```php
   define('SYSTEM_API_URL', 'https://senior-floors.com/system.php?api=receive-lead');
   ```
   Use **https** ou **http** conforme o seu site, e o **mesmo domínio** que você usa para abrir o system (com ou sem www).
4. Salve o arquivo.

---

## Como saber qual URL usar?

1. Abra o painel (CRM) no navegador e faça login.
2. Olhe a barra de endereço. Exemplo: `https://senior-floors.com/system.php`
3. A URL para configurar é essa + **?api=receive-lead**:  
   `https://senior-floors.com/system.php?api=receive-lead`

---

## Testar

Envie um lead de teste pelo **form-test-lp.html**. A resposta deve mostrar:

- **system_sent: true** — Lead repassado ao CRM.
- **system_database_saved: true** — Lead salvo no banco do CRM.

Se ainda aparecer **system_sent: false**, confira:

- A URL está **exatamente** igual à do navegador (com https/http e com ou sem www)?
- O arquivo foi salvo no servidor (config/system-api-url.php ou send-lead.php)?
