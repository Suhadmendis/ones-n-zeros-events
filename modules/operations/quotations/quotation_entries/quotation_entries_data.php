<?php
// quotation_entries_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

// t_quotation_lines has no module or number series of its own (by design — it's
// wired into this screen, not a standalone module), so its refs are derived from
// the parent quotation ref rather than consumed from sys_tms_module_number_series.
function saveQuotationLines(string $quotationRef, array $lines): void {
    $lineNo = 0;
    foreach ($lines as $line) {
        $hasLineData = !empty($line['item_name']) || !empty($line['description'])
            || !empty($line['qty']) || !empty($line['unit_price']);
        if (!$hasLineData) continue;

        $lineNo++;

        supabase_post(SB_API . 't_quotation_lines', [
            'ref' => $quotationRef . '-L' . $lineNo,
            'quotation_ref' => $quotationRef,
            'line_no' => $lineNo,
            'item_name' => $line['item_name'] ?? '',
            'description' => !empty($line['description']) ? $line['description'] : null,
            'image' => !empty($line['image']) ? $line['image'] : null,
            'qty' => $line['qty'] ?? 0,
            'unit' => !empty($line['unit']) ? $line['unit'] : null,
            'unit_price' => $line['unit_price'] ?? 0,
            'discount' => $line['discount'] ?? 0,
            'tax' => $line['tax'] ?? 0,
            'amount' => $line['amount'] ?? 0,
            'created_by' => current_user()['ref'] ?? null,
            'updated_by' => current_user()['ref'] ?? null,
        ]);
    }
}

function saveQuotations(array $data): array {
    $ref = consumeNextReference('quotation_entries');

    $quotation = supabase_post(SB_API . 'm_quotations', [
        'ref' => $ref,
        'revision_no' => $data['revision_no'] ?? 0,
        'customer_ref' => !empty($data['customer_ref']) ? $data['customer_ref'] : null,
        'contact_person' => !empty($data['contact_person']) ? $data['contact_person'] : null,
        'subject' => !empty($data['subject']) ? $data['subject'] : null,
        'customer_reference' => !empty($data['customer_reference']) ? $data['customer_reference'] : null,
        'quotation_status_ref' => !empty($data['quotation_status_ref']) ? $data['quotation_status_ref'] : null,
        'quotation_date' => !empty($data['quotation_date']) ? $data['quotation_date'] : null,
        'valid_until' => !empty($data['valid_until']) ? $data['valid_until'] : null,
        'currency_ref' => !empty($data['currency_ref']) ? $data['currency_ref'] : null,
        'salesperson_ref' => !empty($data['salesperson_ref']) ? $data['salesperson_ref'] : null,
        'price_list' => !empty($data['price_list']) ? $data['price_list'] : null,
        'payment_terms' => !empty($data['payment_terms']) ? $data['payment_terms'] : null,
        'delivery_period' => !empty($data['delivery_period']) ? $data['delivery_period'] : null,
        'notes' => $data['notes'] ?? '',
        'terms_conditions' => $data['terms_conditions'] ?? '',
        'internal_notes' => $data['internal_notes'] ?? '',
        'subtotal' => $data['subtotal'] ?? 0,
        'discount' => $data['discount'] ?? 0,
        'tax' => $data['tax'] ?? 0,
        'total_amount' => $data['total_amount'] ?? 0,
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    if (!empty($quotation['ref'])) {
        saveQuotationLines($quotation['ref'], $data['lines'] ?? []);
    }

    return $quotation;
}

function updateQuotations(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_quotations', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $quotation = supabase_patch(SB_API . 'm_quotations?ref=eq.' . urlencode($ref), [
        'revision_no' => $data['revision_no'] ?? 0,
        'customer_ref' => !empty($data['customer_ref']) ? $data['customer_ref'] : null,
        'contact_person' => !empty($data['contact_person']) ? $data['contact_person'] : null,
        'subject' => !empty($data['subject']) ? $data['subject'] : null,
        'customer_reference' => !empty($data['customer_reference']) ? $data['customer_reference'] : null,
        'quotation_status_ref' => !empty($data['quotation_status_ref']) ? $data['quotation_status_ref'] : null,
        'quotation_date' => !empty($data['quotation_date']) ? $data['quotation_date'] : null,
        'valid_until' => !empty($data['valid_until']) ? $data['valid_until'] : null,
        'currency_ref' => !empty($data['currency_ref']) ? $data['currency_ref'] : null,
        'salesperson_ref' => !empty($data['salesperson_ref']) ? $data['salesperson_ref'] : null,
        'price_list' => !empty($data['price_list']) ? $data['price_list'] : null,
        'payment_terms' => !empty($data['payment_terms']) ? $data['payment_terms'] : null,
        'delivery_period' => !empty($data['delivery_period']) ? $data['delivery_period'] : null,
        'notes' => $data['notes'] ?? '',
        'terms_conditions' => $data['terms_conditions'] ?? '',
        'internal_notes' => $data['internal_notes'] ?? '',
        'subtotal' => $data['subtotal'] ?? 0,
        'discount' => $data['discount'] ?? 0,
        'tax' => $data['tax'] ?? 0,
        'total_amount' => $data['total_amount'] ?? 0,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    // Replace the line set wholesale: delete every existing child row for this
    // quotation ref, then reinsert the current set fresh via the same helper
    // saveQuotations() uses — avoids duplicating saveQuotationLines()'s ref-naming
    // (quotationRef . '-L' . lineNo) and null-handling logic.
    supabase_delete(SB_API . 't_quotation_lines?quotation_ref=eq.' . urlencode($ref));
    saveQuotationLines($ref, $data['lines'] ?? []);

    return $quotation;
}

const QUOTATION_SELECT = 'id,ref,revision_no,customer_ref,contact_person,subject,customer_reference,'
    . 'quotation_status_ref,quotation_date,valid_until,currency_ref,salesperson_ref,price_list,'
    . 'payment_terms,delivery_period,notes,terms_conditions,internal_notes,subtotal,discount,tax,total_amount,'
    . 'm_customers(ref,customer_name),m_quotation_statuses(ref,name,display_color),'
    . 'm_currencies(ref,currency_code,currency_name,symbol),m_employees(ref,full_name)';

function listQuotations(): array {
    return supabase_get(SB_API . 'm_quotations?select=' . QUOTATION_SELECT . '&order=id.asc');
}

function getQuotationWithLines(string $ref): array {
    $headers = supabase_get(SB_API . 'm_quotations?select=' . QUOTATION_SELECT . '&ref=eq.' . urlencode($ref) . '&limit=1');
    if (empty($headers)) return [];
    $quotation = $headers[0];

    $quotation['lines'] = supabase_get(SB_API . 't_quotation_lines?select=ref,line_no,item_name,description,image,qty,unit,unit_price,discount,tax,amount&quotation_ref=eq.' . urlencode($ref) . '&order=line_no.asc');

    return $quotation;
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listQuotations());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveQuotations($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateQuotations($body));

} elseif ($action === 'get') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(getQuotationWithLines($ref));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_quotations', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
