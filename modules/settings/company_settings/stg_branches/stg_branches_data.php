<?php
// modules/settings/company_settings/stg_branches/stg_branches_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $name = trim($data['branch_name'] ?? '');
    $code = strtoupper(trim($data['branch_code'] ?? ''));

    if ($name === '') { http_response_code(422); echo json_encode(['error' => 'Branch name is required.']); exit; }
    if ($code === '') { http_response_code(422); echo json_encode(['error' => 'Branch code is required.']); exit; }

    $ref = consumeNextReference('stg_branches');

    return supabase_post(SB_API . 'm_branches', [
        'ref'            => $ref,
        'branch_name'    => $name,
        'branch_code'    => $code,
        'address'        => trim($data['address']      ?? '') ?: null,
        'city'           => trim($data['city']          ?? '') ?: null,
        'phone'          => trim($data['phone']         ?? '') ?: null,
        'email'          => trim($data['email']         ?? '') ?: null,
        'manager_name'   => trim($data['manager_name']  ?? '') ?: null,
        'is_head_office' => !empty($data['is_head_office']),
        'status'         => $data['status'] ?? 'active',
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ]);
}

function updateRecord(array $data): array {
    $ref  = trim($data['ref'] ?? '');
    $name = trim($data['branch_name'] ?? '');
    $code = strtoupper(trim($data['branch_code'] ?? ''));

    if ($ref  === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']);   exit; }
    if ($name === '') { http_response_code(422); echo json_encode(['error' => 'Branch name is required.']); exit; }
    if ($code === '') { http_response_code(422); echo json_encode(['error' => 'Branch code is required.']); exit; }

    return supabase_patch(SB_API . 'm_branches?ref=eq.' . urlencode($ref), [
        'branch_name'    => $name,
        'branch_code'    => $code,
        'address'        => trim($data['address']      ?? '') ?: null,
        'city'           => trim($data['city']          ?? '') ?: null,
        'phone'          => trim($data['phone']         ?? '') ?: null,
        'email'          => trim($data['email']         ?? '') ?: null,
        'manager_name'   => trim($data['manager_name']  ?? '') ?: null,
        'is_head_office' => !empty($data['is_head_office']),
        'status'         => $data['status'] ?? 'active',
        'updated_by'     => current_user()['ref'] ?? null,
        'updated_at'     => date('c'),
    ]);
}

function listRecords(): array {
    return supabase_get(
        SB_API . 'm_branches?select=ref,branch_name,branch_code,address,city,phone,email,manager_name,is_head_office,status&order=id.desc'
    );
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('stg_branches'));
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_branches', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
