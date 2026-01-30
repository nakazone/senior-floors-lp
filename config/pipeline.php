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

    // Estágios do pipeline (slug => nome) - 11 estágios conforme especificação
    'stages' => [
        'lead_received'    => 'Lead Recebido',
        'contact_made'     => 'Contato Realizado',
        'qualified'        => 'Qualificado',
        'visit_scheduled'  => 'Visita Agendada',
        'measurement_done' => 'Medição Realizada',
        'proposal_created' => 'Proposta Criada',
        'proposal_sent'    => 'Proposta Enviada',
        'quote_sent'       => 'Orçamento enviado', // legado
        'negotiation'      => 'Em Negociação',
        'closed_won'       => 'Fechado - Ganhou',
        'closed_lost'      => 'Fechado - Perdido',
        'production'      => 'Produção / Obra',
        'post_sale'        => 'Pós-venda', // legado
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
