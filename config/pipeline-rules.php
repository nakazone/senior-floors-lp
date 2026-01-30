<?php
/**
 * Regras do Pipeline - Senior Floors CRM
 * Não pular etapas, campos obrigatórios por estágio, próximos estágios permitidos
 */

return [
    // Ordem dos estágios (slug => order). Só pode avançar para o próximo ou voltar.
    'stage_order' => [
        'lead_received'    => 1,
        'contact_made'     => 2,
        'qualified'        => 3,
        'visit_scheduled'  => 4,
        'measurement_done' => 5,
        'proposal_created' => 6,
        'proposal_sent'    => 7,
        'negotiation'      => 8,
        'closed_won'       => 9,
        'closed_lost'      => 10,
        'production'       => 11,
    ],

    // Estágios que podem ser pulados (ex: closed_lost de qualquer um)
    'can_skip_to' => [
        'closed_lost' => true,  // Pode ir para "Fechado - Perdido" de qualquer etapa
        'closed_won'  => false, // Só após negotiation ou proposal_sent
    ],

    // Campos obrigatórios para avançar (slug => array de campos ou regras)
    'required_to_advance' => [
        'contact_made'     => [],  // Nenhum obrigatório
        'qualified'        => ['property_type', 'service_type', 'estimated_budget'],
        'visit_scheduled'  => [],
        'measurement_done' => ['has_measurement'],  // Regra: existir medição/visita concluída
        'proposal_created' => ['has_proposal'],
        'proposal_sent'    => [],
        'negotiation'      => [],
        'closed_won'       => ['has_contract_or_approval'],
        'closed_lost'      => [],
        'production'       => ['has_contract'],
    ],

    // Mensagens de validação
    'messages' => [
        'cannot_skip' => 'Não é permitido pular etapas. Avance uma etapa por vez.',
        'required_fields' => 'Preencha os campos obrigatórios para avançar: %s',
        'invalid_transition' => 'Transição de status não permitida.',
    ],
];
