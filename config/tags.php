<?php
/**
 * Tags Configuration
 * FASE 2 - MÓDULO 05: Tags e Qualificação
 * 
 * Tags pré-definidas para leads
 */

// Tags disponíveis para leads
$available_tags = [
    'vinyl' => 'Vinyl Flooring',
    'hardwood' => 'Hardwood',
    'laminate' => 'Laminate',
    'tile' => 'Tile',
    'carpet' => 'Carpet',
    'repair' => 'Repair',
    'installation' => 'Installation',
    'removal' => 'Removal',
    'commercial' => 'Commercial',
    'residential' => 'Residential',
    'urgent' => 'Urgent',
    'quote' => 'Needs Quote',
    'follow-up' => 'Follow-up Required'
];

/**
 * Retorna todas as tags disponíveis
 */
function getAvailableTags() {
    global $available_tags;
    return $available_tags;
}

/**
 * Verifica se uma tag é válida
 */
function isValidTag($tag) {
    global $available_tags;
    return isset($available_tags[$tag]);
}

/**
 * Retorna o nome amigável de uma tag
 */
function getTagLabel($tag) {
    global $available_tags;
    return $available_tags[$tag] ?? $tag;
}
