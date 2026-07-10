<?php
// modules/finance/bank_reconciliation/bank_reconciliation_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function buildPayload(array $data): array {
    return [
        'account_name'          => trim($data['account_name']          ?? ''),
        'account_number'        => trim($data['account_number']        ?? '') ?: null,
        'reconciliation_date'   => trim($data['reconciliation_date']   ?? ''),
        'period'                => trim($data['period']                ?? ''),
        'bank_balance'          => (float) ($data['bank_balance']          ?? 0),
        'book_balance'          => (float) ($data['book_balance']          ?? 0),
        'outstanding_deposits'  => (float) ($data['outstanding_deposits']  ?? 0),
        'outstanding_cheques'   => (float) ($data['outstanding_cheques']   ?? 0),
        'notes'                 => trim($data['notes']                 ?? '') ?: null,
        'status'                => $data['status'] ?? 'draft',
    ];
}

function validatePayload(array $p): void {
    if ($p['account_name']        === '') { http_response_code(422); echo json_encode(['error' => 'Account name is required.']);        exit; }
    if ($p['reconciliation_date'] === '') { http_response_code(422); echo json_encode(['error' => 'Reconciliation date is required.']); exit; }
    if ($p['period']              === '') { http_response_code(422); echo json_encode(['error' => 'Period is required.']);               exit; }
}

function saveRecord(array $data): array {
    $payload = buildPayload($data);
    validatePayload($payload);

    $payload['ref']        = consumeNextReference('bank_reconciliation');
    $payload['created_by'] = current_user()['ref'] ?? null;
    $payload['updated_by'] = current_user()['ref'] ?? null;

    $record = supabase_post(SB_API . 'm_bank_reconciliation', $payload);

    return $record;
}

function updateRecord(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']); exit; }

    $payload = buildPayload($data);
    validatePayload($payload);

    $payload['updated_by'] = current_user()['ref'] ?? null;
    $payload['updated_at'] = date('c');

    return supabase_patch(SB_API . 'm_bank_reconciliation?ref=eq.' . urlencode($ref), $payload);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_bank_reconciliation?select=ref,account_name,account_number,period,reconciliation_date,bank_balance,book_balance,adjusted_bank_balance,difference,status&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('bank_reconciliation'));
} elseif ($action === 'list') {
    echo json_encode(listRecords());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveRecord($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateRecord($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_bank_reconciliation', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
