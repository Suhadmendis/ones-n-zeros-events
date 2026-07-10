<?php
// employee_advance_data.php — Employee advance module endpoint

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveAdvance(array $data): array {
    $employee_ref = trim($data['employee_ref'] ?? '');

    if ($employee_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Employee is required.']);
        exit;
    }

    $ref = consumeNextReference('employee_advance');

    $payment = supabase_post(SB_API . 'm_advance_payments', [
        'ref'          => $ref,
        'employee_ref' => $employee_ref,
        'date'         => $data['date']   ?? date('Y-m-d'),
        'amount'       => (float) ($data['amount'] ?? 0),
        'created_by'   => current_user()['ref'] ?? null,
        'updated_by'   => current_user()['ref'] ?? null,
    ]);

    $amt = (float) ($data['amount'] ?? 0);
    if ($amt > 0) {
        jnl_create([
            'journal_date' => $data['date'] ?? date('Y-m-d'),
            'description'  => 'Advance to employee: ' . $ref,
            'status'       => 'posted',
            'source_type'  => 'employee_advance',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => '1210', 'debit_amount' => $amt, 'credit_amount' => 0,    'description' => 'Staff Advances Receivable'],
                ['account_code' => '1100', 'debit_amount' => 0,    'credit_amount' => $amt, 'description' => 'Cash in Hand'],
            ],
        ]);
    }

    return $payment;
}

function updateAdvance(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_advance_payments', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $employee_ref = trim($data['employee_ref'] ?? '');
    if ($employee_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Employee is required.']);
        exit;
    }

    return supabase_patch(SB_API . 'm_advance_payments?ref=eq.' . urlencode($ref), [
        'employee_ref' => $employee_ref,
        'date'         => $data['date']   ?? date('Y-m-d'),
        'amount'       => (float) ($data['amount'] ?? 0),
        'updated_by'   => current_user()['ref'] ?? null,
    ]);
}

function listAdvances(): array {
    return supabase_get(SB_API . 'm_advance_payments?select=id,ref,employee_ref,date,amount,m_employees(ref,full_name)&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listAdvances());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveAdvance($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateAdvance($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_advance_payments', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
