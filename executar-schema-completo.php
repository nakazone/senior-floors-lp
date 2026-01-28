<?php
/**
 * Script para Executar Schema Completo v3.0 Automaticamente
 * 
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo para: public_html/executar-schema-completo.php
 * 2. Acesse: https://seudominio.com/executar-schema-completo.php
 * 3. O script vai executar o schema completo automaticamente
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
    <title>Executar Schema Completo v3.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
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
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
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
            margin-right: 10px;
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
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
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
            max-height: 300px;
            overflow-y: auto;
        }
        .table-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .table-item {
            background: #e7f3ff;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
        }
        .table-item.exists {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Executar Schema Completo v3.0</h1>

        <?php
        if (!isDatabaseConfigured()) {
            echo '<div class="error">';
            echo '<strong>❌ Erro:</strong> Banco de dados não está configurado.<br>';
            echo 'Configure o arquivo <code>config/database.php</code> primeiro.';
            echo '</div>';
            exit;
        }

        $execution_started = false;
        $execution_success = false;
        $errors = [];
        $tables_created = [];
        $existing_tables = [];

        try {
            $pdo = getDBConnection();
            
            if (!$pdo) {
                throw new Exception("Não foi possível conectar ao banco de dados");
            }

            // Verificar tabelas existentes
            $stmt = $pdo->query("SHOW TABLES");
            $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Ler arquivo SQL
            $sql_file = __DIR__ . '/database/schema-v3-completo.sql';
            
            if (!file_exists($sql_file)) {
                echo '<div class="error">';
                echo '<strong>❌ Erro:</strong> Arquivo SQL não encontrado.<br>';
                echo 'Arquivo esperado: <code>database/schema-v3-completo.sql</code><br>';
                echo 'Faça upload do arquivo SQL para o servidor ou execute manualmente via phpMyAdmin.';
                echo '</div>';
                exit;
            }

            $sql_content = file_get_contents($sql_file);

            // Executar schema se solicitado
            if (isset($_POST['execute'])) {
                $execution_started = true;
                
                echo '<div class="step">';
                echo '<strong>🔄 Executando schema completo...</strong><br><br>';

                // Dividir em queries individuais
                $queries = array_filter(
                    array_map('trim', explode(';', $sql_content)),
                    function($query) {
                        return !empty($query) && 
                               !preg_match('/^\s*--/', $query) && 
                               !preg_match('/^\s*\/\*/', $query);
                    }
                );

                $success_count = 0;
                $error_count = 0;

                foreach ($queries as $query) {
                    if (empty(trim($query))) continue;
                    
                    try {
                        $pdo->exec($query);
                        $success_count++;
                        
                        // Detectar criação de tabela
                        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $query, $matches)) {
                            $table_name = $matches[1];
                            $tables_created[] = $table_name;
                        }
                    } catch (PDOException $e) {
                        // Ignorar erros de "table already exists"
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            $errors[] = [
                                'query' => substr($query, 0, 100) . '...',
                                'error' => $e->getMessage()
                            ];
                            $error_count++;
                        }
                    }
                }

                echo "✅ Queries executadas com sucesso: <strong>$success_count</strong><br>";
                if ($error_count > 0) {
                    echo "⚠️ Queries com erro (ignoradas): <strong>$error_count</strong><br>";
                }
                
                if (!empty($tables_created)) {
                    echo "<br><strong>Tabelas criadas/verificadas:</strong><br>";
                    echo '<div class="table-list">';
                    foreach ($tables_created as $table) {
                        echo '<div class="table-item exists">✅ ' . htmlspecialchars($table) . '</div>';
                    }
                    echo '</div>';
                }

                $execution_success = $success_count > 0;

                echo '</div>';
            }

            // Verificar tabelas após execução
            $stmt = $pdo->query("SHOW TABLES");
            $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $expected_tables = [
                'leads', 'customers', 'projects', 'activities', 
                'assignment_history', 'coupons', 'coupon_usage',
                'lead_tags', 'customer_tags', 'project_tags',
                'lead_notes', 'customer_notes', 'project_notes', 'users'
            ];

        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Erro:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>

        <?php if ($execution_success): ?>
            <div class="success">
                <strong>🎉 Schema executado com sucesso!</strong><br><br>
                Todas as tabelas foram criadas/verificadas.<br><br>
                <strong>Próximos passos:</strong><br>
                1. Teste criar um customer: <code>system.php?module=customers</code><br>
                2. Teste criar um project: <code>system.php?module=projects</code><br>
                3. Teste criar um coupon: <code>system.php?module=coupons</code><br>
                4. <strong>DELETE este arquivo</strong> por segurança<br>
            </div>
        <?php elseif (!$execution_started): ?>
            <div class="info">
                <strong>📋 Status Atual:</strong><br><br>
                
                <?php if (!empty($existing_tables)): ?>
                    <strong>Tabelas existentes:</strong>
                    <div class="table-list">
                        <?php foreach ($existing_tables as $table): ?>
                            <div class="table-item exists">✅ <?php echo htmlspecialchars($table); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Nenhuma tabela encontrada. O banco está vazio.</p>
                <?php endif; ?>
            </div>

            <div class="step">
                <strong>📋 O que este script faz:</strong><br>
                - Cria todas as 14 tabelas do CRM completo<br>
                - Inclui tabelas: leads, customers, projects, activities, coupons, etc.<br>
                - Usa <code>CREATE TABLE IF NOT EXISTS</code> para não sobrescrever dados existentes<br>
                - Preserva dados já existentes nas tabelas<br><br>

                <strong>⚠️ Importante:</strong><br>
                - Este script é seguro e não apaga dados existentes<br>
                - Se uma tabela já existe, ela será ignorada<br>
                - Apenas tabelas novas serão criadas<br>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="warning">
                    <strong>⚠️ Avisos durante execução:</strong><br>
                    <?php foreach ($errors as $error): ?>
                        <pre><?php echo htmlspecialchars($error['error']); ?></pre>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <button type="submit" name="execute" class="btn btn-success" onclick="return confirm('Tem certeza que deseja executar o schema completo? Isso vai criar todas as tabelas do CRM.');">
                    🚀 Executar Schema Completo
                </button>
            </form>
        <?php endif; ?>

        <?php if (!empty($all_tables)): ?>
            <div class="step">
                <strong>📊 Tabelas no Banco de Dados:</strong>
                <div class="table-list">
                    <?php 
                    foreach ($expected_tables as $expected): 
                        $exists = in_array($expected, $all_tables);
                    ?>
                        <div class="table-item <?php echo $exists ? 'exists' : ''; ?>">
                            <?php echo $exists ? '✅' : '❌'; ?> 
                            <?php echo htmlspecialchars($expected); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="step">
            <strong>🔒 Segurança:</strong><br>
            Após executar o schema, <strong>DELETE este arquivo</strong> do servidor por segurança.<br>
            Arquivo: <code>public_html/executar-schema-completo.php</code>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <strong>Alternativa Manual:</strong><br>
            Se preferir executar manualmente, use o arquivo:<br>
            <code>database/schema-v3-completo.sql</code><br>
            Execute via phpMyAdmin → Aba SQL
        </div>
    </div>
</body>
</html>
