<?php
/**
 * Encaminha o POST do formulário da LP para senior-floors.com/send-lead.php
 * 
 * Use este arquivo em lp.senior-floors.com quando o form enviar para a LP:
 * - Coloque como send-lead.php na LP (ou renomeie) OU
 * - Defina no form/JS: action ou SENIOR_FLOORS_FORM_URL = 'https://senior-floors.com/send-lead.php'
 * 
 * Se o form ainda postar para lp.senior-floors.com/send-lead.php, renomeie este arquivo
 * para send-lead.php na LP — assim o POST será encaminhado ao painel (e-mail + CSV + banco).
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$target = 'https://senior-floors.com/send-lead.php';

$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'application/x-www-form-urlencoded';
$body = file_get_contents('php://input');

$ch = curl_init($target);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: ' . $content_type,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao encaminhar para o servidor.',
        'forward_error' => $err
    ]);
    exit;
}

http_response_code($http_code >= 100 && $http_code < 600 ? $http_code : 200);
echo $response !== false ? $response : json_encode(['success' => false, 'message' => 'Resposta vazia do servidor.']);
