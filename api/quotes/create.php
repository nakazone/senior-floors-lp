<?php
/**
 * API: Create Quote (Invoice2go-style)
 * POST lead_id | customer_id, items[], issue_date, expiration_date, discount_type, discount_value, tax_total, notes, internal_notes, currency
 */
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../libs/quotes-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : null;
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
$created_by = isset($_POST['created_by']) ? (int)$_POST['created_by'] : null;
$issue_date = isset($_POST['issue_date']) ? trim($_POST['issue_date']) : date('Y-m-d');
$expiration_date = isset($_POST['expiration_date']) ? trim($_POST['expiration_date']) : null;
$discount_type = isset($_POST['discount_type']) && $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percentage';
$discount_value = isset($_POST['discount_value']) ? (float)str_replace(',', '.', $_POST['discount_value']) : 0;
$tax_total = isset($_POST['tax_total']) ? (float)str_replace(',', '.', $_POST['tax_total']) : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
$internal_notes = isset($_POST['internal_notes']) ? trim($_POST['internal_notes']) : null;
$currency = isset($_POST['currency']) ? trim($_POST['currency']) : 'USD';

if (!$lead_id && !$customer_id && !$project_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'lead_id, customer_id or project_id required']);
    exit;
}

if (!isDatabaseConfigured()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database not configured']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('No connection');
    $has_quotes = $pdo->query("SHOW TABLES LIKE 'quotes'")->rowCount() > 0;
    if (!$has_quotes) {
        http_response_code(501);
        echo json_encode(['success' => false, 'message' => 'Quotes table not found. Run migration.']);
        exit;
    }

    $items = [];
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        $items = $_POST['items'];
    } elseif (isset($_POST['floor_type']) && isset($_POST['area_sqft']) && isset($_POST['unit_price'])) {
        $items[] = [
            'type' => 'material',
            'name' => $_POST['floor_type'] ?? 'Item',
            'quantity' => str_replace(',', '.', $_POST['area_sqft']),
            'unit_price' => str_replace(',', '.', $_POST['unit_price']),
        ];
    }

    $subtotal = 0;
    $normalized_items = [];
    foreach ($items as $it) {
        $qty = (float)($it['quantity'] ?? $it['area_sqft'] ?? 1);
        $unit = (float)($it['unit_price'] ?? 0);
        $tax_rate = (float)($it['tax_rate'] ?? 0);
        $total = quotes_item_total($qty, $unit, $tax_rate);
        $subtotal += $total;
        $normalized_items[] = [
            'type' => $it['type'] ?? 'material',
            'name' => $it['name'] ?? ($it['floor_type'] ?? 'Item'),
            'description' => $it['description'] ?? null,
            'quantity' => $qty,
            'unit_price' => $unit,
            'tax_rate' => $tax_rate,
            'total' => $total,
        ];
    }
    $subtotal = round($subtotal, 2);
    $discount = $discount_type === 'percentage' ? round($subtotal * $discount_value / 100, 2) : min($discount_value, $subtotal);
    $total_amount = round($subtotal - $discount + $tax_total, 2);

    $has_quote_number = $pdo->query("SHOW COLUMNS FROM quotes LIKE 'quote_number'")->rowCount() > 0;
    $quote_number = $has_quote_number ? quotes_generate_quote_number($pdo) : null;

    $cols = ['lead_id','customer_id','project_id','version','total_amount','labor_amount','materials_amount','margin_percent','status','created_by'];
    $vals = [':lead_id'=> $lead_id ?: null, ':customer_id'=> $customer_id ?: null, ':project_id'=> $project_id ?: null, ':version'=> 1,
        ':total_amount'=> $total_amount, ':labor_amount'=> 0, ':materials_amount'=> $subtotal, ':margin_percent'=> null, ':status'=> 'draft', ':created_by'=> $created_by];
    if ($has_quote_number) {
        $cols = array_merge($cols, ['quote_number','issue_date','expiration_date','subtotal','discount_type','discount_value','tax_total','notes','internal_notes','currency']);
        $vals[':quote_number'] = $quote_number;
        $vals[':issue_date'] = $issue_date ?: null;
        $vals[':expiration_date'] = $expiration_date ?: null;
        $vals[':subtotal'] = $subtotal;
        $vals[':discount_type'] = $discount_type;
        $vals[':discount_value'] = $discount_value;
        $vals[':tax_total'] = $tax_total;
        $vals[':notes'] = $notes;
        $vals[':internal_notes'] = $internal_notes;
        $vals[':currency'] = $currency;
    }
    $placeholders = array_map(function ($c) { return ':' . $c; }, $cols);
    $sql = "INSERT INTO quotes (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
    $quote_id = (int)$pdo->lastInsertId();

    $has_item_type = $pdo->query("SHOW COLUMNS FROM quote_items LIKE 'type'")->rowCount() > 0;
    foreach ($normalized_items as $it) {
        if ($has_item_type) {
            $pdo->prepare("INSERT INTO quote_items (quote_id, type, name, description, quantity, unit_price, tax_rate, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$quote_id, $it['type'], $it['name'], $it['description'], $it['quantity'], $it['unit_price'], $it['tax_rate'], $it['total']]);
        } else {
            $pdo->prepare("INSERT INTO quote_items (quote_id, floor_type, area_sqft, unit_price, total_price) VALUES (?, ?, ?, ?, ?)")
                ->execute([$quote_id, $it['name'], $it['quantity'], $it['unit_price'], $it['total']]);
        }
    }

    $has_log = $pdo->query("SHOW TABLES LIKE 'quote_activity_log'")->rowCount() > 0;
    if ($has_log) {
        quotes_log_activity($pdo, $quote_id, 'created', $created_by, 'user', ['quote_number' => $quote_number]);
    }

    if ($lead_id && $pdo->query("SHOW TABLES LIKE 'pipeline_stages'")->rowCount() > 0 && $pdo->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage_id'")->rowCount() > 0) {
        $stage = $pdo->query("SELECT id FROM pipeline_stages WHERE slug = 'quote_sent' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($stage) {
            $pdo->prepare("UPDATE leads SET pipeline_stage_id = ? WHERE id = ?")->execute([$stage['id'], $lead_id]);
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $quote_id,
            'quote_number' => $quote_number,
            'total_amount' => $total_amount,
            'subtotal' => $subtotal,
            'status' => 'draft',
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
