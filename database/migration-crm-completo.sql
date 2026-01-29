-- ============================================
-- Senior Floors CRM - Migration Completo (11 blocos)
-- Novos campos, qualificação, pipeline, visitas, orçamentos, automações, IA
-- Execute após schema-v3-completo.sql
-- ============================================

-- ============================================
-- 1. LEADS - Novos campos (captura e qualificação)
-- ============================================

-- Campos de captura (endereço, tipo imóvel, tipo serviço, interesse)
-- Execute uma vez; se coluna já existir, ignore o erro ou comente a linha
ALTER TABLE `leads`
  ADD COLUMN `address` TEXT NULL AFTER `zipcode`,
  ADD COLUMN `property_type` ENUM('casa','apartamento','comercial') NULL AFTER `address`,
  ADD COLUMN `service_type` VARCHAR(100) NULL COMMENT 'Vinyl, Hardwood, Tile, Carpet, Refinishing, etc.' AFTER `property_type`,
  ADD COLUMN `main_interest` TEXT NULL AFTER `service_type`;

ALTER TABLE `leads`
  ADD COLUMN `budget_estimated` DECIMAL(12,2) NULL AFTER `main_interest`,
  ADD COLUMN `urgency` ENUM('imediato','30_dias','60_mais') NULL AFTER `budget_estimated`,
  ADD COLUMN `is_decision_maker` TINYINT(1) NULL COMMENT '1=Sim, 0=Não' AFTER `urgency`,
  ADD COLUMN `payment_type` ENUM('cash','financing') NULL AFTER `is_decision_maker`,
  ADD COLUMN `has_competition` TINYINT(1) NULL COMMENT '1=Sim, 0=Não' AFTER `payment_type`,
  ADD COLUMN `lead_score` INT(11) DEFAULT 0 AFTER `has_competition`,
  ADD COLUMN `last_activity_at` DATETIME NULL AFTER `lead_score`;

-- ============================================
-- 2. PIPELINE STAGES (Kanban)
-- ============================================
CREATE TABLE IF NOT EXISTS `pipeline_stages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `order_num` INT(11) DEFAULT 0,
  `sla_hours` INT(11) DEFAULT NULL COMMENT 'SLA em horas para sair deste estágio',
  `required_actions` JSON DEFAULT NULL COMMENT 'Ações obrigatórias',
  `required_fields` JSON DEFAULT NULL COMMENT 'Campos obrigatórios',
  `is_closed` TINYINT(1) DEFAULT 0 COMMENT '1=estágio final (ganhou/perdeu)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  INDEX `idx_order` (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir estágios padrão
INSERT INTO `pipeline_stages` (`name`, `slug`, `order_num`, `sla_hours`, `is_closed`) VALUES
('Lead recebido', 'lead_received', 1, 24, 0),
('Contato feito', 'contact_made', 2, 48, 0),
('Qualificado', 'qualified', 3, 72, 0),
('Visita / Medição agendada', 'visit_scheduled', 4, 168, 0),
('Medição realizada', 'measurement_done', 5, 72, 0),
('Orçamento enviado', 'quote_sent', 6, 168, 0),
('Negociação', 'negotiation', 7, 336, 0),
('Fechado - Ganhou', 'closed_won', 8, NULL, 1),
('Fechado - Perdeu', 'closed_lost', 9, NULL, 1),
('Pós-venda', 'post_sale', 10, NULL, 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Coluna pipeline_stage_id em leads (referência ao estágio atual)
ALTER TABLE `leads`
  ADD COLUMN `pipeline_stage_id` INT(11) NULL AFTER `status`,
  ADD INDEX `idx_pipeline_stage_id` (`pipeline_stage_id`);

-- FK opcional (só se pipeline_stages existir)
-- ALTER TABLE leads ADD FOREIGN KEY (pipeline_stage_id) REFERENCES pipeline_stages(id) ON DELETE SET NULL;

-- ============================================
-- 3. REGRAS DE DISTRIBUIÇÃO DE LEADS
-- ============================================
CREATE TABLE IF NOT EXISTS `lead_distribution_rules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('round_robin','by_region','by_source','manual') DEFAULT 'round_robin',
  `config` JSON DEFAULT NULL COMMENT 'Ex: region_mapping, source_mapping',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para round-robin (último usuário que recebeu lead)
CREATE TABLE IF NOT EXISTS `lead_distribution_state` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rule_id` INT(11) NOT NULL,
  `last_user_id` INT(11) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rule_id` (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. VISITAS E MEDIÇÕES
-- ============================================
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL,
  `seller_id` INT(11) DEFAULT NULL COMMENT 'Vendedor',
  `technician_id` INT(11) DEFAULT NULL COMMENT 'Técnico / Medidor',
  `address` TEXT NULL,
  `notes` TEXT NULL,
  `status` ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_scheduled_at` (`scheduled_at`),
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_technician_id` (`technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `measurements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `visit_id` INT(11) NOT NULL,
  `lead_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `area_sqft` DECIMAL(10,2) NULL COMMENT 'Metragem em pés quadrados',
  `rooms` VARCHAR(255) NULL COMMENT 'Cômodos/áreas',
  `technical_notes` TEXT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_visit_id` (`visit_id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `visit_attachments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `visit_id` INT(11) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` ENUM('photo','video','document') DEFAULT 'photo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_visit_id` (`visit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. ORÇAMENTOS E PROPOSTAS
-- ============================================
CREATE TABLE IF NOT EXISTS `quotes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `version` INT(11) DEFAULT 1,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `labor_amount` DECIMAL(12,2) DEFAULT 0,
  `materials_amount` DECIMAL(12,2) DEFAULT 0,
  `margin_percent` DECIMAL(5,2) DEFAULT NULL,
  `status` ENUM('draft','sent','viewed','approved','rejected') DEFAULT 'draft',
  `sent_at` DATETIME NULL,
  `viewed_at` DATETIME NULL,
  `approved_at` DATETIME NULL,
  `pdf_path` VARCHAR(500) NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `quote_id` INT(11) NOT NULL,
  `floor_type` VARCHAR(100) NOT NULL COMMENT 'Vinyl, Hardwood, Tile, etc.',
  `area_sqft` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(12,2) NOT NULL,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_quote_id` (`quote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. CONTRATOS E FECHAMENTO
-- ============================================
CREATE TABLE IF NOT EXISTS `contracts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `quote_id` INT(11) DEFAULT NULL,
  `closed_amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('cash','financing','check','card','other') DEFAULT NULL,
  `installments` INT(11) DEFAULT 1,
  `start_date` DATE NULL COMMENT 'Data início obra',
  `end_date` DATE NULL COMMENT 'Data prevista término',
  `responsible_id` INT(11) DEFAULT NULL COMMENT 'Responsável pela obra',
  `contract_path` VARCHAR(500) NULL,
  `signed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_quote_id` (`quote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. PÓS-VENDA / OBRA (complemento em projects já existe)
-- ============================================
CREATE TABLE IF NOT EXISTS `project_documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `doc_type` VARCHAR(50) NULL COMMENT 'contrato, foto_entrega, etc.',
  `uploaded_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_issues` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('open','in_progress','resolved') DEFAULT 'open',
  `reported_by` INT(11) DEFAULT NULL,
  `resolved_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `delivery_checklists` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `completed` TINYINT(1) DEFAULT 0,
  `completed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. AUTOMAÇÕES E WORKFLOWS
-- ============================================
CREATE TABLE IF NOT EXISTS `workflows` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `trigger_type` ENUM('stage_change','inactivity','new_lead','schedule') NOT NULL,
  `trigger_config` JSON DEFAULT NULL,
  `actions` JSON NOT NULL COMMENT 'Lista de ações: email, whatsapp, task, stage_change',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scheduled_followups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `channel` ENUM('email','whatsapp','phone') DEFAULT 'whatsapp',
  `message_template` TEXT NULL,
  `sent_at` DATETIME NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_scheduled_at` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. PREPARAÇÃO PARA IA (eventos e logs)
-- ============================================
CREATE TABLE IF NOT EXISTS `interaction_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `entity_type` ENUM('lead','customer','project') NOT NULL,
  `entity_id` INT(11) NOT NULL,
  `event_type` VARCHAR(80) NOT NULL COMMENT 'stage_change, email_sent, call, etc.',
  `payload` JSON DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. TAREFAS (para criação automática ao entrar lead)
-- ============================================
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `due_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_assigned_to` (`assigned_to`),
  INDEX `idx_due_at` (`due_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Atualizar leads: definir pipeline_stage_id padrão para estágio 1
-- ============================================
UPDATE `leads` SET `pipeline_stage_id` = 1 WHERE `pipeline_stage_id` IS NULL AND `status` IN ('new','contacted','qualified','proposal','negotiation');
UPDATE `leads` SET `pipeline_stage_id` = 8 WHERE `status` = 'closed_won';
UPDATE `leads` SET `pipeline_stage_id` = 9 WHERE `status` = 'closed_lost';
