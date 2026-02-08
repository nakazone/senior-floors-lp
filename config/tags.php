<?php
/**
 * Tags Configuration
 * Common tags used across the system
 */

// This file is included by tag management endpoints
// It can be extended to define allowed tags or tag categories

$allowed_tags = [
    'vinyl',
    'hardwood',
    'laminate',
    'tile',
    'carpet',
    'repair',
    'installation',
    'refinishing',
    'maintenance',
    'commercial',
    'residential',
    'urgent',
    'follow-up',
    'quote-sent',
    'proposal-pending'
];

function isValidTag($tag) {
    global $allowed_tags;
    return in_array(strtolower($tag), $allowed_tags);
}

/** Labels amigáveis para exibição (lead-detail, API) */
$tag_labels = [
    'vinyl' => 'Vinyl',
    'hardwood' => 'Hardwood',
    'laminate' => 'Laminate',
    'tile' => 'Tile',
    'carpet' => 'Carpet',
    'repair' => 'Reparo',
    'installation' => 'Instalação',
    'refinishing' => 'Refinishing',
    'maintenance' => 'Manutenção',
    'commercial' => 'Comercial',
    'residential' => 'Residencial',
    'urgent' => 'Urgente',
    'follow-up' => 'Follow-up',
    'quote-sent' => 'Orçamento enviado',
    'proposal-pending' => 'Proposta pendente'
];

function getTagLabel($tag_key) {
    global $tag_labels;
    $key = strtolower(trim((string) $tag_key));
    return isset($tag_labels[$key]) ? $tag_labels[$key] : $tag_key;
}

function getAvailableTags() {
    global $tag_labels;
    return $tag_labels;
}
