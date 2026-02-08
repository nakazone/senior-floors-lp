<?php
/**
 * Quotes module config (Invoice2go-style)
 * Status, item types, discount types, currency
 */

return [
    'quote_status' => [
        'draft'    => 'Rascunho',
        'sent'     => 'Enviado',
        'viewed'   => 'Visualizado',
        'approved' => 'Aprovado',  // legacy
        'rejected' => 'Rejeitado',  // legacy
        'accepted' => 'Aceito',
        'declined' => 'Recusado',
        'expired'  => 'Expirado',
    ],

    'item_type' => [
        'material' => 'Material',
        'labor'    => 'Mão de obra',
        'service'  => 'Serviço',
    ],

    'discount_type' => [
        'percentage' => 'Percentual (%)',
        'fixed'      => 'Valor fixo',
    ],

    'currency' => 'USD',
    'currency_symbol' => '$',

    'default_expiration_days' => 30,
];
