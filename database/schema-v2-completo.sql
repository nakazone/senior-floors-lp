-- ============================================
-- Senior Floors CRM - Schema Completo v2.0
-- Sistema de CRM para empresa de Flooring
-- Inspirado em HubSpot, Pipedrive, Salesforce
-- ============================================

-- ============================================
-- TABELA 1: LEADS (Atualizada)
-- ============================================
CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `zipcode` VARCHAR(10) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `source` VARCHAR(50) DEFAULT 'LP' COMMENT 'LP, Website, Ads, Referral, etc.',
  `form_type` VARCHAR(50) DEFAULT 'contact-form' COMMENT 'hero-form, contact-form',
  `status` ENUM('new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost') DEFAULT 'new',
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `customer_type` ENUM('residential', 'commercial', 'property_manager', 'investor', 'builder') DEFAULT 'residential',
  `owner_id` INT(11) DEFAULT NULL COMMENT 'Sales rep responsável',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_source` (`source`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_email` (`email`),
  INDEX `idx_owner_id` (`owner_id`),
  INDEX `idx_customer_type` (`customer_type`),
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 2: CLIENTES (Novos clientes convertidos de leads)
-- ============================================
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL COMMENT 'Lead que originou este cliente',
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(50) DEFAULT NULL,
  `zipcode` VARCHAR(10) DEFAULT NULL,
  `customer_type` ENUM('residential', 'commercial', 'property_manager', 'investor', 'builder') DEFAULT 'residential',
  `owner_id` INT(11) DEFAULT NULL COMMENT 'Sales rep responsável',
  `status` ENUM('active', 'inactive', 'archived') DEFAULT 'active',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_owner_id` (`owner_id`),
  INDEX `idx_customer_type` (`customer_type`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 3: PROJETOS (Obras)
-- ============================================
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `lead_id` INT(11) DEFAULT NULL COMMENT 'Lead que originou este projeto',
  `name` VARCHAR(255) NOT NULL COMMENT 'Nome do projeto',
  `project_type` ENUM('installation', 'refinishing', 'repair', 'maintenance') DEFAULT 'installation',
  `status` ENUM('quoted', 'scheduled', 'in_progress', 'completed', 'cancelled', 'on_hold') DEFAULT 'quoted',
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(50) DEFAULT NULL,
  `zipcode` VARCHAR(10) DEFAULT NULL,
  `estimated_start_date` DATE DEFAULT NULL,
  `estimated_end_date` DATE DEFAULT NULL,
  `actual_start_date` DATE DEFAULT NULL,
  `actual_end_date` DATE DEFAULT NULL,
  `estimated_cost` DECIMAL(10,2) DEFAULT NULL,
  `actual_cost` DECIMAL(10,2) DEFAULT NULL,
  `owner_id` INT(11) DEFAULT NULL COMMENT 'Sales rep/Project manager responsável',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_project_type` (`project_type`),
  INDEX `idx_owner_id` (`owner_id`),
  INDEX `idx_estimated_start_date` (`estimated_start_date`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 4: ATIVIDADES (Timeline de ações)
-- ============================================
CREATE TABLE IF NOT EXISTS `activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `activity_type` ENUM('email_sent', 'whatsapp_message', 'phone_call', 'meeting_scheduled', 'site_visit', 'proposal_sent', 'note', 'status_change', 'assignment', 'other') DEFAULT 'note',
  `subject` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `activity_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `user_id` INT(11) DEFAULT NULL COMMENT 'Usuário que executou a ação',
  `owner_id` INT(11) DEFAULT NULL COMMENT 'Responsável pela atividade',
  `related_to` VARCHAR(50) DEFAULT NULL COMMENT 'lead, customer, project',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_activity_type` (`activity_type`),
  INDEX `idx_activity_date` (`activity_date`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_owner_id` (`owner_id`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 5: HISTÓRICO DE ATRIBUIÇÃO (Encaminhamento)
-- ============================================
CREATE TABLE IF NOT EXISTS `assignment_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `from_user_id` INT(11) DEFAULT NULL COMMENT 'De quem foi transferido',
  `to_user_id` INT(11) NOT NULL COMMENT 'Para quem foi transferido',
  `reason` TEXT DEFAULT NULL COMMENT 'Motivo da transferência',
  `assigned_by` INT(11) DEFAULT NULL COMMENT 'Quem fez a atribuição',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_to_user_id` (`to_user_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 6: CUPONS DE DESCONTO
-- ============================================
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código do cupom',
  `name` VARCHAR(255) DEFAULT NULL COMMENT 'Nome do cupom',
  `discount_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL COMMENT 'Valor do desconto (percentual ou fixo)',
  `max_uses` INT(11) DEFAULT NULL COMMENT 'Máximo de usos (NULL = ilimitado)',
  `used_count` INT(11) DEFAULT 0 COMMENT 'Quantas vezes foi usado',
  `valid_from` DATE DEFAULT NULL,
  `valid_until` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_valid_until` (`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 7: USO DE CUPONS
-- ============================================
CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` INT(11) NOT NULL,
  `lead_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Valor do desconto aplicado',
  `used_by` INT(11) DEFAULT NULL COMMENT 'Usuário que aplicou',
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_coupon_id` (`coupon_id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_used_at` (`used_at`),
  FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 8: TAGS (Atualizada)
-- ============================================
CREATE TABLE IF NOT EXISTS `lead_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lead_tag` (`lead_id`, `tag_name`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_tag_name` (`tag_name`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 9: TAGS DE CLIENTES
-- ============================================
CREATE TABLE IF NOT EXISTS `customer_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_tag` (`customer_id`, `tag_name`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_tag_name` (`tag_name`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 10: TAGS DE PROJETOS
-- ============================================
CREATE TABLE IF NOT EXISTS `project_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_tag` (`project_id`, `tag_name`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_tag_name` (`tag_name`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 11: NOTAS (Atualizada - agora genérica)
-- ============================================
CREATE TABLE IF NOT EXISTS `lead_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `note` TEXT NOT NULL,
  `created_by` VARCHAR(255) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 12: NOTAS DE CLIENTES
-- ============================================
CREATE TABLE IF NOT EXISTS `customer_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL,
  `note` TEXT NOT NULL,
  `created_by` VARCHAR(255) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 13: NOTAS DE PROJETOS
-- ============================================
CREATE TABLE IF NOT EXISTS `project_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `note` TEXT NOT NULL,
  `created_by` VARCHAR(255) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA 14: USUÁRIOS (Sales Reps / Team)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('admin', 'sales_rep', 'project_manager', 'support') DEFAULT 'sales_rep',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================
-- Índices compostos para queries frequentes
CREATE INDEX idx_lead_status_priority ON leads(status, priority);
CREATE INDEX idx_lead_owner_status ON leads(owner_id, status);
CREATE INDEX idx_project_status_dates ON projects(status, estimated_start_date);
CREATE INDEX idx_activity_lead_date ON activities(lead_id, activity_date);
CREATE INDEX idx_activity_customer_date ON activities(customer_id, activity_date);

-- ============================================
-- DADOS INICIAIS (Seed Data)
-- ============================================

-- Usuário padrão (admin)
INSERT INTO `users` (`name`, `email`, `role`, `is_active`) 
VALUES ('Admin', 'admin@senior-floors.com', 'admin', 1)
ON DUPLICATE KEY UPDATE `name` = `name`;

-- Cupons de exemplo
INSERT INTO `coupons` (`code`, `name`, `discount_type`, `discount_value`, `max_uses`, `is_active`) 
VALUES 
  ('WELCOME10', 'Welcome Discount', 'percentage', 10.00, NULL, 1),
  ('REFERRAL50', 'Referral Bonus', 'fixed', 50.00, NULL, 1)
ON DUPLICATE KEY UPDATE `name` = `name`;
