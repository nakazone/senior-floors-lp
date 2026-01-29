-- ============================================
-- Responsável pelo Lead + Histórico de contatos (activities)
-- Execute no phpMyAdmin no mesmo banco da tabela leads.
-- Se der "Duplicate column" ou "Table already exists", ignore.
-- ============================================

-- 1. Coluna owner_id em leads (responsável pelo lead)
ALTER TABLE `leads`
  ADD COLUMN `owner_id` INT(11) NULL DEFAULT NULL AFTER `status`;
ALTER TABLE `leads`
  ADD INDEX `idx_owner_id` (`owner_id`);

-- 2. Tabela activities (histórico de conversas/contatos: ligação, email, WhatsApp, reunião, nota)
CREATE TABLE IF NOT EXISTS `activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `activity_type` ENUM('email_sent', 'whatsapp_message', 'phone_call', 'meeting_scheduled', 'site_visit', 'proposal_sent', 'note', 'status_change', 'assignment', 'other') DEFAULT 'note',
  `subject` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `activity_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `user_id` INT(11) DEFAULT NULL,
  `owner_id` INT(11) DEFAULT NULL,
  `related_to` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_activity_date` (`activity_date`),
  INDEX `idx_activity_lead_date` (`lead_id`, `activity_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Histórico de atribuição (quem encaminhou para quem)
CREATE TABLE IF NOT EXISTS `assignment_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `from_user_id` INT(11) DEFAULT NULL,
  `to_user_id` INT(11) NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `assigned_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
