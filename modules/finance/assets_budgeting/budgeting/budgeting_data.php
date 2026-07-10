<?php
// modules/finance/budgeting/budgeting_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $budget_name  = trim($data['budget_name']  ?? '');
    $account_code = trim($data['account_code'] ?? '');
    $period       = trim($data['period']       ?? '');
    $category     = trim($data['category']     ?? '');
    $budget_amount= (float) ($data['budget_amount'] ?? 0);

    if ($budget_name  === '') { http_response_code(422); echo json_encode(['error' => 'Budget name is required.']); exit; }
    if ($account_code === '') { http_response_code(422); echo json_encode(['error' => 'Account code is required.']); exit; }
    if ($period       === '') { http_response_code(422); echo json_encode(['error' => 'Period is required.']); exit; }
    if ($category     === '') { http_response_code(422); echo json_encode(['error' => 'Category is required.']); exit; }
    if ($budget_amount <= 0)  { http_response_code(422); echo json_encode(['error' => 'Budget amount must be greater than zero.']); exit; }

    $ref = consumeNextReference('budgeting');

    $record = supabase_post(SB_API . 'm_budgets', [
        'ref'           => $ref,
        'budget_name'   => $budget_name,
        'account_code'  => $account_code,
        'period'        => $period,
        'budget_type'   => $data['budget_type']  ?? 'Monthly',
        'category'      => $category,
        'budget_amount' => $budget_amount,
        'actual_amount' => (float) ($data['actual_amount'] ?? 0),
        'notes'         => trim($data['notes'] ?? '') ?: null,
        'status'        => $data['status'] ?? 'draft',
        'created_by'    => current_user()['ref'] ?? null,
        'updated_by'    => current_user()['ref'] ?? null,
    ]);

    return $record;
}

function updateRecord(array $data): array {
    $budget_name  = trim($data['budget_name']  ?? '');
    $account_code = trim($data['account_code'] ?? '');
    $period       = trim($data['period']       ?? '');
    $category     = trim($data['category']     ?? '');
    $budget_amount= (float) ($data['budget_amount'] ?? 0);

    if ($budget_name  === '') { http_response_code(422); echo json_encode(['error' => 'Budget name is required.']); exit; }
    if ($account_code === '') { http_response_code(422); echo json_encode(['error' => 'Account code is required.']); exit; }
    if ($period       === '') { http_response_code(422); echo json_encode(['error' => 'Period is required.']); exit; }
    if ($category     === '') { http_response_code(422); echo json_encode(['error' => 'Category is required.']); exit; }
    if ($budget_amount <= 0)  { http_response_code(422); echo json_encode(['error' => 'Budget amount must be greater than zero.']); exit; }

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_budgets', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_budgets?ref=eq.' . urlencode($ref), [
        'budget_name'   => $budget_name,
        'account_code'  => $account_code,
        'period'        => $period,
        'budget_type'   => $data['budget_type']  ?? 'Monthly',
        'category'      => $category,
        'budget_amount' => $budget_amount,
        'actual_amount' => (float) ($data['actual_amount'] ?? 0),
        'notes'         => trim($data['notes'] ?? '') ?: null,
        'status'        => $data['status'] ?? 'draft',
        'updated_by'    => current_user()['ref'] ?? null,
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_budgets?select=ref,budget_name,account_code,period,budget_type,category,budget_amount,actual_amount,variance,notes,status&order=period.desc,id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listRecords());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveRecord($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateRecord($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_budgets', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
