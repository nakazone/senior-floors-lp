-- Permissões para os novos módulos do CRM (Visitas, Orçamentos, Pipeline, Contratos)
-- Execute após migration-add-permissions.sql

INSERT INTO `permissions` (`permission_key`, `permission_name`, `permission_group`, `description`) VALUES
('visits.view', 'View Visits', 'visits', 'Visualizar visitas e medições'),
('visits.create', 'Create Visits', 'visits', 'Agendar visitas'),
('visits.edit', 'Edit Visits', 'visits', 'Editar visitas e registrar medições'),
('quotes.view', 'View Quotes', 'quotes', 'Visualizar orçamentos'),
('quotes.create', 'Create Quotes', 'quotes', 'Criar orçamentos'),
('quotes.edit', 'Edit Quotes', 'quotes', 'Editar e alterar status de orçamentos'),
('pipeline.view', 'View Pipeline', 'pipeline', 'Visualizar pipeline Kanban'),
('pipeline.edit', 'Move Pipeline', 'pipeline', 'Mover leads entre estágios'),
('contracts.view', 'View Contracts', 'contracts', 'Visualizar contratos'),
('contracts.create', 'Create Contracts', 'contracts', 'Fechar venda e criar contrato')
ON DUPLICATE KEY UPDATE `permission_name` = VALUES(`permission_name`);

-- Conceder ao admin (opcional: só se quiser que admin tenha explicitamente)
INSERT INTO `user_permissions` (`user_id`, `permission_id`, `granted`)
SELECT u.id, p.id, 1
FROM `users` u
CROSS JOIN `permissions` p
WHERE u.role = 'admin' AND p.permission_key IN (
  'visits.view','visits.create','visits.edit',
  'quotes.view','quotes.create','quotes.edit',
  'pipeline.view','pipeline.edit',
  'contracts.view','contracts.create'
)
ON DUPLICATE KEY UPDATE `granted` = 1;
