-- ============================================
-- QUOTES MODULE (Invoice2go-style)
-- Full lifecycle: draft → sent → viewed → accepted/declined/expired
-- Run after migration-crm-completo.sql (quotes + quote_items exist).
-- If you get "Duplicate column name" errors, that column already exists - skip that line.
-- ============================================

-- 1. QUOTES: new columns
ALTER TABLE `quotes` ADD COLUMN `quote_number` VARCHAR(50) NULL UNIQUE COMMENT 'Sequential display number e.g. Q-2024-0001';
ALTER TABLE `quotes` ADD COLUMN `issue_date` DATE NULL;
ALTER TABLE `quotes` ADD COLUMN `expiration_date` DATE NULL;
ALTER TABLE `quotes` ADD COLUMN `subtotal` DECIMAL(12,2) NULL;
ALTER TABLE `quotes` ADD COLUMN `discount_type` ENUM('percentage','fixed') DEFAULT 'percentage';
ALTER TABLE `quotes` ADD COLUMN `discount_value` DECIMAL(12,2) DEFAULT 0;
ALTER TABLE `quotes` ADD COLUMN `tax_total` DECIMAL(12,2) DEFAULT 0;
ALTER TABLE `quotes` ADD COLUMN `notes` TEXT NULL COMMENT 'Public notes on quote';
ALTER TABLE `quotes` ADD COLUMN `internal_notes` TEXT NULL COMMENT 'Admin only';
ALTER TABLE `quotes` ADD COLUMN `currency` VARCHAR(3) DEFAULT 'USD';
ALTER TABLE `quotes` ADD COLUMN `public_token` VARCHAR(64) NULL UNIQUE COMMENT 'Secure token for public view link';
ALTER TABLE `quotes` ADD COLUMN `declined_at` DATETIME NULL;
ALTER TABLE `quotes` ADD COLUMN `decline_reason` TEXT NULL;

-- Extend status enum
ALTER TABLE `quotes` MODIFY COLUMN `status` ENUM('draft','sent','viewed','approved','rejected','accepted','declined','expired') DEFAULT 'draft';

-- 2. QUOTE_ITEMS: new columns (type, name, description, quantity, unit_price, tax_rate, total)
ALTER TABLE `quote_items` ADD COLUMN `type` ENUM('material','labor','service') DEFAULT 'material';
ALTER TABLE `quote_items` ADD COLUMN `name` VARCHAR(255) NULL;
ALTER TABLE `quote_items` ADD COLUMN `description` TEXT NULL;
ALTER TABLE `quote_items` ADD COLUMN `quantity` DECIMAL(12,2) DEFAULT 1;
ALTER TABLE `quote_items` ADD COLUMN `unit_price` DECIMAL(12,2) NULL;
ALTER TABLE `quote_items` ADD COLUMN `tax_rate` DECIMAL(5,2) DEFAULT 0;
ALTER TABLE `quote_items` ADD COLUMN `total` DECIMAL(12,2) NULL;

-- 3. QUOTE_ACTIVITY_LOG
CREATE TABLE IF NOT EXISTS `quote_activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `quote_id` INT(11) NOT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'created, sent, viewed, accepted, declined, edited',
  `performed_by` VARCHAR(50) DEFAULT NULL COMMENT 'user id or client',
  `performed_by_type` ENUM('user','client','system') DEFAULT 'user',
  `metadata` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_quote_id` (`quote_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CUSTOMERS: tax_id (Client spec)
ALTER TABLE `customers` ADD COLUMN `tax_id` VARCHAR(50) NULL COMMENT 'CPF/CNPJ or tax ID';
