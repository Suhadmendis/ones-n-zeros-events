<?php
// general_expense_entry_data.php — General expense entry endpoint

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveExpense(array $data): array {
    $ref = consumeNextReference('general_expense_entry');

    $expense = supabase_post(SB_API . 'm_general_expenses', [
        'ref'              => $ref,
        'expense_type_ref' => !empty($data['expense_type_ref']) ? $data['expense_type_ref'] : null,
        'remark'          => $data['remark'] ?? '',
        'amount'          => (float) ($data['amount'] ?? 0),
        'date'            => $data['date'] ?: null,
        'created_by'      => current_user()['ref'] ?? null,
        'updated_by'      => current_user()['ref'] ?? null,
    ]);

    $amt = (float) ($data['amount'] ?? 0);
    if ($amt > 0) {
        jnl_create([
            'journal_date' => $data['date'] ?: date('Y-m-d'),
            'description'  => 'General expense: ' . (trim($data['remark'] ?? '') ?: $ref),
            'status'       => 'posted',
            'source_type'  => 'general_expense',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => '5400', 'debit_amount' => $amt, 'credit_amount' => 0,    'description' => 'General Admin Expense'],
                ['account_code' => '1100', 'debit_amount' => 0,    'credit_amount' => $amt, 'description' => 'Cash in Hand'],
            ],
        ]);
    }

    return $expense;
}

function updateExpense(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_general_expenses', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_general_expenses?ref=eq.' . urlencode($ref), [
        'expense_type_ref' => !empty($data['expense_type_ref']) ? $data['expense_type_ref'] : null,
        'remark'          => $data['remark'] ?? '',
        'amount'          => (float) ($data['amount'] ?? 0),
        'date'            => $data['date'] ?: null,
        'updated_by'      => current_user()['ref'] ?? null,
    ]);
}

function listExpenses(): array {
    return supabase_get(
        SB_API . 'm_general_expenses' .
        '?select=ref,expense_type_ref,remark,amount,date,m_general_expense_types(ref,name)' .
        '&order=id.asc'
    );
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listExpenses());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveExpense($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateExpense($body));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_general_expenses', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
