# Módulo Orçamentos (Invoice2go-style)

Módulo completo de orçamentos integrado ao CRM: criação, envio por link, visualização pelo cliente, aceitar/recusar e conversão em projeto.

## 1. Banco de dados

Execute no phpMyAdmin (após `migration-crm-completo.sql`):

- **database/migration-quotes-invoice2go.sql**

Se aparecer erro "Duplicate column name", a coluna já existe — pule aquela linha.

### Tabelas e campos principais

- **quotes**: `quote_number`, `status` (draft/sent/viewed/accepted/declined/expired), `issue_date`, `expiration_date`, `subtotal`, `discount_type`, `discount_value`, `tax_total`, `notes`, `internal_notes`, `currency`, `public_token`
- **quote_items**: `type` (material|labor|service), `name`, `description`, `quantity`, `unit_price`, `tax_rate`, `total`
- **quote_activity_log**: histórico de ações (created, sent, viewed, accepted, declined, edited)
- **customers**: campo `tax_id` (CPF/CNPJ)

## 2. Regras de negócio

- Número do orçamento único e sequencial (ex.: Q-2024-0001).
- Orçamentos começam como "Rascunho".
- Status "Visualizado" quando o cliente abre o link público.
- Aceitar ou recusar apenas uma vez; orçamento expirado não pode ser aceito.
- Só orçamentos em rascunho podem ser editados.
- Orçamento aceito pode ser convertido em Projeto.

## 3. APIs

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| api/quotes/create.php | POST | Criar orçamento (lead_id/customer_id, items, issue_date, expiration_date, discount, tax, notes) |
| api/quotes/get.php | GET | Obter orçamento por id (itens, client, activity_log) |
| api/quotes/list.php | GET | Listar com filtros: status, search, date_from, date_to, lead_id, customer_id |
| api/quotes/update.php | POST | Atualizar status |
| api/quotes/send.php | POST | Enviar (gerar public_token, status=sent, retorna public_link) |
| api/quotes/public-get.php | GET | Obter orçamento por token (público; marca como viewed na 1ª vez) |
| api/quotes/accept.php | POST | Cliente aceita (token) |
| api/quotes/decline.php | POST | Cliente recusa (token, reason opcional) |
| api/quotes/convert-project.php | POST | Converter orçamento aceito em projeto |
| api/quotes/pdf.php | GET | Retorna pdf_path/url (stub; PDF pode ser gerado depois) |

## 4. Painel admin

- **Orçamentos (quotes)**: lista com filtros por status, busca (número ou cliente), data; formulário rápido "Novo orçamento".
- **Detalhe do orçamento (quote-detail)**: resumo, link para o cliente, edição (datas, desconto, impostos, notas) quando rascunho, botão "Enviar orçamento ao cliente", itens, timeline de atividade.

## 5. Página pública (cliente)

- **quote-view.php?token=xxx**: layout com dados da empresa, itens, total, notas. Botões "Aceitar orçamento" e "Recusar" (com motivo opcional). Após aceitar/recusar, exibe confirmação.

## 6. PDF

- Geração de PDF não implementada. Use "Imprimir" no navegador na página pública ou no preview do admin. O campo `pdf_path` em `quotes` pode ser preenchido por uma rotina futura (ex.: geração server-side e armazenamento em arquivo).

## 7. Extensões sugeridas

- Modelos de orçamento (templates).
- Pagamento parcial (entrada %).
- Multimodalidade e multi-moeda.
- Permissões (admin, vendas, visualizador).
