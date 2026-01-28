-- ============================================
-- Migration: Adicionar Sistema de Permissões
-- Execute este script após o schema completo
-- ============================================

-- Tabela de Permissões Disponíveis
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `permission_key` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Chave única da permissão (ex: leads.view)',
  `permission_name` VARCHAR(255) NOT NULL COMMENT 'Nome legível da permissão',
  `permission_group` VARCHAR(50) DEFAULT NULL COMMENT 'Grupo da permissão (leads, customers, projects, etc.)',
  `description` TEXT DEFAULT NULL COMMENT 'Descrição da permissão',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission_key` (`permission_key`),
  INDEX `idx_permission_group` (`permission_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Permissões de Usuários (Many-to-Many)
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  `granted` TINYINT(1) DEFAULT 1 COMMENT '1 = permitido, 0 = negado',
  `granted_by` INT(11) DEFAULT NULL COMMENT 'Usuário que concedeu a permissão',
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_permission` (`user_id`, `permission_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_permission_id` (`permission_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atualizar tabela users para incluir senha e autenticação
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'Hash da senha (bcrypt)',
ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `last_login` TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `login_attempts` INT(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `locked_until` TIMESTAMP NULL DEFAULT NULL;

-- Inserir Permissões Padrão
INSERT INTO `permissions` (`permission_key`, `permission_name`, `permission_group`, `description`) VALUES
-- Dashboard
('dashboard.view', 'View Dashboard', 'dashboard', 'Visualizar o dashboard principal'),

-- Leads
('leads.view', 'View Leads', 'leads', 'Visualizar lista de leads'),
('leads.create', 'Create Leads', 'leads', 'Criar novos leads'),
('leads.edit', 'Edit Leads', 'leads', 'Editar leads existentes'),
('leads.delete', 'Delete Leads', 'leads', 'Excluir leads'),
('leads.assign', 'Assign Leads', 'leads', 'Atribuir leads a outros usuários'),

-- Customers
('customers.view', 'View Customers', 'customers', 'Visualizar lista de customers'),
('customers.create', 'Create Customers', 'customers', 'Criar novos customers'),
('customers.edit', 'Edit Customers', 'customers', 'Editar customers existentes'),
('customers.delete', 'Delete Customers', 'customers', 'Excluir customers'),

-- Projects
('projects.view', 'View Projects', 'projects', 'Visualizar lista de projects'),
('projects.create', 'Create Projects', 'projects', 'Criar novos projects'),
('projects.edit', 'Edit Projects', 'projects', 'Editar projects existentes'),
('projects.delete', 'Delete Projects', 'projects', 'Excluir projects'),
('projects.update_status', 'Update Project Status', 'projects', 'Atualizar status de projects'),

-- Coupons
('coupons.view', 'View Coupons', 'coupons', 'Visualizar lista de coupons'),
('coupons.create', 'Create Coupons', 'coupons', 'Criar novos coupons'),
('coupons.edit', 'Edit Coupons', 'coupons', 'Editar coupons existentes'),
('coupons.delete', 'Delete Coupons', 'coupons', 'Excluir coupons'),

-- Users & Permissions
('users.view', 'View Users', 'users', 'Visualizar lista de usuários'),
('users.create', 'Create Users', 'users', 'Criar novos usuários'),
('users.edit', 'Edit Users', 'users', 'Editar usuários existentes'),
('users.delete', 'Delete Users', 'users', 'Excluir usuários'),
('users.manage_permissions', 'Manage User Permissions', 'users', 'Gerenciar permissões de usuários'),

-- Settings
('settings.view', 'View Settings', 'settings', 'Visualizar configurações'),
('settings.edit', 'Edit Settings', 'settings', 'Editar configurações'),

-- Activities
('activities.view', 'View Activities', 'activities', 'Visualizar atividades'),
('activities.create', 'Create Activities', 'activities', 'Criar atividades'),

-- Reports
('reports.view', 'View Reports', 'reports', 'Visualizar relatórios'),
('reports.export', 'Export Reports', 'reports', 'Exportar relatórios')

ON DUPLICATE KEY UPDATE `permission_name` = VALUES(`permission_name`);

-- Criar usuário admin padrão se não existir (senha: admin123 - DEVE SER ALTERADA!)
-- Hash bcrypt de "admin123"
INSERT INTO `users` (`name`, `email`, `role`, `is_active`, `password_hash`) 
SELECT 'Admin', 'admin@senior-floors.com', 'admin', 1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@senior-floors.com');

-- Conceder todas as permissões ao admin padrão
INSERT INTO `user_permissions` (`user_id`, `permission_id`, `granted`)
SELECT u.id, p.id, 1 
FROM `users` u
CROSS JOIN `permissions` p
WHERE u.email = 'admin@senior-floors.com'
ON DUPLICATE KEY UPDATE `granted` = 1;
