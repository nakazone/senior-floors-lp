-- ============================================
-- Migration: v2.0 → v3.0 - SIMPLIFICADA
-- Adiciona campo post_service_status na tabela projects
-- ============================================
-- 
-- INSTRUÇÕES:
-- 1. Acesse phpMyAdmin no Hostinger
-- 2. Selecione seu banco de dados
-- 3. Clique na aba "SQL"
-- 4. Cole este código completo
-- 5. Clique em "Go" ou "Executar"
-- ============================================

-- Verificar se a tabela projects existe
-- Se não existir, você precisa executar o schema completo primeiro

-- Adicionar campo post_service_status na tabela projects
ALTER TABLE `projects` 
ADD COLUMN `post_service_status` ENUM(
    'installation_scheduled',
    'installation_completed', 
    'follow_up_sent',
    'review_requested',
    'warranty_active'
) DEFAULT NULL COMMENT 'Status de pós-atendimento' AFTER `status`;

-- Adicionar índice para melhorar performance
CREATE INDEX `idx_post_service_status` ON `projects`(`post_service_status`);

-- ============================================
-- FIM DA MIGRATION
-- ============================================
-- 
-- Verificação:
-- 1. Clique na tabela 'projects'
-- 2. Clique na aba "Structure"
-- 3. Procure por 'post_service_status'
-- 4. Se aparecer, a migration foi bem-sucedida! ✅
-- ============================================
