<?php
/**
 * Telegram Bot Configuration
 * FASE 1 - MÓDULO 02: Alerta Interno Free (Telegram)
 * 
 * INSTRUÇÕES DE CONFIGURAÇÃO:
 * 1. Crie um bot no Telegram falando com @BotFather
 * 2. Envie /newbot e siga as instruções
 * 3. Copie o BOT_TOKEN fornecido
 * 4. Para obter o CHAT_ID:
 *    - Adicione o bot em um grupo ou converse com ele
 *    - Envie uma mensagem para o bot
 *    - Acesse: https://api.telegram.org/bot<SEU_BOT_TOKEN>/getUpdates
 *    - Procure por "chat":{"id":123456789} - esse é o CHAT_ID
 * 5. Configure as constantes abaixo
 */

// ============================================
// CONFIGURAÇÃO DO TELEGRAM BOT
// ============================================

// Token do bot (obtido do @BotFather)
// Exemplo: '123456789:ABCdefGHIjklMNOpqrsTUVwxyz'
define('TELEGRAM_BOT_TOKEN', ''); // ⚠️ CONFIGURE AQUI

// ID do chat/grupo onde as mensagens serão enviadas
// Pode ser um chat pessoal (seu ID) ou um grupo (ID do grupo)
// Exemplo: '-1001234567890' (grupo) ou '123456789' (pessoa)
define('TELEGRAM_CHAT_ID', ''); // ⚠️ CONFIGURE AQUI

// URL da API do Telegram
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);

// Habilitar/desabilitar notificações (útil para testes)
define('TELEGRAM_ENABLED', !empty(TELEGRAM_BOT_TOKEN) && !empty(TELEGRAM_CHAT_ID));

/**
 * Verifica se o Telegram está configurado
 */
function isTelegramConfigured() {
    return TELEGRAM_ENABLED;
}
