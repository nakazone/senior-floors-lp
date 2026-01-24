<?php
/**
 * Debug CRM Read - Verificar como CRM está lendo os dados
 * Acesse: https://seudominio.com/debug-crm-read.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug CRM Read</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
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
        <h1>🔍 Debug CRM Read</h1>
        
        <?php
        // Simular o que o CRM faz
        require_once __DIR__ . '/config/database.php';
        
        echo "<div class='section'>";
        echo "<h2>1. Verificar Banco de Dados</h2>";
        
        $db_configured = false;
        if (function_exists('isDatabaseConfigured')) {
            $db_configured = isDatabaseConfigured();
            if ($db_configured) {
                echo "<p class='success'>✅ Banco de dados está configurado</p>";
                
                try {
                    $pdo = getDBConnection();
                    if ($pdo) {
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads");
                        $count = $stmt->fetch()['total'];
                        echo "<p><strong>Total de leads no banco:</strong> $count</p>";
                        
                        if ($count > 0) {
                            $stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 5");
                            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
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
            } else {
                echo "<p class='warning'>⚠️ Banco de dados NÃO está configurado (usando CSV)</p>";
            }
        } else {
            echo "<p class='error'>❌ Função isDatabaseConfigured() não existe</p>";
        }
        echo "</div>";
        
        // Verificar CSV
        echo "<div class='section'>";
        echo "<h2>2. Verificar CSV</h2>";
        
        $csv_dir = ($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__));
        $csv_file = $csv_dir . '/leads.csv';
        
        echo "<p><strong>Caminho do CSV:</strong> <code>$csv_file</code></p>";
        
        if (file_exists($csv_file)) {
            echo "<p class='success'>✅ Arquivo CSV existe</p>";
            echo "<p><strong>Tamanho:</strong> " . filesize($csv_file) . " bytes</p>";
            echo "<p><strong>Última modificação:</strong> " . date('Y-m-d H:i:s', filemtime($csv_file)) . "</p>";
            
            // Ler CSV como o CRM faz
            $leads = [];
            if (($handle = fopen($csv_file, 'r')) !== FALSE) {
                $header = fgetcsv($handle);
                echo "<p><strong>Cabeçalho:</strong> " . implode(', ', $header) . "</p>";
                
                $line_count = 0;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $line_count++;
                    if (count($data) === count($header)) {
                        $lead = array_combine($header, $data);
                        $leads[] = $lead;
                    } else {
                        echo "<p class='warning'>⚠️ Linha $line_count tem " . count($data) . " colunas, esperado " . count($header) . "</p>";
                    }
                }
                fclose($handle);
                
                echo "<p><strong>Total de leads no CSV:</strong> " . count($leads) . "</p>";
                
                if (count($leads) > 0) {
                    echo "<h3>Últimos 5 leads do CSV:</h3>";
                    $leads = array_reverse($leads); // Mostrar mais recentes primeiro
                    echo "<table>";
                    echo "<tr>";
                    foreach ($header as $col) {
                        echo "<th>" . htmlspecialchars($col) . "</th>";
                    }
                    echo "</tr>";
                    
                    foreach (array_slice($leads, -5) as $lead) {
                        echo "<tr>";
                        foreach ($header as $col) {
                            echo "<td>" . htmlspecialchars($lead[$col] ?? '') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='warning'>⚠️ CSV existe mas está vazio (só tem cabeçalho)</p>";
                }
            } else {
                echo "<p class='error'>❌ Não foi possível abrir o arquivo CSV</p>";
            }
        } else {
            echo "<p class='error'>❌ Arquivo CSV não existe</p>";
        }
        echo "</div>";
        
        // Simular lógica do CRM
        echo "<div class='section'>";
        echo "<h2>3. Simular Lógica do CRM</h2>";
        
        $crm_leads = [];
        $data_source = '';
        
        // Try MySQL first (como o CRM faz)
        if ($db_configured) {
            try {
                $pdo = getDBConnection();
                if ($pdo) {
                    $stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC");
                    $crm_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $data_source = 'MySQL Database';
                }
            } catch (PDOException $e) {
                echo "<p class='warning'>⚠️ Erro ao ler do banco: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        // Fallback to CSV
        if (empty($crm_leads) && file_exists($csv_file)) {
            if (($handle = fopen($csv_file, 'r')) !== FALSE) {
                $header = fgetcsv($handle);
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) === count($header)) {
                        $lead = array_combine($header, $data);
                        $crm_leads[] = $lead;
                    }
                }
                fclose($handle);
                $crm_leads = array_reverse($crm_leads);
                $data_source = 'CSV File';
            }
        }
        
        echo "<p><strong>Fonte de dados que o CRM usaria:</strong> <span class='info'>$data_source</span></p>";
        echo "<p><strong>Total de leads que o CRM encontraria:</strong> " . count($crm_leads) . "</p>";
        
        if (count($crm_leads) === 0) {
            echo "<p class='error'>❌ PROBLEMA: CRM não encontraria nenhum lead!</p>";
            echo "<p class='warning'>Possíveis causas:</p>";
            echo "<ul>";
            echo "<li>Banco não configurado E CSV vazio ou não legível</li>";
            echo "<li>Problema na leitura do CSV</li>";
            echo "<li>Estrutura do CSV diferente do esperado</li>";
            echo "</ul>";
        } else {
            echo "<p class='success'>✅ CRM encontraria " . count($crm_leads) . " leads</p>";
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
