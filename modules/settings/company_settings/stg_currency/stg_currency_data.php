<?php
// modules/settings/company_settings/stg_currency/stg_currency_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $code           = strtoupper(trim($data['currency_code']  ?? ''));
    $name           = trim($data['currency_name']             ?? '');
    $effective_date = trim($data['effective_date']            ?? '');
    $rate           = isset($data['exchange_rate']) && $data['exchange_rate'] !== '' ? (float) $data['exchange_rate'] : null;

    if ($code           === '') { http_response_code(422); echo json_encode(['error' => 'Currency code is required.']);  exit; }
    if ($name           === '') { http_response_code(422); echo json_encode(['error' => 'Currency name is required.']);  exit; }
    if ($effective_date === '') { http_response_code(422); echo json_encode(['error' => 'Effective date is required.']); exit; }
    if ($rate === null || $rate <= 0) { http_response_code(422); echo json_encode(['error' => 'Exchange rate must be greater than zero.']); exit; }

    $ref = consumeNextReference('stg_currency');

    $record = supabase_post(SB_API . 'm_currencies', [
        'ref'              => $ref,
        'currency_code'    => $code,
        'currency_name'    => $name,
        'symbol'           => trim($data['symbol'] ?? '') ?: null,
        'exchange_rate'    => $rate,
        'is_base_currency' => !empty($data['is_base_currency']),
        'effective_date'   => $effective_date,
        'description'      => trim($data['description'] ?? '') ?: null,
        'status'           => $data['status'] ?? 'active',
        'created_by'       => current_user()['ref'] ?? null,
        'updated_by'       => current_user()['ref'] ?? null,
    ]);

    return $record;
}

function updateRecord(array $data): array {
    $ref            = trim($data['ref']            ?? '');
    $code           = strtoupper(trim($data['currency_code'] ?? ''));
    $name           = trim($data['currency_name']  ?? '');
    $effective_date = trim($data['effective_date'] ?? '');
    $rate           = isset($data['exchange_rate']) && $data['exchange_rate'] !== '' ? (float) $data['exchange_rate'] : null;

    if ($ref            === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']);       exit; }
    if ($code           === '') { http_response_code(422); echo json_encode(['error' => 'Currency code is required.']);  exit; }
    if ($name           === '') { http_response_code(422); echo json_encode(['error' => 'Currency name is required.']);  exit; }
    if ($effective_date === '') { http_response_code(422); echo json_encode(['error' => 'Effective date is required.']); exit; }
    if ($rate === null || $rate <= 0) { http_response_code(422); echo json_encode(['error' => 'Exchange rate must be greater than zero.']); exit; }

    return supabase_patch(SB_API . 'm_currencies?ref=eq.' . urlencode($ref), [
        'currency_code'    => $code,
        'currency_name'    => $name,
        'symbol'           => trim($data['symbol'] ?? '') ?: null,
        'exchange_rate'    => $rate,
        'is_base_currency' => !empty($data['is_base_currency']),
        'effective_date'   => $effective_date,
        'description'      => trim($data['description'] ?? '') ?: null,
        'status'           => $data['status'] ?? 'active',
        'updated_by'       => current_user()['ref'] ?? null,
        'updated_at'       => date('c'),
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_currencies?select=ref,currency_code,currency_name,symbol,exchange_rate,is_base_currency,effective_date,status&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('stg_currency'));
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_currencies', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
