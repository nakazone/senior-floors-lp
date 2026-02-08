# 🏠 Senior Floors - Sistema de Gestão de Leads

Sistema completo de gestão de leads para Senior Floors, empresa de flooring nos EUA.

## 🚀 Features

- ✅ Landing Page otimizada para conversão
- ✅ Sistema de captura de leads (formulários)
- ✅ Painel administrativo completo
- ✅ CRM integrado
- ✅ Banco de dados MySQL
- ✅ Deploy automático via GitHub Actions
- ✅ LP hospedável no **Netlify** (backend no Hostinger) — ver [NETLIFY.md](NETLIFY.md)
- ✅ LP em **Node.js** na **Vercel** (serverless: `/api/send-lead`, `/api/receive-lead`) — ver [VERCEL.md](VERCEL.md)

## 📁 Estrutura do Projeto

```
public_html/
├── api/                    # API endpoints
│   └── leads/
├── admin-modules/          # Módulos do painel admin
├── config/                  # Configurações
├── database/               # Scripts SQL
├── lp/                     # Landing page files
├── assets/                 # Imagens e recursos
├── system.php             # Painel administrativo
└── index.html             # Landing page principal
```

## 🛠️ Tecnologias

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+ **ou Node.js 18+** (ver pasta `server/`)
- **Database**: MySQL 5.7+
- **Email**: PHPMailer (PHP) ou Nodemailer (Node)
- **Deploy**: GitHub Actions → Hostinger

### Backend em Node.js (opcional)

O projeto inclui uma versão do backend em **Node.js** na pasta `server/`, que substitui o fluxo PHP de recebimento de leads (`send-lead.php`, `system.php?api=receive-lead`). Use o mesmo banco MySQL. Instruções: **[server/README.md](server/README.md)**.

## 📋 Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Acesso FTP ou SSH ao Hostinger

## 🔧 Instalação

### 1. Clone o Repositório

```bash
git clone https://github.com/USERNAME/senior-floors-system.git
cd senior-floors-system
```

### 2. Configure o Banco de Dados

1. Crie um banco MySQL no Hostinger
2. Execute `database/schema.sql` no phpMyAdmin
3. Configure `config/database.php` com suas credenciais

### 3. Configure Email (Opcional)

1. Configure PHPMailer em `send-lead.php`
2. Adicione Google App Password

### 4. Configure Deploy Automático

Veja `DEPLOYMENT.md` para instruções completas.

## 📚 Documentação

- `FASE1_MODULO01_SETUP.md` - Setup do banco de dados
- `DEPLOYMENT.md` - Deploy automático
- `SYSTEM_INTEGRATION_SETUP.md` - Integração de sistemas

## 🔐 Segurança

- ✅ Senhas e credenciais em arquivos separados (não commitados)
- ✅ Validação e sanitização de dados
- ✅ Prepared statements (SQL injection protection)
- ✅ HTTPS obrigatório em produção

## 📝 Licença

Proprietário - Senior Floors

## 👥 Contribuição

Sistema interno - não open source.

---

**Desenvolvido para Senior Floors** 🏠
# Deploy Test - Wed Jan 21 22:32:29 MST 2026
