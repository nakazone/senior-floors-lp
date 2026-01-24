<?php
/**
 * Test Permissions Script
 * Acesse: https://seudominio.com/test-permissions.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Permissions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        <h1>🔍 Test Permissions</h1>
        
        <?php
        // Test 1: Check DOCUMENT_ROOT
        echo "<div class='section'>";
        echo "<h2>1. DOCUMENT_ROOT</h2>";
        $doc_root = $_SERVER['DOCUMENT_ROOT'] ?? 'Not set';
        echo "<p><strong>DOCUMENT_ROOT:</strong> <code>$doc_root</code></p>";
        echo "</div>";
        
        // Test 2: Check possible CSV paths
        echo "<div class='section'>";
        echo "<h2>2. Verificar Caminhos Possíveis</h2>";
        
        $possible_paths = [
            $_SERVER['DOCUMENT_ROOT'] . '/leads.csv',
            dirname(__DIR__) . '/leads.csv',
            __DIR__ . '/../leads.csv',
        ];
        
        foreach ($possible_paths as $path) {
            $dir = dirname($path);
            echo "<h3>Caminho: <code>$path</code></h3>";
            
            // Check if directory exists
            if (is_dir($dir)) {
                echo "<p class='success'>✅ Diretório existe</p>";
                
                // Check if directory is writable
                if (is_writable($dir)) {
                    echo "<p class='success'>✅ Diretório tem permissão de escrita</p>";
                } else {
                    echo "<p class='error'>❌ Diretório NÃO tem permissão de escrita</p>";
                    echo "<p><strong>Permissões atuais:</strong> " . substr(sprintf('%o', fileperms($dir)), -4) . "</p>";
                    echo "<p class='warning'>⚠️ Precisa ser 755 ou 775</p>";
                }
                
                // Check if file exists
                if (file_exists($path)) {
                    echo "<p class='info'>ℹ️ Arquivo existe</p>";
                    
                    // Check if file is writable
                    if (is_writable($path)) {
                        echo "<p class='success'>✅ Arquivo tem permissão de escrita</p>";
                    } else {
                        echo "<p class='error'>❌ Arquivo NÃO tem permissão de escrita</p>";
                        echo "<p><strong>Permissões atuais:</strong> " . substr(sprintf('%o', fileperms($path)), -4) . "</p>";
                        echo "<p class='warning'>⚠️ Precisa ser 644 ou 666</p>";
                    }
                } else {
                    echo "<p class='info'>ℹ️ Arquivo não existe (será criado)</p>";
                }
            } else {
                echo "<p class='error'>❌ Diretório NÃO existe</p>";
            }
            
            echo "<hr>";
        }
        echo "</div>";
        
        // Test 3: Try to create/write file
        echo "<div class='section'>";
        echo "<h2>3. Teste de Escrita</h2>";
        
        $test_path = $_SERVER['DOCUMENT_ROOT'] . '/leads.csv';
        $test_dir = dirname($test_path);
        
        if (is_dir($test_dir) && is_writable($test_dir)) {
            $test_data = date('Y-m-d H:i:s') . ",test,Test User,555-1234,test@test.com,12345,Test message from permissions script\n";
            
            if (file_put_contents($test_path, $test_data, FILE_APPEND | LOCK_EX)) {
                echo "<p class='success'>✅ SUCESSO! Conseguiu escrever no arquivo!</p>";
                echo "<p><strong>Caminho:</strong> <code>$test_path</code></p>";
                
                // Read back to confirm
                if (file_exists($test_path)) {
                    $content = file_get_contents($test_path);
                    $lines = explode("\n", trim($content));
                    echo "<p><strong>Total de linhas no arquivo:</strong> " . count($lines) . "</p>";
                    echo "<p><strong>Última linha:</strong></p>";
                    echo "<pre>" . htmlspecialchars(end($lines)) . "</pre>";
                }
            } else {
                echo "<p class='error'>❌ FALHOU! Não conseguiu escrever no arquivo.</p>";
                echo "<p class='warning'>Possíveis causas:</p>";
                echo "<ul>";
                echo "<li>Permissões do arquivo (se já existe)</li>";
                echo "<li>Limite de espaço em disco</li>";
                echo "<li>Proteção do servidor</li>";
                echo "</ul>";
            }
        } else {
            echo "<p class='error'>❌ Não é possível testar: diretório não existe ou não tem permissão de escrita</p>";
        }
        echo "</div>";
        
        // Test 4: Check current user
        echo "<div class='section'>";
        echo "<h2>4. Informações do Servidor</h2>";
        echo "<p><strong>Usuário PHP:</strong> " . (function_exists('get_current_user') ? get_current_user() : 'N/A') . "</p>";
        echo "<p><strong>UID:</strong> " . (function_exists('getmyuid') ? getmyuid() : 'N/A') . "</p>";
        echo "<p><strong>GID:</strong> " . (function_exists('getmygid') ? getmygid() : 'N/A') . "</p>";
        echo "</div>";
        
        // Instructions
        echo "<div class='section'>";
        echo "<h2>📋 Instruções para Corrigir</h2>";
        echo "<h3>Via File Manager (Hostinger):</h3>";
        echo "<ol>";
        echo "<li>Acesse File Manager no painel Hostinger</li>";
        echo "<li>Navegue até <code>public_html/</code></li>";
        echo "<li>Clique com botão direito em <code>public_html/</code></li>";
        echo "<li>Selecione 'Change Permissions' ou 'Alterar Permissões'</li>";
        echo "<li>Defina como <strong>755</strong> (ou 775)</li>";
        echo "<li>Se <code>leads.csv</code> já existe, defina permissões como <strong>666</strong> (ou 644)</li>";
        echo "</ol>";
        
        echo "<h3>Via SSH (se tiver acesso):</h3>";
        echo "<pre>";
        echo "cd public_html\n";
        echo "chmod 755 .\n";
        echo "touch leads.csv\n";
        echo "chmod 666 leads.csv\n";
        echo "</pre>";
        echo "</div>";
        ?>
        
        <hr style="margin: 30px 0;">
        <p style="color: #666; font-size: 12px;">
            <strong>Nota:</strong> Este arquivo é apenas para diagnóstico. Remova ou proteja este arquivo em produção.
        </p>
    </div>
</body>
</html>
