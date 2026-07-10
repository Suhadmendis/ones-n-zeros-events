<?php
// modules/finance/cash_bank/cash_bank_data.php

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $account_name     = trim($data['account_name']     ?? '');
    $transaction_type = trim($data['transaction_type'] ?? '');
    $transaction_date = trim($data['transaction_date'] ?? '');
    $amount           = (float) ($data['amount']       ?? 0);

    if ($account_name     === '') { http_response_code(422); echo json_encode(['error' => 'Account name is required.']); exit; }
    if ($transaction_type === '') { http_response_code(422); echo json_encode(['error' => 'Transaction type is required.']); exit; }
    if ($transaction_date === '') { http_response_code(422); echo json_encode(['error' => 'Transaction date is required.']); exit; }
    if ($amount <= 0)             { http_response_code(422); echo json_encode(['error' => 'Amount must be greater than zero.']); exit; }

    $ref = consumeNextReference('cash_bank');

    $record = supabase_post(SB_API . 'm_cash_bank', [
        'ref'              => $ref,
        'account_name'     => $account_name,
        'account_number'   => trim($data['account_number'] ?? '') ?: null,
        'transaction_type' => $transaction_type,
        'transaction_date' => $transaction_date,
        'amount'           => $amount,
        'description'      => trim($data['description']   ?? '') ?: null,
        'reference_doc'    => trim($data['reference_doc'] ?? '') ?: null,
        'status'           => $data['status'] ?? 'pending',
        'created_by'       => current_user()['ref'] ?? null,
        'updated_by'       => current_user()['ref'] ?? null,
    ]);

    switch ($transaction_type) {
        case 'deposit':
            $dr_code = '1110'; $dr_desc = 'Bank Account';   $cr_code = '1100'; $cr_desc = 'Cash in Hand';        break;
        case 'withdrawal':
            $dr_code = '1100'; $dr_desc = 'Cash in Hand';   $cr_code = '1110'; $cr_desc = 'Bank Account';        break;
        case 'receipt':
            $dr_code = '1110'; $dr_desc = 'Bank Account';   $cr_code = '1200'; $cr_desc = 'Accounts Receivable'; break;
        case 'payment':
            $dr_code = '2100'; $dr_desc = 'Accounts Payable'; $cr_code = '1110'; $cr_desc = 'Bank Account';      break;
        default:
            $dr_code = '1110'; $dr_desc = 'Bank Account';   $cr_code = '1100'; $cr_desc = 'Cash in Hand';
    }
    jnl_create([
        'journal_date' => $transaction_date,
        'description'  => 'Cash/bank ' . $transaction_type . ': ' . $account_name . ' [' . $ref . ']',
        'status'       => 'posted',
        'source_type'  => 'cash_bank',
        'source_ref'   => $ref,
        'lines'        => [
            ['account_code' => $dr_code, 'debit_amount' => $amount, 'credit_amount' => 0,       'description' => $dr_desc],
            ['account_code' => $cr_code, 'debit_amount' => 0,       'credit_amount' => $amount, 'description' => $cr_desc],
        ],
    ]);

    return $record;
}

// Updates the cash_bank record only. Does NOT touch the journal entry
// created by saveRecord() on insert — that entry posts straight to the GL
// ('status' => 'posted') and, like all posted entries, must not be
// re-created/duplicated just because the transaction row was edited.
function updateRecord(array $data): array {
    $account_name     = trim($data['account_name']     ?? '');
    $transaction_type = trim($data['transaction_type'] ?? '');
    $transaction_date = trim($data['transaction_date'] ?? '');
    $amount           = (float) ($data['amount']       ?? 0);

    if ($account_name     === '') { http_response_code(422); echo json_encode(['error' => 'Account name is required.']); exit; }
    if ($transaction_type === '') { http_response_code(422); echo json_encode(['error' => 'Transaction type is required.']); exit; }
    if ($transaction_date === '') { http_response_code(422); echo json_encode(['error' => 'Transaction date is required.']); exit; }
    if ($amount <= 0)             { http_response_code(422); echo json_encode(['error' => 'Amount must be greater than zero.']); exit; }

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_cash_bank', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_cash_bank?ref=eq.' . urlencode($ref), [
        'account_name'     => $account_name,
        'account_number'   => trim($data['account_number'] ?? '') ?: null,
        'transaction_type' => $transaction_type,
        'transaction_date' => $transaction_date,
        'amount'           => $amount,
        'description'      => trim($data['description']   ?? '') ?: null,
        'reference_doc'    => trim($data['reference_doc'] ?? '') ?: null,
        'status'           => $data['status'] ?? 'pending',
        'updated_by'       => current_user()['ref'] ?? null,
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_cash_bank?select=ref,account_name,account_number,transaction_type,transaction_date,amount,description,reference_doc,status&order=transaction_date.desc,id.desc');
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_cash_bank', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
