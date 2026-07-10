<?php
// modules/finance/accounts_payable/accounts_payable_data.php

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $supplier_name = trim($data['supplier_name'] ?? '');
    $invoice_date  = trim($data['invoice_date']  ?? '');
    $due_date      = trim($data['due_date']       ?? '');
    $amount        = (float) ($data['amount']     ?? 0);

    if ($supplier_name === '') { http_response_code(422); echo json_encode(['error' => 'Supplier name is required.']); exit; }
    if ($invoice_date  === '') { http_response_code(422); echo json_encode(['error' => 'Invoice date is required.']); exit; }
    if ($due_date      === '') { http_response_code(422); echo json_encode(['error' => 'Due date is required.']); exit; }
    if ($amount <= 0)          { http_response_code(422); echo json_encode(['error' => 'Invoice amount must be greater than zero.']); exit; }

    $ref = consumeNextReference('accounts_payable');

    $record = supabase_post(SB_API . 'm_accounts_payable', [
        'ref'           => $ref,
        'supplier_name' => $supplier_name,
        'supplier_ref'  => trim($data['supplier_ref'] ?? '') ?: null,
        'invoice_ref'   => trim($data['invoice_ref']  ?? '') ?: null,
        'invoice_date'  => $invoice_date,
        'due_date'      => $due_date,
        'amount'        => $amount,
        'amount_paid'   => (float) ($data['amount_paid'] ?? 0),
        'description'   => trim($data['description'] ?? '') ?: null,
        'status'        => $data['status'] ?? 'open',
        'created_by'    => current_user()['ref'] ?? null,
        'updated_by'    => current_user()['ref'] ?? null,
    ]);

    jnl_create([
        'journal_date'  => $invoice_date,
        'description'   => 'Accounts payable: ' . $supplier_name . ' [' . $ref . ']',
        'status'        => 'draft',
        'source_type'   => 'accounts_payable',
        'source_ref'    => $ref,
        'reference_doc' => trim($data['invoice_ref'] ?? '') ?: null,
        'lines'         => [
            ['account_code' => '5400', 'debit_amount' => $amount, 'credit_amount' => 0,       'description' => 'General Admin Expense'],
            ['account_code' => '2100', 'debit_amount' => 0,       'credit_amount' => $amount, 'description' => 'Accounts Payable'],
        ],
    ]);

    return $record;
}

// Updates the accounts_payable record only. Does NOT touch the journal entry
// created by saveRecord() on insert — GL postings are immutable history and
// must not be re-created/duplicated just because the invoice row was edited.
function updateRecord(array $data): array {
    $supplier_name = trim($data['supplier_name'] ?? '');
    $invoice_date  = trim($data['invoice_date']  ?? '');
    $due_date      = trim($data['due_date']       ?? '');
    $amount        = (float) ($data['amount']     ?? 0);

    if ($supplier_name === '') { http_response_code(422); echo json_encode(['error' => 'Supplier name is required.']); exit; }
    if ($invoice_date  === '') { http_response_code(422); echo json_encode(['error' => 'Invoice date is required.']); exit; }
    if ($due_date      === '') { http_response_code(422); echo json_encode(['error' => 'Due date is required.']); exit; }
    if ($amount <= 0)          { http_response_code(422); echo json_encode(['error' => 'Invoice amount must be greater than zero.']); exit; }

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_accounts_payable', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_accounts_payable?ref=eq.' . urlencode($ref), [
        'supplier_name' => $supplier_name,
        'supplier_ref'  => trim($data['supplier_ref'] ?? '') ?: null,
        'invoice_ref'   => trim($data['invoice_ref']  ?? '') ?: null,
        'invoice_date'  => $invoice_date,
        'due_date'      => $due_date,
        'amount'        => $amount,
        'amount_paid'   => (float) ($data['amount_paid'] ?? 0),
        'description'   => trim($data['description'] ?? '') ?: null,
        'status'        => $data['status'] ?? 'open',
        'updated_by'    => current_user()['ref'] ?? null,
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_accounts_payable?select=ref,supplier_name,supplier_ref,invoice_ref,invoice_date,due_date,amount,amount_paid,balance,description,status&order=invoice_date.desc,id.desc');
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_accounts_payable', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
