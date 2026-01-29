# Leads no banco de dados – Passo a passo (Hostinger)

Se os leads estão indo para o CSV mas **não para o banco**, siga estes passos no servidor.

---

## 1. Criar o arquivo de configuração

O arquivo `config/database.php` **não é enviado** pelo deploy (por segurança). Você precisa criá-lo no servidor.

1. No **Hostinger**, abra o **Gerenciador de Arquivos** (ou FTP) e vá até a pasta do site (ex.: `public_html`).
2. Entre na pasta **`config/`**.
3. Copie o arquivo **`database.php.example`** e renomeie a cópia para **`database.php`**.

---

## 2. Obter as credenciais MySQL no Hostinger

1. No painel do Hostinger: **Bancos de dados MySQL** (ou **MySQL Databases**).
2. Crie um banco (se ainda não existir) e um usuário com senha.
3. Anote:
   - **Nome do banco** (ex.: `u123456789_senior_floors_db`) – nome completo com prefixo.
   - **Usuário** (ex.: `u123456789_senior_user`).
   - **Senha** do usuário.
   - **Host** costuma ser `localhost`.

---

## 3. Editar config/database.php

Abra `config/database.php` no editor do Hostinger e preencha:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_senior_floors_db');   // Nome COMPLETO do banco
define('DB_USER', 'u123456789_senior_user');        // Nome COMPLETO do usuário
define('DB_PASS', 'SuaSenhaRealAqui');              // Senha do usuário
define('DB_CHARSET', 'utf8mb4');
```

Salve o arquivo.

---

## 4. Criar a tabela `leads` no MySQL

1. No Hostinger, abra **phpMyAdmin** (ou “Acessar phpMyAdmin” no banco).
2. Selecione o banco de dados que você configurou.
3. Vá em **Importar** (Import).
4. Escolha o arquivo **`database/schema-v3-completo.sql`** do seu projeto (faça upload se necessário) e execute.
5. Isso cria a tabela **`leads`** e as demais tabelas do CRM.

Se preferir, copie e cole o conteúdo de `database/schema-v3-completo.sql` na aba **SQL** e execute.

---

## 5. Conferir com o diagnóstico

No navegador, acesse:

**`https://SEU_DOMINIO/diagnostico-banco.php`**

A página mostra:

- Se o arquivo `config/database.php` foi encontrado  
- Se a configuração está preenchida  
- Se a conexão com o MySQL funciona  
- Se a tabela `leads` existe  
- As últimas linhas do log de salvamento de leads  

Corrija o que estiver em vermelho (✗) e teste o formulário de novo.

---

## 6. Testar o formulário

Envie um lead pelo formulário do site. Depois:

1. Acesse o painel: **`https://SEU_DOMINIO/system.php`** → **CRM - Leads**. O lead deve aparecer na lista (dados vindo do MySQL).
2. Ou abra de novo **diagnostico-banco.php** e veja as últimas linhas do log: deve aparecer algo como “✅ Lead saved to database”.

---

## Resumo

| Problema                         | Solução                                                                 |
|----------------------------------|-------------------------------------------------------------------------|
| `config/database.php` não existe | Copiar `config/database.php.example` para `config/database.php`        |
| Banco “não configurado”          | Editar `config/database.php` com nome do banco, usuário e senha reais   |
| Erro de conexão                  | Conferir host, nome do banco, usuário e senha no painel do Hostinger   |
| Tabela `leads` não existe        | Executar `database/schema-v3-completo.sql` no MySQL (phpMyAdmin)        |

Depois de configurar, você pode remover ou restringir o acesso a **diagnostico-banco.php** por segurança.
