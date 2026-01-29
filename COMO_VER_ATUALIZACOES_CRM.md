# Como ver as atualizações do CRM no sistema

## 1. Acessar o painel correto

O **CRM e todas as atualizações** (Pipeline, Visitas, Orçamentos, etc.) ficam no **painel admin**, não na página inicial do site.

- **URL do painel:** `https://seusite.com/system.php`  
  (troque `seusite.com` pelo seu domínio)

- **Página inicial (index.html):** é só o site da Senior Floors (formulário de contato, etc.). As novas funcionalidades do CRM **não** aparecem ali.

No **footer** do site foi adicionado um link discreto: **"Acesso ao painel (Admin)"**, que leva ao `system.php`.

## 2. Fazer login

Ao abrir `system.php`, use o usuário e senha do admin (configurados no banco ou em `admin-config.php`). Depois do login você verá o menu lateral com:

- Dashboard  
- CRM - Leads  
- **Pipeline (Kanban)**  
- **Visitas e Medições**  
- **Orçamentos**  
- Customers  
- Projects  
- Coupons  
- Users  
- Settings  

## 3. Se os novos itens não aparecerem

### a) Arquivos não foram enviados ao servidor

Se você desenvolveu local e depois fez deploy (GitHub, FTP, Hostinger), é preciso **enviar os arquivos novos** para o servidor. Depois do deploy, acesse:

`https://seusite.com/verificar-atualizacoes-crm.php`

Essa página lista quais arquivos do CRM existem no servidor. Se algo estiver com ✗, o deploy não subiu esse arquivo.

### b) Cache do navegador

- Faça **hard refresh:** `Ctrl+Shift+R` (Windows/Linux) ou `Cmd+Shift+R` (Mac).  
- Ou abra `system.php` em uma **aba anônima/privada**.

### c) Banco de dados sem migration

Para Pipeline, Visitas, Orçamentos e Contratos funcionarem, o banco precisa das novas tabelas. Execute no MySQL (phpMyAdmin ou CLI):

`database/migration-crm-completo.sql`

(após já ter rodado o schema v3 completo.)

## 4. Resumo

| O que você vê              | Onde está        | O que fazer                    |
|----------------------------|------------------|--------------------------------|
| Site da Senior Floors      | index.html       | Normal. Para CRM use system.php |
| Tela de login              | system.php       | Digite usuário e senha         |
| Dashboard, CRM, Pipeline…   | system.php (logado) | Menu lateral com todos os módulos |

**Link direto para o painel:** `https://SEU_DOMINIO/system.php`
