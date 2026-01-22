<?php
/**
 * Telegram Notifier Library
 * FASE 1 - MÓDULO 02: Alerta Interno Free (Telegram)
 * 
 * Função para enviar notificações de novos leads via Telegram Bot API
 */

require_once __DIR__ . '/../config/telegram.php';

/**
 * Envia notificação de novo lead para o Telegram
 * 
 * @param array $lead_data Dados do lead:
 *   - name (string) Nome do lead
 *   - email (string) Email do lead
 *   - phone (string) Telefone do lead
 *   - zipcode (string) CEP do lead
 *   - message (string) Mensagem do lead (opcional)
 *   - source (string) Origem do lead (ex: LP-Hero, LP-Contact)
 *   - form_type (string) Tipo de formulário (ex: hero-form, contact-form)
 *   - ip_address (string) IP do lead (opcional)
 * 
 * @return array ['success' => bool, 'message' => string, 'error' => string|null]
 */
function sendTelegramNotification($lead_data) {
    // Verificar se Telegram está configurado
    if (!isTelegramConfigured()) {
        return [
            'success' => false,
            'message' => 'Telegram not configured',
            'error' => 'TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID not set'
        ];
    }

    // Validar dados obrigatórios
    if (empty($lead_data['name']) || empty($lead_data['email']) || empty($lead_data['phone'])) {
        return [
            'success' => false,
            'message' => 'Missing required lead data',
            'error' => 'Name, email, and phone are required'
        ];
    }

    // Formatar mensagem
    $message = formatTelegramMessage($lead_data);

    // Enviar via cURL
    $url = TELEGRAM_API_URL . '/sendMessage';
    
    $post_data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML', // Permite formatação HTML básica
        'disable_web_page_preview' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para Hostinger
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Verificar resposta
    if ($curl_error) {
        error_log("Telegram cURL Error: " . $curl_error);
        return [
            'success' => false,
            'message' => 'Failed to send Telegram notification',
            'error' => $curl_error
        ];
    }

    if ($http_code !== 200) {
        error_log("Telegram API Error (HTTP $http_code): " . $response);
        return [
            'success' => false,
            'message' => 'Telegram API returned error',
            'error' => "HTTP $http_code: " . substr($response, 0, 100)
        ];
    }

    $response_data = json_decode($response, true);
    
    if (isset($response_data['ok']) && $response_data['ok'] === true) {
        return [
            'success' => true,
            'message' => 'Telegram notification sent successfully',
            'error' => null
        ];
    } else {
        $error_msg = $response_data['description'] ?? 'Unknown error';
        error_log("Telegram API Error: " . $error_msg);
        return [
            'success' => false,
            'message' => 'Telegram API error',
            'error' => $error_msg
        ];
    }
}

/**
 * Formata a mensagem do Telegram com os dados do lead
 * 
 * @param array $lead_data Dados do lead
 * @return string Mensagem formatada em HTML
 */
function formatTelegramMessage($lead_data) {
    $name = htmlspecialchars($lead_data['name'] ?? 'N/A');
    $email = htmlspecialchars($lead_data['email'] ?? 'N/A');
    $phone = htmlspecialchars($lead_data['phone'] ?? 'N/A');
    $zipcode = htmlspecialchars($lead_data['zipcode'] ?? 'N/A');
    $message = !empty($lead_data['message']) ? htmlspecialchars($lead_data['message']) : 'Nenhuma mensagem';
    $source = htmlspecialchars($lead_data['source'] ?? 'Unknown');
    $form_type = htmlspecialchars($lead_data['form_type'] ?? 'contact-form');
    $ip_address = htmlspecialchars($lead_data['ip_address'] ?? 'Unknown');
    $timestamp = date('Y-m-d H:i:s');

    // Emoji para melhor visualização
    $emoji_new = '🆕';
    $emoji_name = '👤';
    $emoji_phone = '📞';
    $emoji_email = '📧';
    $emoji_location = '📍';
    $emoji_message = '💬';
    $emoji_source = '📊';
    $emoji_time = '🕐';

    // Formato da mensagem
    $telegram_message = "$emoji_new <b>NOVO LEAD - Senior Floors</b>\n\n";
    $telegram_message .= "$emoji_name <b>Nome:</b> $name\n";
    $telegram_message .= "$emoji_phone <b>Telefone:</b> $phone\n";
    $telegram_message .= "$emoji_email <b>Email:</b> $email\n";
    $telegram_message .= "$emoji_location <b>CEP:</b> $zipcode\n";
    $telegram_message .= "$emoji_source <b>Origem:</b> $source\n";
    $telegram_message .= "<b>Formulário:</b> " . ($form_type === 'hero-form' ? 'Hero Form' : 'Contact Form') . "\n";
    
    if (!empty($lead_data['message'])) {
        $telegram_message .= "\n$emoji_message <b>Mensagem:</b>\n";
        $telegram_message .= "$message\n";
    }
    
    $telegram_message .= "\n$emoji_time <b>Data/Hora:</b> $timestamp\n";
    $telegram_message .= "<b>IP:</b> $ip_address";

    return $telegram_message;
}

/**
 * Testa a conexão com o Telegram Bot
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function testTelegramConnection() {
    if (!isTelegramConfigured()) {
        return [
            'success' => false,
            'message' => 'Telegram não está configurado. Configure TELEGRAM_BOT_TOKEN e TELEGRAM_CHAT_ID em config/telegram.php'
        ];
    }

    $url = TELEGRAM_API_URL . '/getMe';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $data = json_decode($response, true);
        if (isset($data['ok']) && $data['ok'] === true) {
            $bot_name = $data['result']['first_name'] ?? 'Unknown';
            return [
                'success' => true,
                'message' => "Conexão com Telegram OK! Bot: $bot_name"
            ];
        }
    }

    return [
        'success' => false,
        'message' => 'Falha ao conectar com Telegram. Verifique o BOT_TOKEN.'
    ];
}
