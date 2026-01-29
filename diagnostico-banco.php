<?php
/**
 * Diagnóstico: por que os leads não estão indo para o banco?
 * Acesse no navegador: https://seusite.com/diagnostico-banco.php
 * Remova ou proteja este arquivo após corrigir.
 */
header('Content-Type: text/html; charset=UTF-8');
$possible_configs = [
    __DIR__ . '/config/database.php',
    dirname(__DIR__) . '/config/database.php',
];
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $possible_configs[] = $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
}
$db_config_file = null;
foreach ($possible_configs as $path) {
    if (file_exists($path)) {
        $db_config_file = $path;
        break;
    }
}

// Mesmo caminho que send-lead.php usa (raiz do site ou pasta do script)
$log_file_primary = (!empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : __DIR__) . '/lead-db-save.log';
$log_file_fallback = __DIR__ . '/lead-db-save.log';
$log_file = file_exists($log_file_primary) ? $log_file_primary : $log_file_fallback;
$last_log = '';
$log_path_used = null;
if (file_exists($log_file_primary)) {
    $last_log = trim(@file_get_contents($log_file_primary));
    $log_path_used = $log_file_primary;
}
if ($last_log === '' && file_exists($log_file_fallback) && $log_file_fallback !== $log_file_primary) {
    $last_log = trim(@file_get_contents($log_file_fallback));
    $log_path_used = $log_file_fallback;
}
if ($log_path_used === null) {
    $log_path_used = $log_file_primary;
}
$last_lines = $last_log ? implode("\n", array_slice(explode("\n", $last_log), -15)) : '(vazio)';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Banco - Senior Floors</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #1a2036; }
        .ok { color: #16a34a; font-weight: 600; }
        .fail { color: #dc2626; font-weight: 600; }
        .warn { color: #ca8a04; }
        .box { background: #fff; border-radius: 8px; padding: 16px; margin: 16px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        pre { background: #f1f5f9; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
        code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; }
        ul { margin: 8px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <h1>Diagnóstico: Leads no banco de dados</h1>
    <p>Use esta página para descobrir por que os leads estão indo só para o CSV e não para o MySQL.</p>

    <div class="box">
        <h2>1. Arquivo de configuração</h2>
        <?php if ($db_config_file): ?>
            <p class="ok">✓ Arquivo encontrado: <code><?php echo htmlspecialchars($db_config_file); ?></code></p>
        <?php else: ?>
            <p class="fail">✗ Arquivo <code>config/database.php</code> não encontrado.</p>
            <p><strong>O que fazer:</strong></p>
            <ul>
                <li>No servidor (Hostinger), na pasta <code>public_html</code>, entre em <code>config/</code>.</li>
                <li>Copie <code>database.php.example</code> para <code>database.php</code>.</li>
                <li>Edite <code>database.php</code> e preencha com as credenciais MySQL do Hostinger (DB_HOST, DB_NAME, DB_USER, DB_PASS).</li>
            </ul>
            <p>Paths tentados: <?php echo htmlspecialchars(implode(', ', $possible_configs)); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($db_config_file): ?>
    <?php
    require_once $db_config_file;
    $configured = isDatabaseConfigured();
    $pdo = null;
    $conn_error = null;
    if ($configured) {
        try {
            $pdo = getDBConnection();
        } catch (Throwable $e) {
            $conn_error = $e->getMessage();
        }
    }
    ?>
    <div class="box">
        <h2>2. Configuração preenchida</h2>
        <?php if ($configured): ?>
            <p class="ok">✓ <code>isDatabaseConfigured()</code> retorna true (credenciais não são placeholders).</p>
        <?php else: ?>
            <p class="fail">✗ Banco não considerado configurado.</p>
            <p>Abra <code>config/database.php</code> e substitua <code>seu_usuario</code>, <code>sua_senha</code> e <code>senior_floors_db</code> pelos valores reais do Hostinger (nome completo do banco e usuário, com prefixo tipo <code>u123456789_</code>).</p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>3. Conexão MySQL</h2>
        <?php if ($conn_error): ?>
            <p class="fail">✗ Erro ao conectar: <?php echo htmlspecialchars($conn_error); ?></p>
            <p>Verifique host, nome do banco, usuário e senha no painel Hostinger (Bancos de dados MySQL).</p>
        <?php elseif ($pdo): ?>
            <p class="ok">✓ Conexão com o MySQL estabelecida.</p>
        <?php elseif ($configured): ?>
            <p class="fail">✗ getDBConnection() retornou null (verifique error_log do PHP).</p>
        <?php else: ?>
            <p class="warn">— Configure o banco primeiro (passo 2).</p>
        <?php endif; ?>
    </div>

    <?php if ($pdo): ?>
    <?php
    $table_exists = false;
    $columns = [];
    try {
        $r = $pdo->query("SHOW TABLES LIKE 'leads'");
        $table_exists = $r && $r->rowCount() > 0;
        if ($table_exists) {
            $r = $pdo->query("SHOW COLUMNS FROM leads");
            $columns = $r ? $r->fetchAll(PDO::FETCH_COLUMN) : [];
        }
    } catch (Throwable $e) {
        $table_error = $e->getMessage();
    }
    ?>
    <div class="box">
        <h2>4. Tabela <code>leads</code></h2>
        <?php if ($table_exists): ?>
            <p class="ok">✓ A tabela <code>leads</code> existe.</p>
            <p>Colunas: <code><?php echo htmlspecialchars(implode(', ', $columns)); ?></code></p>
        <?php else: ?>
            <p class="fail">✗ A tabela <code>leads</code> não existe.</p>
            <p><strong>O que fazer:</strong> No phpMyAdmin (Hostinger), importe/execute o arquivo <code>database/schema-v3-completo.sql</code> no seu banco de dados.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="box">
        <h2>5. Últimas linhas do log (lead-db-save.log)</h2>
        <p><strong>Caminho lido:</strong> <code><?php echo htmlspecialchars($log_path_used); ?></code></p>
        <p>Se o log estiver vazio, o <code>send-lead.php</code> pode não estar sendo chamado (verifique a URL do formulário no <code>script.js</code>) ou o servidor não tem permissão de escrita nessa pasta. O script também tenta gravar em <code><?php echo htmlspecialchars($log_file_fallback); ?></code>.</p>
        <p>Quando um lead é enviado pelo formulário, o script grava:</p>
        <ul>
            <li><strong>send-lead.php chamado</strong> = requisição chegou ao script</li>
            <li><strong>LP recebido</strong> = dados da LP chegaram (name, email, form)</li>
            <li><strong>✅ Lead saved</strong> = gravado no MySQL</li>
            <li><strong>❌</strong> = mensagem de erro (config, conexão, tabela, INSERT)</li>
        </ul>
        <p>Se após enviar o formulário <em>não</em> aparecer "send-lead.php chamado", o pedido está indo para outra URL ou há erro antes do PHP (404, CORS, etc.).</p>
        <pre><?php echo htmlspecialchars($last_lines); ?></pre>
    </div>

    <div class="box">
        <h2>Resumo</h2>
        <ul>
            <li><strong>config/database.php não existe</strong> → Crie a partir de <code>config/database.php.example</code> com as credenciais MySQL do Hostinger.</li>
            <li><strong>Banco “não configurado”</strong> → Troque placeholders (seu_usuario, sua_senha, senior_floors_db) pelos valores reais.</li>
            <li><strong>Erro de conexão</strong> → Confira host, nome do banco, usuário e senha no painel do Hostinger.</li>
            <li><strong>Tabela leads não existe</strong> → Execute <code>database/schema-v3-completo.sql</code> no MySQL.</li>
        </ul>
    </div>
</body>
</html>
