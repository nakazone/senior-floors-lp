<?php
/**
 * Pipeline e fontes de lead - Senior Floors CRM
 * Estágios do Kanban, SLAs e fontes de captura
 */

return [
    // Fontes de lead (para formulários, importação, indicação manual)
    'lead_sources' => [
        'site_form'       => 'Site (formulários)',
        'whatsapp'        => 'WhatsApp',
        'instagram'       => 'Instagram / Facebook',
        'google_ads'      => 'Google Ads',
        'manual'          => 'Indicação manual',
        'csv_upload'      => 'Upload em massa (CSV)',
        'LP-Hero'         => 'Landing Page - Hero',
        'LP-Contact'      => 'Landing Page - Contato',
    ],

    // Tipos de imóvel
    'property_types' => [
        'casa'       => 'Casa',
        'apartamento' => 'Apartamento',
        'comercial'   => 'Comercial',
    ],

    // Tipos de serviço (flooring)
    'service_types' => [
        'vinyl'      => 'Vinyl',
        'hardwood'   => 'Hardwood',
        'tile'       => 'Tile',
        'carpet'     => 'Carpet',
        'refinishing'=> 'Refinishing',
        'laminate'   => 'Laminate',
        'other'      => 'Outro',
    ],

    // Estágios do pipeline (slug => nome)
    'stages' => [
        'lead_received'   => 'Lead recebido',
        'contact_made'    => 'Contato feito',
        'qualified'       => 'Qualificado',
        'visit_scheduled' => 'Visita / Medição agendada',
        'measurement_done'=> 'Medição realizada',
        'quote_sent'      => 'Orçamento enviado',
        'negotiation'     => 'Negociação',
        'closed_won'      => 'Fechado - Ganhou',
        'closed_lost'     => 'Fechado - Perdeu',
        'post_sale'       => 'Pós-venda',
    ],

    // Urgência
    'urgency' => [
        'imediato' => 'Imediato',
        '30_dias'  => '30 dias',
        '60_mais'  => '60+ dias',
    ],

    // Pagamento
    'payment_type' => [
        'cash'       => 'À vista (Cash)',
        'financing'  => 'Financiamento',
    ],

    // Status do orçamento
    'quote_status' => [
        'draft'   => 'Rascunho',
        'sent'    => 'Enviado',
        'viewed'  => 'Visualizado',
        'approved'=> 'Aprovado',
        'rejected'=> 'Rejeitado',
    ],
];
