<?php
// modules/finance/cost_centers/cost_centers_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $code = trim($data['cost_center_code'] ?? '');
    $name = trim($data['cost_center_name'] ?? '');

    if ($code === '') { http_response_code(422); echo json_encode(['error' => 'Cost center code is required.']); exit; }
    if ($name === '') { http_response_code(422); echo json_encode(['error' => 'Cost center name is required.']); exit; }

    $ref = consumeNextReference('cost_centers');

    $record = supabase_post(SB_API . 'm_cost_centers', [
        'ref'              => $ref,
        'cost_center_code' => $code,
        'cost_center_name' => $name,
        'department'       => trim($data['department']  ?? '') ?: null,
        'manager'          => trim($data['manager']     ?? '') ?: null,
        'budget_amount'    => (float) ($data['budget_amount'] ?? 0),
        'actual_amount'    => (float) ($data['actual_amount'] ?? 0),
        'description'      => trim($data['description'] ?? '') ?: null,
        'status'           => $data['status'] ?? 'active',
        'created_by'       => current_user()['ref'] ?? null,
        'updated_by'       => current_user()['ref'] ?? null,
    ]);

    return $record;
}

function updateRecord(array $data): array {
    $ref  = trim($data['ref']              ?? '');
    $code = trim($data['cost_center_code'] ?? '');
    $name = trim($data['cost_center_name'] ?? '');

    if ($ref  === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']);          exit; }
    if ($code === '') { http_response_code(422); echo json_encode(['error' => 'Cost center code is required.']); exit; }
    if ($name === '') { http_response_code(422); echo json_encode(['error' => 'Cost center name is required.']); exit; }

    return supabase_patch(SB_API . 'm_cost_centers?ref=eq.' . urlencode($ref), [
        'cost_center_code' => $code,
        'cost_center_name' => $name,
        'department'       => trim($data['department']  ?? '') ?: null,
        'manager'          => trim($data['manager']     ?? '') ?: null,
        'budget_amount'    => (float) ($data['budget_amount'] ?? 0),
        'actual_amount'    => (float) ($data['actual_amount'] ?? 0),
        'description'      => trim($data['description'] ?? '') ?: null,
        'status'           => $data['status'] ?? 'active',
        'updated_by'       => current_user()['ref'] ?? null,
        'updated_at'       => date('c'),
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_cost_centers?select=ref,cost_center_code,cost_center_name,department,manager,budget_amount,actual_amount,variance,status&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('cost_centers'));
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_cost_centers', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
