<?php
/**
 * Debug Script - Verificar Caminhos de Salvamento
 * Acesse: https://seudominio.com/debug-save-path.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Caminhos de Salvamento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 20px auto;
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
        .section {
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
            font-size: 12px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #1a2036;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug - Caminhos de Salvamento</h1>
        
        <?php
        // Informações do servidor
        echo "<div class='section'>";
        echo "<h2>1. Informações do Servidor</h2>";
        echo "<p><strong>Script atual:</strong> <code>" . __FILE__ . "</code></p>";
        echo "<p><strong>Diretório atual:</strong> <code>" . __DIR__ . "</code></p>";
        echo "<p><strong>SCRIPT_NAME:</strong> <code>" . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</code></p>";
        echo "<p><strong>DOCUMENT_ROOT:</strong> <code>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</code></p>";
        echo "</div>";
        
        // Caminho que send-lead.php usa
        echo "<div class='section'>";
        echo "<h2>2. Caminho que send-lead.php Usa</h2>";
        
        // Simular o que send-lead.php faz
        $log_dir_send_lead = dirname(__DIR__);
        $log_file_send_lead = $log_dir_send_lead . '/leads.csv';
        
        echo "<p><strong>send-lead.php está em:</strong> <code>" . __DIR__ . "/lp/send-lead.php</code> (assumindo)</p>";
        echo "<p><strong>dirname(__DIR__) retorna:</strong> <code>$log_dir_send_lead</code></p>";
        echo "<p><strong>Caminho do CSV (send-lead.php):</strong> <code>$log_file_send_lead</code></p>";
        
        if (file_exists($log_file_send_lead)) {
            echo "<p class='success'>✅ Arquivo existe!</p>";
            echo "<p><strong>Tamanho:</strong> " . filesize($log_file_send_lead) . " bytes</p>";
            echo "<p><strong>Última modificação:</strong> " . date('Y-m-d H:i:s', filemtime($log_file_send_lead)) . "</p>";
            echo "<p><strong>Permissões:</strong> " . substr(sprintf('%o', fileperms($log_file_send_lead)), -4) . "</p>";
            
            // Ler últimas linhas
            $lines = file($log_file_send_lead);
            echo "<p><strong>Total de linhas:</strong> " . count($lines) . "</p>";
            if (count($lines) > 0) {
                echo "<h3>Últimas 5 linhas:</h3>";
                echo "<pre>";
                echo htmlspecialchars(implode('', array_slice($lines, -5)));
                echo "</pre>";
            }
        } else {
            echo "<p class='error'>❌ Arquivo NÃO existe neste caminho!</p>";
        }
        echo "</div>";
        
        // Caminho que CRM usa
        echo "<div class='section'>";
        echo "<h2>3. Caminho que CRM Usa</h2>";
        
        // Simular o que CRM faz
        $crm_dir = __DIR__ . '/admin-modules';
        $csv_file_crm = dirname($crm_dir) . '/leads.csv';
        
        echo "<p><strong>CRM está em:</strong> <code>$crm_dir/crm.php</code></p>";
        echo "<p><strong>Caminho do CSV (CRM):</strong> <code>$csv_file_crm</code></p>";
        
        if (file_exists($csv_file_crm)) {
            echo "<p class='success'>✅ Arquivo existe!</p>";
            echo "<p><strong>Tamanho:</strong> " . filesize($csv_file_crm) . " bytes</p>";
            echo "<p><strong>Última modificação:</strong> " . date('Y-m-d H:i:s', filemtime($csv_file_crm)) . "</p>";
        } else {
            echo "<p class='error'>❌ Arquivo NÃO existe neste caminho!</p>";
        }
        echo "</div>";
        
        // Verificar outros caminhos possíveis
        echo "<div class='section'>";
        echo "<h2>4. Verificar Outros Caminhos Possíveis</h2>";
        
        $possible_paths = [
            __DIR__ . '/leads.csv',
            dirname(__DIR__) . '/leads.csv',
            $_SERVER['DOCUMENT_ROOT'] . '/leads.csv',
            $_SERVER['DOCUMENT_ROOT'] . '/public_html/leads.csv',
            dirname(__DIR__) . '/public_html/leads.csv',
        ];
        
        echo "<table>";
        echo "<tr><th>Caminho</th><th>Existe?</th><th>Tamanho</th><th>Última Modificação</th></tr>";
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                echo "<tr>";
                echo "<td><code>$path</code></td>";
                echo "<td class='success'>✅ Sim</td>";
                echo "<td>" . filesize($path) . " bytes</td>";
                echo "<td>" . date('Y-m-d H:i:s', filemtime($path)) . "</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td><code>$path</code></td>";
                echo "<td class='error'>❌ Não</td>";
                echo "<td>-</td>";
                echo "<td>-</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        echo "</div>";
        
        // Verificar banco de dados
        echo "<div class='section'>";
        echo "<h2>5. Verificar Banco de Dados</h2>";
        
        $db_config_file = __DIR__ . '/config/database.php';
        if (file_exists($db_config_file)) {
            require_once $db_config_file;
            
            if (function_exists('isDatabaseConfigured') && isDatabaseConfigured()) {
                echo "<p class='success'>✅ Banco de dados está configurado</p>";
                
                if (function_exists('getDBConnection')) {
                    try {
                        $pdo = getDBConnection();
                        if ($pdo) {
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads");
                            $count = $stmt->fetch()['total'];
                            echo "<p><strong>Total de leads no banco:</strong> $count</p>";
                            
                            // Últimos 5 leads
                            $stmt = $pdo->query("SELECT id, name, email, created_at FROM leads ORDER BY created_at DESC LIMIT 5");
                            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($leads) > 0) {
                                echo "<h3>Últimos 5 leads no banco:</h3>";
                                echo "<table>";
                                echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Data</th></tr>";
                                foreach ($leads as $lead) {
                                    echo "<tr>";
                                    echo "<td>" . $lead['id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($lead['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($lead['email']) . "</td>";
                                    echo "<td>" . $lead['created_at'] . "</td>";
                                    echo "</tr>";
                                }
                                echo "</table>";
                            }
                        }
                    } catch (PDOException $e) {
                        echo "<p class='error'>❌ Erro ao conectar: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
            } else {
                echo "<p class='warning'>⚠️ Banco de dados NÃO está configurado (usando CSV)</p>";
            }
        } else {
            echo "<p class='error'>❌ Arquivo de configuração do banco não existe</p>";
        }
        echo "</div>";
        
        // Resumo
        echo "<div class='section'>";
        echo "<h2>📋 Resumo</h2>";
        
        $send_lead_exists = file_exists($log_file_send_lead);
        $crm_exists = file_exists($csv_file_crm);
        $same_file = ($log_file_send_lead === $csv_file_crm);
        
        if ($send_lead_exists && $crm_exists && $same_file) {
            echo "<p class='success'>✅ TUDO OK! send-lead.php e CRM estão usando o mesmo arquivo.</p>";
        } elseif ($send_lead_exists && $crm_exists && !$same_file) {
            echo "<p class='error'>❌ PROBLEMA: send-lead.php e CRM estão usando arquivos DIFERENTES!</p>";
            echo "<p>send-lead.php salva em: <code>$log_file_send_lead</code></p>";
            echo "<p>CRM lê de: <code>$csv_file_crm</code></p>";
        } elseif (!$send_lead_exists) {
            echo "<p class='error'>❌ PROBLEMA: send-lead.php não está conseguindo salvar o arquivo!</p>";
            echo "<p>Verifique permissões do diretório: <code>$log_dir_send_lead</code></p>";
        } elseif (!$crm_exists) {
            echo "<p class='error'>❌ PROBLEMA: CRM não está encontrando o arquivo!</p>";
        }
        echo "</div>";
        ?>
        
        <hr style="margin: 30px 0;">
        <p style="color: #666; font-size: 12px;">
            <strong>Nota:</strong> Este arquivo é apenas para diagnóstico. Remova ou proteja este arquivo em produção.
        </p>
    </div>
</body>
</html>
