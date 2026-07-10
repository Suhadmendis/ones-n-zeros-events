<?php
// modules/finance/accounts_receivable/accounts_receivable_data.php

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $customer_name = trim($data['customer_name'] ?? '');
    $invoice_date  = trim($data['invoice_date']  ?? '');
    $due_date      = trim($data['due_date']       ?? '');
    $amount        = (float) ($data['amount']     ?? 0);

    if ($customer_name === '') { http_response_code(422); echo json_encode(['error' => 'Customer name is required.']); exit; }
    if ($invoice_date  === '') { http_response_code(422); echo json_encode(['error' => 'Invoice date is required.']); exit; }
    if ($due_date      === '') { http_response_code(422); echo json_encode(['error' => 'Due date is required.']); exit; }
    if ($amount <= 0)          { http_response_code(422); echo json_encode(['error' => 'Invoice amount must be greater than zero.']); exit; }

    $ref = consumeNextReference('accounts_receivable');

    $record = supabase_post(SB_API . 'm_accounts_receivable', [
        'ref'           => $ref,
        'customer_name' => $customer_name,
        'customer_ref'  => trim($data['customer_ref'] ?? '') ?: null,
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
        'description'   => 'Accounts receivable: ' . $customer_name . ' [' . $ref . ']',
        'status'        => 'draft',
        'source_type'   => 'accounts_receivable',
        'source_ref'    => $ref,
        'reference_doc' => trim($data['invoice_ref'] ?? '') ?: null,
        'lines'         => [
            ['account_code' => '1200', 'debit_amount' => $amount, 'credit_amount' => 0,       'description' => 'Accounts Receivable'],
            ['account_code' => '4000', 'debit_amount' => 0,       'credit_amount' => $amount, 'description' => 'Transport Revenue'],
        ],
    ]);

    return $record;
}

// Updates the accounts_receivable record only. Does NOT touch the journal
// entry created by saveRecord() on insert — GL postings are immutable
// history and must not be re-created/duplicated just because the invoice
// row was edited.
function updateRecord(array $data): array {
    $customer_name = trim($data['customer_name'] ?? '');
    $invoice_date  = trim($data['invoice_date']  ?? '');
    $due_date      = trim($data['due_date']       ?? '');
    $amount        = (float) ($data['amount']     ?? 0);

    if ($customer_name === '') { http_response_code(422); echo json_encode(['error' => 'Customer name is required.']); exit; }
    if ($invoice_date  === '') { http_response_code(422); echo json_encode(['error' => 'Invoice date is required.']); exit; }
    if ($due_date      === '') { http_response_code(422); echo json_encode(['error' => 'Due date is required.']); exit; }
    if ($amount <= 0)          { http_response_code(422); echo json_encode(['error' => 'Invoice amount must be greater than zero.']); exit; }

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_accounts_receivable', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_accounts_receivable?ref=eq.' . urlencode($ref), [
        'customer_name' => $customer_name,
        'customer_ref'  => trim($data['customer_ref'] ?? '') ?: null,
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
    return supabase_get(SB_API . 'm_accounts_receivable?select=ref,customer_name,customer_ref,invoice_ref,invoice_date,due_date,amount,amount_paid,balance,description,status&order=invoice_date.desc,id.desc');
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_accounts_receivable', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
