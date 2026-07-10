<?php
// modules/finance/chart_of_accounts/chart_of_accounts_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveAccount(array $data): array {
    $account_code = trim($data['account_code'] ?? '');
    $account_name = trim($data['account_name'] ?? '');
    $account_type = trim($data['account_type'] ?? '');

    if ($account_code === '') { http_response_code(422); echo json_encode(['error' => 'Account code is required.']); exit; }
    if ($account_name === '') { http_response_code(422); echo json_encode(['error' => 'Account name is required.']); exit; }
    if ($account_type === '') { http_response_code(422); echo json_encode(['error' => 'Account type is required.']); exit; }

    $ref = consumeNextReference('chart_of_accounts');

    $account = supabase_post(SB_API . 'm_chart_of_accounts', [
        'ref'              => $ref,
        'account_code'     => $account_code,
        'account_name'     => $account_name,
        'account_type'     => $account_type,
        'account_sub_type' => trim($data['account_sub_type'] ?? '') ?: null,
        'parent_code'      => trim($data['parent_code']      ?? '') ?: null,
        'description'      => trim($data['description']      ?? '') ?: null,
        'status'           => $data['status'] ?? 'active',
        'created_by'       => current_user()['ref'] ?? null,
        'updated_by'       => current_user()['ref'] ?? null,
    ]);

    return $account;
}

function updateAccount(array $data): array {
    $account_code = trim($data['account_code'] ?? '');
    $account_name = trim($data['account_name'] ?? '');
    $account_type = trim($data['account_type'] ?? '');

    if ($account_code === '') { http_response_code(422); echo json_encode(['error' => 'Account code is required.']); exit; }
    if ($account_name === '') { http_response_code(422); echo json_encode(['error' => 'Account name is required.']); exit; }
    if ($account_type === '') { http_response_code(422); echo json_encode(['error' => 'Account type is required.']); exit; }

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_chart_of_accounts', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_chart_of_accounts?ref=eq.' . urlencode($ref), [
        'account_code'     => $account_code,
        'account_name'     => $account_name,
        'account_type'     => $account_type,
        'account_sub_type' => trim($data['account_sub_type'] ?? '') ?: null,
        'parent_code'      => trim($data['parent_code']      ?? '') ?: null,
        'description'      => trim($data['description']      ?? '') ?: null,
        'status'           => $data['status'] ?? 'active',
        'updated_by'       => current_user()['ref'] ?? null,
    ]);
}

function listAccounts(): array {
    return supabase_get(SB_API . 'm_chart_of_accounts?select=ref,account_code,account_name,account_type,account_sub_type,parent_code,description,status&order=account_code.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listAccounts());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveAccount($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateAccount($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_chart_of_accounts', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
