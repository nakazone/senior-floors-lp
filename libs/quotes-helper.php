<?php
/**
 * Quotes module helper: quote_number, public_token, activity log
 */

/**
 * Generate unique sequential quote_number (e.g. Q-2024-0001)
 */
function quotes_generate_quote_number(PDO $pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT id FROM quotes WHERE quote_number LIKE :prefix ORDER BY id DESC LIMIT 1");
    $stmt->execute([':prefix' => "Q-{$year}-%"]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = 1;
    if ($row && preg_match('/Q-\d{4}-(\d+)/', $row['quote_number'] ?? '', $m)) {
        $next = (int)$m[1] + 1;
    }
    return sprintf('Q-%s-%04d', $year, $next);
}

/**
 * Generate secure public token for quote view link
 */
function quotes_generate_public_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Log quote activity
 */
function quotes_log_activity(PDO $pdo, $quote_id, $action, $performed_by = null, $performed_by_type = 'user', $metadata = null) {
    $has_table = $pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0;
    if (!$has_table) return;
    $stmt = $pdo->prepare("INSERT INTO quote_activity_log (quote_id, action, performed_by, performed_by_type, metadata) VALUES (?, ?, ?, ?, ?)");
    $meta_json = $metadata !== null ? json_encode($metadata) : null;
    $stmt->execute([$quote_id, $action, $performed_by, $performed_by_type, $meta_json]);
}

/**
 * Check if quote can be edited (only draft)
 */
function quotes_can_edit($status) {
    return in_array($status, ['draft'], true);
}

/**
 * Check if quote can be accepted/declined (sent or viewed, not expired)
 */
function quotes_can_accept_or_decline($quote) {
    if (!in_array($quote['status'] ?? '', ['sent', 'viewed'], true)) return false;
    $exp = $quote['expiration_date'] ?? null;
    if ($exp && strtotime($exp) < time()) return false;
    return true;
}

/**
 * Calculate item total from quantity * unit_price * (1 + tax_rate/100)
 */
function quotes_item_total($quantity, $unit_price, $tax_rate = 0) {
    $sub = (float)$quantity * (float)$unit_price;
    $tax = $sub * (float)$tax_rate / 100;
    return round($sub + $tax, 2);
}

/**
 * Calculate quote subtotal from items, then discount, then tax
 */
function quotes_calculate_totals($items, $discount_type = 'percentage', $discount_value = 0, $tax_total = 0) {
    $subtotal = 0;
    foreach ($items as $it) {
        $total = $it['total'] ?? (($it['quantity'] ?? 1) * ($it['unit_price'] ?? 0));
        if (isset($it['tax_rate']) && $it['tax_rate']) {
            $total = $total * (1 + (float)$it['tax_rate'] / 100);
        }
        $subtotal += (float)$total;
    }
    $subtotal = round($subtotal, 2);
    $discount = 0;
    if ($discount_type === 'percentage') {
        $discount = round($subtotal * (float)$discount_value / 100, 2);
    } else {
        $discount = min((float)$discount_value, $subtotal);
    }
    $after_discount = $subtotal - $discount;
    $total = round($after_discount + (float)$tax_total, 2);
    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'tax_total' => (float)$tax_total,
        'total' => $total,
    ];
}
