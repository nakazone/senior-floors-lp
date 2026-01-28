<?php
/**
 * Script para Executar Migration v2→v3 Automaticamente
 * 
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo para: public_html/executar-migration.php
 * 2. Acesse: https://seudominio.com/executar-migration.php
 * 3. O script vai executar a migration automaticamente
 * 4. DELETE este arquivo após usar (por segurança)
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executar Migration v2→v3</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .step {
            background: #f9f9f9;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Executar Migration v2→v3</h1>

        <?php
        if (!isDatabaseConfigured()) {
            echo '<div class="error">';
            echo '<strong>❌ Erro:</strong> Banco de dados não está configurado.<br>';
            echo 'Configure o arquivo <code>config/database.php</code> primeiro.';
            echo '</div>';
            exit;
        }

        // Verificar se já foi executado
        $check_executed = false;
        $migration_success = false;
        $errors = [];

        try {
            $pdo = getDBConnection();
            
            if (!$pdo) {
                throw new Exception("Não foi possível conectar ao banco de dados");
            }

            // Verificar se a tabela projects existe
            $stmt = $pdo->query("SHOW TABLES LIKE 'projects'");
            $table_exists = $stmt->rowCount() > 0;

            if (!$table_exists) {
                echo '<div class="error">';
                echo '<strong>❌ Erro:</strong> A tabela <code>projects</code> não existe.<br>';
                echo 'Execute primeiro o schema completo: <code>database/schema-v3-completo.sql</code>';
                echo '</div>';
                exit;
            }

            // Verificar se o campo já existe
            $stmt = $pdo->query("SHOW COLUMNS FROM `projects` LIKE 'post_service_status'");
            $field_exists = $stmt->rowCount() > 0;

            if ($field_exists) {
                echo '<div class="warning">';
                echo '<strong>⚠️ Atenção:</strong> O campo <code>post_service_status</code> já existe!<br>';
                echo 'A migration já foi executada anteriormente.';
                echo '</div>';
                $check_executed = true;
            }

            // Executar migration se ainda não foi executada
            if (!$check_executed && isset($_POST['execute'])) {
                echo '<div class="step">';
                echo '<strong>🔄 Executando migration...</strong><br><br>';

                try {
                    // Adicionar campo
                    $pdo->exec("
                        ALTER TABLE `projects` 
                        ADD COLUMN `post_service_status` ENUM(
                            'installation_scheduled',
                            'installation_completed', 
                            'follow_up_sent',
                            'review_requested',
                            'warranty_active'
                        ) DEFAULT NULL COMMENT 'Status de pós-atendimento' AFTER `status`
                    ");

                    echo '✅ Campo <code>post_service_status</code> adicionado com sucesso!<br>';

                    // Adicionar índice
                    try {
                        $pdo->exec("CREATE INDEX `idx_post_service_status` ON `projects`(`post_service_status`)");
                        echo '✅ Índice criado com sucesso!<br>';
                    } catch (PDOException $e) {
                        // Índice pode já existir, não é crítico
                        if (strpos($e->getMessage(), 'Duplicate key') === false) {
                            throw $e;
                        }
                        echo '⚠️ Índice já existe (não é problema).<br>';
                    }

                    $migration_success = true;

                } catch (PDOException $e) {
                    $errors[] = $e->getMessage();
                    echo '<div class="error">';
                    echo '<strong>❌ Erro ao executar migration:</strong><br>';
                    echo htmlspecialchars($e->getMessage());
                    echo '</div>';
                }

                echo '</div>';
            }

        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Erro:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>

        <?php if ($migration_success): ?>
            <div class="success">
                <strong>🎉 Migration executada com sucesso!</strong><br><br>
                O campo <code>post_service_status</code> foi adicionado à tabela <code>projects</code>.<br><br>
                <strong>Próximos passos:</strong><br>
                1. Teste criar um novo project no sistema<br>
                2. Verifique se o campo "Post-Service Status" aparece<br>
                3. <strong>DELETE este arquivo</strong> por segurança<br>
            </div>
        <?php elseif (!$check_executed): ?>
            <div class="step">
                <strong>📋 O que esta migration faz:</strong><br>
                - Adiciona o campo <code>post_service_status</code> na tabela <code>projects</code><br>
                - Permite gerenciar status de pós-atendimento nos projetos<br>
                - Valores possíveis: Installation Scheduled, Installation Completed, Follow-up Sent, Review Requested, Warranty Active<br><br>

                <strong>⚠️ Importante:</strong><br>
                - Faça backup do banco antes de executar (recomendado)<br>
                - Esta migration é segura e não modifica dados existentes<br>
                - Apenas adiciona um novo campo opcional<br>
            </div>

            <form method="POST">
                <button type="submit" name="execute" class="btn" onclick="return confirm('Tem certeza que deseja executar a migration?');">
                    🚀 Executar Migration
                </button>
            </form>
        <?php endif; ?>

        <?php if ($check_executed): ?>
            <div class="step">
                <strong>✅ Status:</strong> Migration já foi executada anteriormente.<br><br>
                Não é necessário executar novamente.
            </div>
        <?php endif; ?>

        <div class="step">
            <strong>🔒 Segurança:</strong><br>
            Após executar a migration, <strong>DELETE este arquivo</strong> do servidor por segurança.<br>
            Arquivo: <code>public_html/executar-migration.php</code>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <strong>Alternativa Manual:</strong><br>
            Se preferir executar manualmente, use o arquivo:<br>
            <code>database/migration-v2-to-v3-simples.sql</code><br>
            Execute via phpMyAdmin → Aba SQL
        </div>
    </div>
</body>
</html>
