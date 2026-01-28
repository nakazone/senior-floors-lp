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
