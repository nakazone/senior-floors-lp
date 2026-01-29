-- ============================================
-- Pipeline apenas: estágios + coluna em leads
-- Execute no phpMyAdmin (Hostinger) no mesmo banco onde está a tabela leads.
-- Se der erro "Duplicate column" ou "Duplicate key", ignore (já existe).
-- ============================================

-- 1. Tabela de estágios do Kanban
CREATE TABLE IF NOT EXISTS `pipeline_stages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `order_num` INT(11) DEFAULT 0,
  `sla_hours` INT(11) DEFAULT NULL,
  `is_closed` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  INDEX `idx_order` (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Inserir estágios padrão (se já existirem, só atualiza o nome)
INSERT INTO `pipeline_stages` (`name`, `slug`, `order_num`, `sla_hours`, `is_closed`) VALUES
('Lead recebido', 'lead_received', 1, 24, 0),
('Contato feito', 'contact_made', 2, 48, 0),
('Qualificado', 'qualified', 3, 72, 0),
('Visita / Medição agendada', 'visit_scheduled', 4, 168, 0),
('Medição realizada', 'measurement_done', 5, 72, 0),
('Orçamento enviado', 'quote_sent', 6, 168, 0),
('Negociação', 'negotiation', 7, 336, 0),
('Fechado - Ganhou', 'closed_won', 8, NULL, 1),
('Fechado - Perdeu', 'closed_lost', 9, NULL, 1),
('Pós-venda', 'post_sale', 10, NULL, 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 3. Coluna em leads (se der erro "Duplicate column name", a coluna já existe — pule para o passo 4)
ALTER TABLE `leads`
  ADD COLUMN `pipeline_stage_id` INT(11) NULL AFTER `status`;
ALTER TABLE `leads`
  ADD INDEX `idx_pipeline_stage_id` (`pipeline_stage_id`);

-- 4. Colocar todos os leads no estágio "Lead recebido" (id = 1) — execute sempre
UPDATE `leads` SET `pipeline_stage_id` = 1 WHERE `pipeline_stage_id` IS NULL;
