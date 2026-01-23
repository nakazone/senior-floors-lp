-- ============================================
-- Senior Floors - Database Schema
-- FASE 1 - MÓDULO 01: Central de Leads
-- 
-- INSTRUÇÕES:
-- 1. Acesse phpMyAdmin no Hostinger
-- 2. Selecione seu banco de dados
-- 3. Clique na aba "SQL"
-- 4. Cole este código completo
-- 5. Clique em "Go" ou "Executar"
-- ============================================

-- Tabela principal de leads
CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `zipcode` VARCHAR(10) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `source` VARCHAR(50) DEFAULT 'LP' COMMENT 'LP, Website, Ads, etc.',
  `form_type` VARCHAR(50) DEFAULT 'contact-form' COMMENT 'hero-form, contact-form',
  `status` ENUM('new', 'contacted', 'qualified', 'proposal', 'closed_won', 'closed_lost') DEFAULT 'new',
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_source` (`source`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tags (para MÓDULO 05)
CREATE TABLE IF NOT EXISTS `lead_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `tag` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  INDEX `idx_tag` (`tag`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de observações internas (para MÓDULO 04)
CREATE TABLE IF NOT EXISTS `lead_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11) NOT NULL,
  `note` TEXT NOT NULL,
  `created_by` VARCHAR(100) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_lead_id` (`lead_id`),
  FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
