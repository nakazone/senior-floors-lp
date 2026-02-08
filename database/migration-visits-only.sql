-- ============================================
-- Tabela VISITAS (para agendar visita na aba do lead)
-- Execute no phpMyAdmin no mesmo banco da tabela leads.
-- ============================================
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL,
  `seller_id` INT(11) DEFAULT NULL COMMENT 'Quem fará a visita',
  `technician_id` INT(11) DEFAULT NULL,
  `address` TEXT NULL,
  `notes` TEXT NULL,
  `status` ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_scheduled_at` (`scheduled_at`),
  INDEX `idx_seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
