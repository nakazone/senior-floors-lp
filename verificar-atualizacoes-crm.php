<?php
/**
 * Verificar se as atualizações do CRM estão no servidor
 * Acesse: https://seusite.com/verificar-atualizacoes-crm.php
 * Remova ou proteja este arquivo após conferir.
 */
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar atualizações CRM - Senior Floors</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #1a2036; }
        .ok { color: #16a34a; }
        .fail { color: #dc2626; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #1a2036; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .btn:hover { background: #252b47; }
    </style>
</head>
<body>
    <h1>Verificar atualizações do CRM</h1>
    <p>Confira se os novos módulos e arquivos estão presentes no servidor.</p>

    <h2>Painel do sistema</h2>
    <p><strong>URL do CRM (painel admin):</strong> <a href="system.php">system.php</a></p>
    <p>Se você está vendo apenas o site (landing page), acesse <strong>system.php</strong> para ver o painel com Dashboard, CRM, Pipeline, Visitas, Orçamentos, etc.</p>

    <h2>Arquivos dos novos módulos</h2>
    <ul>
        <?php
        $files = [
            'system.php' => 'Painel principal',
            'admin-modules/pipeline.php' => 'Pipeline (Kanban)',
            'admin-modules/visits.php' => 'Visitas e Medições',
            'admin-modules/visit-detail.php' => 'Detalhe Visita',
            'admin-modules/quotes.php' => 'Orçamentos',
            'admin-modules/quote-detail.php' => 'Detalhe Orçamento',
            'api/pipeline/stages.php' => 'API Pipeline estágios',
            'api/pipeline/leads.php' => 'API Pipeline leads',
            'api/pipeline/move.php' => 'API mover lead',
            'api/visits/list.php' => 'API Visitas listar',
            'api/visits/create.php' => 'API Visitas criar',
            'api/quotes/list.php' => 'API Orçamentos listar',
            'api/contracts/create.php' => 'API Contratos criar',
            'config/pipeline.php' => 'Config pipeline',
            'config/lead-logic.php' => 'Lógica leads (duplicados, distribuição)',
            'database/migration-crm-completo.sql' => 'Migration CRM completo',
        ];
        foreach ($files as $path => $label) {
            $exists = file_exists(__DIR__ . '/' . $path);
            echo '<li class="' . ($exists ? 'ok' : 'fail') . '">' . ($exists ? '✓' : '✗') . ' ' . htmlspecialchars($label) . ' <code>' . htmlspecialchars($path) . '</code></li>';
        }
        ?>
    </ul>

    <h2>O que fazer se algo estiver faltando</h2>
    <ol>
        <li><strong>Fazer deploy:</strong> Se usa GitHub/Hostinger, dê push no repositório e execute o deploy para enviar os arquivos novos.</li>
        <li><strong>Cache do navegador:</strong> Faça um hard refresh (Ctrl+Shift+R ou Cmd+Shift+R) ou abra o system.php em aba anônima.</li>
        <li><strong>Migration do banco:</strong> Execute <code>database/migration-crm-completo.sql</code> no MySQL para que Pipeline, Visitas, Orçamentos e Contratos funcionem com as novas tabelas.</li>
    </ol>

    <a href="system.php" class="btn">Abrir painel (system.php)</a>
</body>
</html>
