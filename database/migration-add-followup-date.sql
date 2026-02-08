-- ============================================
-- Próximo follow-up no Lead
-- Execute no phpMyAdmin no mesmo banco da tabela leads.
-- Se der "Duplicate column", a coluna já existe — ignore.
-- ============================================

ALTER TABLE `leads`
  ADD COLUMN `next_follow_up_at` DATETIME NULL DEFAULT NULL COMMENT 'Data/hora do próximo follow-up' AFTER `updated_at`;

ALTER TABLE `leads`
  ADD INDEX `idx_next_follow_up_at` (`next_follow_up_at`);
