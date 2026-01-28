-- ============================================
-- Migration: v1.0 -> v2.0
-- Adiciona novas colunas e tabelas sem perder dados existentes
-- ============================================

-- Adicionar novas colunas na tabela leads (se não existirem)
ALTER TABLE `leads` 
ADD COLUMN IF NOT EXISTS `customer_type` ENUM('residential', 'commercial', 'property_manager', 'investor', 'builder') DEFAULT 'residential' AFTER `priority`,
ADD COLUMN IF NOT EXISTS `owner_id` INT(11) DEFAULT NULL COMMENT 'Sales rep responsável' AFTER `customer_type`;

-- Adicionar índices se não existirem
CREATE INDEX IF NOT EXISTS `idx_owner_id` ON `leads`(`owner_id`);
CREATE INDEX IF NOT EXISTS `idx_customer_type` ON `leads`(`customer_type`);

-- Criar tabelas novas (já estão no schema-v2-completo.sql)
-- Este arquivo é apenas para migração incremental
