<?php
// modules/finance/tax_management/tax_management_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $tax_name       = trim($data['tax_name']      ?? '');
    $tax_code       = trim($data['tax_code']       ?? '');
    $tax_type       = trim($data['tax_type']       ?? '');
    $effective_date = trim($data['effective_date'] ?? '');
    $rate           = isset($data['rate']) && $data['rate'] !== '' ? (float) $data['rate'] : null;

    if ($tax_name       === '') { http_response_code(422); echo json_encode(['error' => 'Tax name is required.']);       exit; }
    if ($tax_code       === '') { http_response_code(422); echo json_encode(['error' => 'Tax code is required.']);       exit; }
    if ($tax_type       === '') { http_response_code(422); echo json_encode(['error' => 'Tax type is required.']);        exit; }
    if ($effective_date === '') { http_response_code(422); echo json_encode(['error' => 'Effective date is required.']); exit; }
    if ($rate === null)         { http_response_code(422); echo json_encode(['error' => 'Rate is required.']);           exit; }

    $ref = consumeNextReference('tax_management');

    $record = supabase_post(SB_API . 'm_tax_management', [
        'ref'            => $ref,
        'tax_name'       => $tax_name,
        'tax_code'       => $tax_code,
        'tax_type'       => $tax_type,
        'rate'           => $rate,
        'applicable_on'  => $data['applicable_on']  ?? 'Both',
        'effective_date' => $effective_date,
        'expiry_date'    => trim($data['expiry_date'] ?? '') ?: null,
        'description'    => trim($data['description'] ?? '') ?: null,
        'status'         => $data['status'] ?? 'active',
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ]);

    return $record;
}

function updateRecord(array $data): array {
    $ref            = trim($data['ref']            ?? '');
    $tax_name       = trim($data['tax_name']       ?? '');
    $tax_code       = trim($data['tax_code']       ?? '');
    $tax_type       = trim($data['tax_type']       ?? '');
    $effective_date = trim($data['effective_date'] ?? '');
    $rate           = isset($data['rate']) && $data['rate'] !== '' ? (float) $data['rate'] : null;

    if ($ref            === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']);       exit; }
    if ($tax_name       === '') { http_response_code(422); echo json_encode(['error' => 'Tax name is required.']);       exit; }
    if ($tax_code       === '') { http_response_code(422); echo json_encode(['error' => 'Tax code is required.']);       exit; }
    if ($tax_type       === '') { http_response_code(422); echo json_encode(['error' => 'Tax type is required.']);        exit; }
    if ($effective_date === '') { http_response_code(422); echo json_encode(['error' => 'Effective date is required.']); exit; }
    if ($rate === null)         { http_response_code(422); echo json_encode(['error' => 'Rate is required.']);           exit; }

    return supabase_patch(SB_API . 'm_tax_management?ref=eq.' . urlencode($ref), [
        'tax_name'      => $tax_name,
        'tax_code'      => $tax_code,
        'tax_type'      => $tax_type,
        'rate'          => $rate,
        'applicable_on' => $data['applicable_on']  ?? 'Both',
        'effective_date'=> $effective_date,
        'expiry_date'   => trim($data['expiry_date'] ?? '') ?: null,
        'description'   => trim($data['description'] ?? '') ?: null,
        'status'        => $data['status'] ?? 'active',
        'updated_by'    => current_user()['ref'] ?? null,
        'updated_at'    => date('c'),
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_tax_management?select=ref,tax_name,tax_code,tax_type,rate,applicable_on,effective_date,expiry_date,status&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('tax_management'));
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_tax_management', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
