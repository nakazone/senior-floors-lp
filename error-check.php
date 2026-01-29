<?php
/**
 * Diagnóstico de erro 500 - Senior Floors
 * Acesse: https://senior-floors.com/error-check.php (ou /lp/error-check.php)
 * REMOVA este arquivo após corrigir o problema.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico system.php</h1>";
echo "<p><strong>__DIR__ (pasta deste script):</strong> " . __DIR__ . "</p>";
echo "<p><strong>dirname(__DIR__):</strong> " . dirname(__DIR__) . "</p>";

$paths = [
    'config/permissions.php' => [__DIR__ . '/config/permissions.php', dirname(__DIR__) . '/config/permissions.php'],
    'admin-config.php'       => [__DIR__ . '/admin-config.php', dirname(__DIR__) . '/admin-config.php'],
    'config/database.php'    => [__DIR__ . '/config/database.php', dirname(__DIR__) . '/config/database.php'],
];

echo "<h2>Arquivos necessários</h2><ul>";
foreach ($paths as $label => $candidates) {
    $found = null;
    foreach ($candidates as $p) {
        if (is_file($p)) {
            $found = $p;
            break;
        }
    }
    echo "<li><strong>$label:</strong> " . ($found ? "✓ $found" : "✗ Não encontrado (tentou: " . implode(', ', $candidates) . ")") . "</li>";
}
echo "</ul>";

echo "<h2>Teste de carregamento (como system.php)</h2>";
$SYSTEM_ROOT = __DIR__;
if (!is_file(__DIR__ . '/config/permissions.php') && is_file(dirname(__DIR__) . '/config/permissions.php')) {
    $SYSTEM_ROOT = dirname(__DIR__);
    echo "<p>Usando pasta pai como raiz: <code>$SYSTEM_ROOT</code></p>";
}

try {
    require_once $SYSTEM_ROOT . '/config/permissions.php';
    echo "<p>✓ config/permissions.php carregado.</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>✗ Erro ao carregar permissions: " . $e->getMessage() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

try {
    require_once $SYSTEM_ROOT . '/admin-config.php';
    echo "<p>✓ admin-config.php carregado.</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>✗ Erro ao carregar admin-config: " . $e->getMessage() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<p><a href='system.php'>Tentar abrir system.php</a></p>";
echo "<p><small>Após corrigir, apague este arquivo (error-check.php).</small></p>";
