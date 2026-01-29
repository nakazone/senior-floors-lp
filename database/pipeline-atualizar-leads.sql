-- Coloca todos os leads no estágio "Lead recebido" (id = 1)
-- Execute no phpMyAdmin se os leads ainda não aparecem no Pipeline
UPDATE `leads` SET `pipeline_stage_id` = 1 WHERE `pipeline_stage_id` IS NULL;
