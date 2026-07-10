<?php
// cash_flow_data.php — Cash flow entry module endpoint

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function listCashFlowEntries(): array {
    return supabase_get(SB_API . 'm_cash_flow_entries?select=id,ref,date,flow_type,category,amount,description&order=date.desc,id.desc');
}

function saveCashFlowEntry(array $data): array {
    $date      = trim($data['date']      ?? '');
    $flow_type = trim($data['flow_type'] ?? '');
    $category  = trim($data['category'] ?? '');
    $amount    = $data['amount'] ?? '';

    if ($date === '' || $flow_type === '' || $category === '' || $amount === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Date, flow type, category, and amount are required.']);
        exit;
    }

    if (!in_array($flow_type, ['inflow', 'outflow'], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'Flow type must be inflow or outflow.']);
        exit;
    }

    $ref = consumeNextReference('cash_flow');

    $entry = supabase_post(SB_API . 'm_cash_flow_entries', [
        'ref'         => $ref,
        'date'        => $date,
        'flow_type'   => $flow_type,
        'category'    => $category,
        'amount'      => (float) $amount,
        'description' => trim($data['description'] ?? '') ?: null,
        'created_by'  => current_user()['ref'] ?? null,
        'updated_by'  => current_user()['ref'] ?? null,
    ]);

    $amt = (float) $amount;
    if ($amt > 0) {
        $lines = $flow_type === 'inflow'
            ? [
                ['account_code' => '1100', 'debit_amount' => $amt, 'credit_amount' => 0,    'description' => 'Cash in Hand'],
                ['account_code' => '4010', 'debit_amount' => 0,    'credit_amount' => $amt, 'description' => 'Other Income'],
              ]
            : [
                ['account_code' => '5400', 'debit_amount' => $amt, 'credit_amount' => 0,    'description' => 'General Admin Expense'],
                ['account_code' => '1100', 'debit_amount' => 0,    'credit_amount' => $amt, 'description' => 'Cash in Hand'],
              ];
        jnl_create([
            'journal_date' => $date,
            'description'  => 'Cash ' . $flow_type . ': ' . $category . ' [' . $ref . ']',
            'status'       => 'posted',
            'source_type'  => 'cash_flow',
            'source_ref'   => $ref,
            'lines'        => $lines,
        ]);
    }

    return $entry;
}

function updateCashFlowEntry(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_cash_flow_entries', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $date      = trim($data['date']      ?? '');
    $flow_type = trim($data['flow_type'] ?? '');
    $category  = trim($data['category'] ?? '');
    $amount    = $data['amount'] ?? '';

    if ($date === '' || $flow_type === '' || $category === '' || $amount === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Date, flow type, category, and amount are required.']);
        exit;
    }

    if (!in_array($flow_type, ['inflow', 'outflow'], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'Flow type must be inflow or outflow.']);
        exit;
    }

    return supabase_patch(SB_API . 'm_cash_flow_entries?ref=eq.' . urlencode($ref), [
        'date'        => $date,
        'flow_type'   => $flow_type,
        'category'    => $category,
        'amount'      => (float) $amount,
        'description' => trim($data['description'] ?? '') ?: null,
        'updated_by'  => current_user()['ref'] ?? null,
    ]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listCashFlowEntries());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveCashFlowEntry($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateCashFlowEntry($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_cash_flow_entries', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
