<?php
/**
 * Teste Completo de Banco de Dados
 * Use este arquivo para diagnosticar problemas de conexão
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Banco de Dados - Senior Floors</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #1a2036; }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #1a2036;
            background: #f8f9fa;
        }
        .success { color: #48bb78; font-weight: bold; }
        .error { color: #e53e3e; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #4299e1; }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste Completo de Banco de Dados</h1>
        
        <?php
        // Teste 1: Verificar se arquivo existe
        echo "<div class='test-section'>";
        echo "<h2>Teste 1: Arquivo de Configuração</h2>";
        
        $config_file = __DIR__ . '/config/database.php';
        if (file_exists($config_file)) {
            echo "<p class='success'>✅ Arquivo config/database.php existe</p>";
            require_once $config_file;
        } else {
            echo "<p class='error'>❌ Arquivo config/database.php NÃO existe</p>";
            echo "<p>Caminho esperado: <code>$config_file</code></p>";
            exit;
        }
        echo "</div>";
        
        // Teste 2: Verificar constantes
        echo "<div class='test-section'>";
        echo "<h2>Teste 2: Constantes Definidas</h2>";
        
        $constants = [
            'DB_HOST' => defined('DB_HOST') ? DB_HOST : null,
            'DB_NAME' => defined('DB_NAME') ? DB_NAME : null,
            'DB_USER' => defined('DB_USER') ? DB_USER : null,
            'DB_PASS' => defined('DB_PASS') ? DB_PASS : null,
            'DB_CHARSET' => defined('DB_CHARSET') ? DB_CHARSET : null
        ];
        
        foreach ($constants as $name => $value) {
            if ($value !== null) {
                if ($name === 'DB_PASS') {
                    echo "<p class='info'>✅ $name: " . (empty($value) ? '<span class="error">VAZIO</span>' : '***' . substr($value, -4)) . "</p>";
                } else {
                    echo "<p class='info'>✅ $name: <code>" . htmlspecialchars($value) . "</code></p>";
                }
            } else {
                echo "<p class='error'>❌ $name: NÃO definida</p>";
            }
        }
        echo "</div>";
        
        // Teste 3: Verificar se está configurado
        echo "<div class='test-section'>";
        echo "<h2>Teste 3: Verificação de Configuração</h2>";
        
        if (function_exists('isDatabaseConfigured')) {
            $is_configured = isDatabaseConfigured();
            if ($is_configured) {
                echo "<p class='success'>✅ isDatabaseConfigured() retorna TRUE</p>";
            } else {
                echo "<p class='error'>❌ isDatabaseConfigured() retorna FALSE</p>";
                echo "<p class='warning'>⚠️ Isso significa que os valores ainda são padrão ou estão vazios</p>";
                echo "<p>Verifique se você editou <code>config/database.php</code> com valores reais</p>";
                
                // Mostrar valores atuais
                echo "<h3>Valores Atuais:</h3>";
                echo "<pre>";
                echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'não definido') . "\n";
                echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'não definido') . "\n";
                echo "DB_PASS: " . (defined('DB_PASS') ? (empty(DB_PASS) ? 'VAZIO' : '***') : 'não definido') . "\n";
                echo "\n";
                echo "Valores padrão que NÃO funcionam:\n";
                echo "- DB_NAME = 'senior_floors_db'\n";
                echo "- DB_USER = 'seu_usuario'\n";
                echo "- DB_PASS = 'sua_senha'\n";
                echo "</pre>";
            }
        } else {
            echo "<p class='error'>❌ Função isDatabaseConfigured() não existe</p>";
        }
        echo "</div>";
        
        // Teste 4: Tentar conectar
        echo "<div class='test-section'>";
        echo "<h2>Teste 4: Conexão com Banco de Dados</h2>";
        
        if (function_exists('getDBConnection')) {
            try {
                $pdo = getDBConnection();
                
                if ($pdo) {
                    echo "<p class='success'>✅ Conexão estabelecida com sucesso!</p>";
                    
                    // Teste 5: Verificar tabelas
                    echo "<div class='test-section'>";
                    echo "<h2>Teste 5: Verificar Tabelas</h2>";
                    
                    $tables = ['leads', 'lead_tags', 'lead_notes'];
                    $all_tables_exist = true;
                    
                    foreach ($tables as $table) {
                        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                        if ($stmt->rowCount() > 0) {
                            echo "<p class='success'>✅ Tabela '$table' existe</p>";
                            
                            // Contar registros
                            $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
                            $count = $count_stmt->fetch()['total'];
                            echo "<p class='info'>   → Registros: $count</p>";
                        } else {
                            echo "<p class='error'>❌ Tabela '$table' NÃO existe</p>";
                            $all_tables_exist = false;
                        }
                    }
                    
                    if (!$all_tables_exist) {
                        echo "<p class='warning'>⚠️ Algumas tabelas não existem. Execute o schema SQL no phpMyAdmin.</p>";
                    }
                    echo "</div>";
                    
                    // Teste 6: Testar inserção
                    echo "<div class='test-section'>";
                    echo "<h2>Teste 6: Testar Inserção (Opcional)</h2>";
                    echo "<p class='info'>Este teste cria um lead de teste no banco</p>";
                    
                    if (isset($_GET['test_insert'])) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO leads (name, email, phone, zipcode, message, source, form_type, status, priority)
                                VALUES (:name, :email, :phone, :zipcode, :message, :source, :form_type, 'new', 'medium')
                            ");
                            
                            $stmt->execute([
                                ':name' => 'Test Lead',
                                ':email' => 'test@example.com',
                                ':phone' => '(555) 123-4567',
                                ':zipcode' => '12345',
                                ':message' => 'Este é um lead de teste criado automaticamente',
                                ':source' => 'Test',
                                ':form_type' => 'test-form'
                            ]);
                            
                            $lead_id = $pdo->lastInsertId();
                            echo "<p class='success'>✅ Lead de teste criado com sucesso! ID: $lead_id</p>";
                        } catch (PDOException $e) {
                            echo "<p class='error'>❌ Erro ao criar lead de teste: " . htmlspecialchars($e->getMessage()) . "</p>";
                        }
                    } else {
                        echo "<p><a href='?test_insert=1' style='background: #1a2036; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Criar Lead de Teste</a></p>";
                    }
                    echo "</div>";
                    
                } else {
                    echo "<p class='error'>❌ Falha ao conectar ao banco de dados</p>";
                    echo "<p class='warning'>Verifique:</p>";
                    echo "<ul>";
                    echo "<li>Se o banco de dados existe</li>";
                    echo "<li>Se o usuário tem permissões</li>";
                    echo "<li>Se as credenciais estão corretas em config/database.php</li>";
                    echo "</ul>";
                }
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Erro de conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p class='warning'>Possíveis causas:</p>";
                echo "<ul>";
                echo "<li>Nome do banco incorreto</li>";
                echo "<li>Usuário ou senha incorretos</li>";
                echo "<li>Banco de dados não existe</li>";
                echo "<li>Usuário não tem permissões</li>";
                echo "</ul>";
            }
        } else {
            echo "<p class='error'>❌ Função getDBConnection() não existe</p>";
        }
        echo "</div>";
        
        // Resumo
        echo "<div class='test-section'>";
        echo "<h2>📋 Resumo</h2>";
        
        $all_ok = true;
        if (!file_exists($config_file)) $all_ok = false;
        if (!function_exists('isDatabaseConfigured') || !isDatabaseConfigured()) $all_ok = false;
        if (!function_exists('getDBConnection')) $all_ok = false;
        
        if ($all_ok && isset($pdo) && $pdo) {
            echo "<p class='success' style='font-size: 18px;'>✅ TUDO FUNCIONANDO! Banco de dados configurado corretamente.</p>";
            echo "<p>Você pode acessar o CRM e verificar se mostra 'MySQL Database'</p>";
        } else {
            echo "<p class='error' style='font-size: 18px;'>❌ AINDA HÁ PROBLEMAS</p>";
            echo "<p>Revise os testes acima para identificar o problema.</p>";
        }
        echo "</div>";
        ?>
        
        <hr style="margin: 30px 0;">
        <p style="color: #666; font-size: 12px;">
            <strong>Nota:</strong> Este arquivo é apenas para testes. Remova ou proteja este arquivo em produção.
        </p>
    </div>
</body>
</html>
