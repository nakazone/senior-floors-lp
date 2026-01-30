-- ============================================
-- Senior Floors CRM - Modelo Completo (Especificação)
-- Pipeline 11 estágios, auditoria, logs de status, lead_qualification
-- Execute após schema-v3 e migration-crm-completo (ou migration-pipeline-only)
-- ============================================

-- ============================================
-- 1. PIPELINE STAGES - 11 estágios (especificação)
-- ============================================
-- Garantir tabela existe
CREATE TABLE IF NOT EXISTS `pipeline_stages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `order_num` INT(11) DEFAULT 0,
  `sla_hours` INT(11) DEFAULT NULL,
  `required_fields` JSON DEFAULT NULL COMMENT 'Campos obrigatórios para avançar',
  `is_closed` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  INDEX `idx_order` (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir estágios conforme especificação (11 estágios). Se já existir slug, atualiza nome/order.
INSERT INTO `pipeline_stages` (`name`, `slug`, `order_num`, `sla_hours`, `is_closed`) VALUES
('Lead Recebido', 'lead_received', 1, 24, 0),
('Contato Realizado', 'contact_made', 2, 48, 0),
('Qualificado', 'qualified', 3, 72, 0),
('Visita Agendada', 'visit_scheduled', 4, 168, 0),
('Medição Realizada', 'measurement_done', 5, 72, 0),
('Proposta Criada', 'proposal_created', 6, 72, 0),
('Proposta Enviada', 'proposal_sent', 7, 168, 0),
('Em Negociação', 'negotiation', 8, 336, 0),
('Fechado - Ganhou', 'closed_won', 9, NULL, 1),
('Fechado - Perdido', 'closed_lost', 10, NULL, 1),
('Produção / Obra', 'production', 11, NULL, 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `order_num` = VALUES(`order_num`), `is_closed` = VALUES(`is_closed`);

-- ============================================
-- 2. LEAD_QUALIFICATION (dados de qualificação por lead)
-- ============================================
CREATE TABLE IF NOT EXISTS `lead_qualification` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `property_type` VARCHAR(50) DEFAULT NULL,
  `service_type` VARCHAR(100) DEFAULT NULL,
  `estimated_area` DECIMAL(10,2) DEFAULT NULL,
  `estimated_budget` DECIMAL(12,2) DEFAULT NULL,
  `urgency` VARCHAR(30) DEFAULT NULL,
  `decision_maker` TINYINT(1) DEFAULT NULL COMMENT '1=Sim, 0=Não',
  `payment_type` VARCHAR(30) DEFAULT NULL,
  `score` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_id` (`lead_id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. LEAD_STATUS_CHANGE_LOG (auditoria de mudança de status)
-- ============================================
CREATE TABLE IF NOT EXISTS `lead_status_change_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `from_stage_id` INT(11) DEFAULT NULL,
  `to_stage_id` INT(11) NOT NULL,
  `changed_by` INT(11) DEFAULT NULL COMMENT 'user_id',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_to_stage` (`to_stage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. AUDIT_LOG (alterações em propostas, valores, etc.)
-- ============================================
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'lead, proposal, contract, project',
  `entity_id` INT(11) NOT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'status_change, value_change, create, update',
  `field_name` VARCHAR(100) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. INTERACTIONS (alias ou complemento a activities - tipo call, whatsapp, email, visit)
-- ============================================
CREATE TABLE IF NOT EXISTS `interactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `type` ENUM('call','whatsapp','email','visit') NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_type` (`type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. MEASUREMENTS - colunas risks e photos (execute uma por vez; ignore "Duplicate column")
-- ============================================
-- ALTER TABLE `measurements` ADD COLUMN `final_area` DECIMAL(10,2) NULL AFTER `area_sqft`;
-- ALTER TABLE `measurements` ADD COLUMN `risks` TEXT NULL AFTER `technical_notes`;
-- ALTER TABLE `measurements` ADD COLUMN `photos` JSON NULL AFTER `risks`;

-- ============================================
-- 7. QUOTES - valid_until (proposals)
-- ============================================
-- ALTER TABLE `quotes` ADD COLUMN `valid_until` DATE NULL AFTER `status`;

-- ============================================
-- 8. PROJECTS - contract_id (vínculo contrato -> obra)
-- ============================================
-- ALTER TABLE `projects` ADD COLUMN `contract_id` INT(11) NULL AFTER `lead_id`;
-- ALTER TABLE `projects` ADD INDEX `idx_contract_id` (`contract_id`);

-- ============================================
-- 9. USERS - role (admin, manager, sales, operational)
-- ============================================
-- Mapeamento: manager=project_manager, sales=sales_rep, operational=support
-- Se quiser ENUM exato: ALTER TABLE users MODIFY role ENUM('admin','manager','sales','operational')...
-- Mantendo compatibilidade: project_manager=manager, sales_rep=sales, support=operational

-- ============================================
-- 10. Atualizar leads sem pipeline_stage_id para estágio 1
-- ============================================
UPDATE `leads` SET `pipeline_stage_id` = (SELECT id FROM pipeline_stages WHERE slug = 'lead_received' LIMIT 1) WHERE `pipeline_stage_id` IS NULL;
