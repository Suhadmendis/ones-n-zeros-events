<?php
// modules/finance/approval_workflow/approval_workflow_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function buildPayload(array $data): array {
    return [
        'workflow_name'  => trim($data['workflow_name']  ?? ''),
        'module'         => trim($data['module']         ?? ''),
        'approver_name'  => trim($data['approver_name']  ?? ''),
        'approver_ref'   => trim($data['approver_ref']   ?? '') ?: null,
        'min_amount'     => isset($data['min_amount']) && $data['min_amount'] !== '' ? (float) $data['min_amount'] : null,
        'max_amount'     => isset($data['max_amount']) && $data['max_amount'] !== '' ? (float) $data['max_amount'] : null,
        'approval_order' => (int) ($data['approval_order'] ?? 1),
        'is_mandatory'   => !empty($data['is_mandatory']),
        'description'    => trim($data['description']    ?? '') ?: null,
        'status'         => $data['status'] ?? 'active',
    ];
}

function validatePayload(array $p): void {
    if ($p['workflow_name'] === '') { http_response_code(422); echo json_encode(['error' => 'Workflow name is required.']); exit; }
    if ($p['module']        === '') { http_response_code(422); echo json_encode(['error' => 'Module is required.']);        exit; }
    if ($p['approver_name'] === '') { http_response_code(422); echo json_encode(['error' => 'Approver name is required.']); exit; }
}

function saveRecord(array $data): array {
    $payload = buildPayload($data);
    validatePayload($payload);

    $payload['ref']        = consumeNextReference('approval_workflow');
    $payload['created_by'] = current_user()['ref'] ?? null;
    $payload['updated_by'] = current_user()['ref'] ?? null;

    $record = supabase_post(SB_API . 'm_approval_workflow', $payload);

    return $record;
}

function updateRecord(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']); exit; }

    $payload = buildPayload($data);
    validatePayload($payload);

    $payload['updated_by'] = current_user()['ref'] ?? null;
    $payload['updated_at'] = date('c');

    return supabase_patch(SB_API . 'm_approval_workflow?ref=eq.' . urlencode($ref), $payload);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_approval_workflow?select=ref,workflow_name,module,approver_name,approval_order,is_mandatory,min_amount,max_amount,status&order=module.asc,approval_order.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('approval_workflow'));
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_approval_workflow', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
