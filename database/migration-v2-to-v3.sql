-- ============================================
-- Migration: v2.0 → v3.0
-- Adiciona campo post_service_status na tabela projects
-- ============================================

-- Adicionar campo post_service_status na tabela projects
ALTER TABLE `projects` 
ADD COLUMN IF NOT EXISTS `post_service_status` ENUM(
    'installation_scheduled',
    'installation_completed', 
    'follow_up_sent',
    'review_requested',
    'warranty_active'
) DEFAULT NULL COMMENT 'Status de pós-atendimento' AFTER `status`;

-- Adicionar índice para o novo campo
CREATE INDEX IF NOT EXISTS `idx_post_service_status` ON `projects`(`post_service_status`);
