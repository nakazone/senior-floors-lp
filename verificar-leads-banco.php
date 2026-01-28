<?php
/**
 * Script para Verificar se Leads Estão Sendo Salvos no Banco
 * Acesse: https://seudominio.com/verificar-leads-banco.php
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verificar Leads no Banco</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .success { background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        h2 { color: #333; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificar Leads no Banco de Dados</h1>
        
        <h2>1. Status do Banco de Dados</h2>
        <?php if (isDatabaseConfigured()): ?>
            <div class="success">✅ Banco de dados configurado</div>
            
            <?php
            try {
                $pdo = getDBConnection();
                
                // Verificar tabela leads
                $stmt = $pdo->query("SHOW TABLES LIKE 'leads'");
                $table_exists = $stmt->rowCount() > 0;
                
                if ($table_exists):
                    echo '<div class="success">✅ Tabela leads existe</div>';
                    
                    // Contar leads
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM leads");
                    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    echo "<div class='info'>📊 Total de leads no banco: <strong>$total</strong></div>";
                    
                    // Últimos 10 leads
                    if ($total > 0):
                        $stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT 10");
                        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <h2>2. Últimos 10 Leads</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Form</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leads as $lead): ?>
                                    <tr>
                                        <td><?php echo $lead['id']; ?></td>
                                        <td><?php echo htmlspecialchars($lead['name']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['form_type']); ?></td>
                                        <td><?php echo htmlspecialchars($lead['status']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($lead['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="info">ℹ️ Nenhum lead encontrado no banco de dados</div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="error">❌ Tabela leads não existe</div>
                    <div class="info">
                        <strong>Solução:</strong> Execute o schema SQL:<br>
                        <code>database/schema-v3-completo.sql</code>
                    </div>
                <?php endif; ?>
                
                <?php
            } catch (Exception $e) {
                echo '<div class="error">❌ Erro ao conectar ao banco: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        <?php else: ?>
            <div class="error">❌ Banco de dados não configurado</div>
            <div class="info">
                Configure o arquivo <code>config/database.php</code>
            </div>
        <?php endif; ?>
        
        <h2>3. Verificar Arquivo de Log</h2>
        <?php
        $log_file = __DIR__ . '/lead-db-save.log';
        if (file_exists($log_file)):
            $logs = file_get_contents($log_file);
            $log_lines = explode("\n", $logs);
            $recent_logs = array_slice(array_filter($log_lines), -10);
            ?>
            <div class="info">
                <strong>Últimas 10 entradas do log:</strong>
                <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;"><?php echo htmlspecialchars(implode("\n", $recent_logs)); ?></pre>
            </div>
        <?php else: ?>
            <div class="info">ℹ️ Arquivo de log não existe ainda (será criado quando houver tentativas de salvamento)</div>
        <?php endif; ?>
        
        <h2>4. Verificar CSV (Fallback)</h2>
        <?php
        $csv_file = __DIR__ . '/leads.csv';
        if (file_exists($csv_file)):
            $csv_content = file($csv_file);
            $csv_count = count($csv_content) - 1; // -1 para header
            echo "<div class='info'>📄 Arquivo CSV existe com <strong>$csv_count</strong> leads</div>";
        else:
            echo '<div class="info">ℹ️ Arquivo CSV não existe</div>';
        endif;
        ?>
        
        <h2>5. Teste de Salvamento</h2>
        <div class="info">
            <strong>Para testar:</strong><br>
            1. Preencha um formulário na landing page<br>
            2. Envie o formulário<br>
            3. Recarregue esta página para ver se o lead apareceu<br>
            4. Verifique o arquivo <code>lead-db-save.log</code> para ver detalhes
        </div>
        
        <h2>6. Links Úteis</h2>
        <ul>
            <li><a href="system.php?module=crm" target="_blank">Acessar CRM</a></li>
            <li><a href="index.html" target="_blank">Landing Page (para testar formulário)</a></li>
        </ul>
    </div>
</body>
</html>
