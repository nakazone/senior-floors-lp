-- ============================================
-- Campos de Qualificação (metragem, motivo desqualificação)
-- Execute no phpMyAdmin no mesmo banco da tabela leads.
-- Se der "Duplicate column", a coluna já existe — ignore.
-- ============================================

ALTER TABLE `leads`
  ADD COLUMN `estimated_area` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Metragem estimada (ex: 50 m²)' AFTER `service_type`;

ALTER TABLE `leads`
  ADD COLUMN `disqualification_reason` TEXT NULL DEFAULT NULL COMMENT 'Motivo obrigatório quando lead é desqualificado' AFTER `lead_score`;
