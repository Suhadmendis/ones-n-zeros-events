<?php
// driver_advance_data.php — Driver advance module endpoint

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveAdvance(array $data): array {
    $recipient_type = $data['recipient_type'] ?? 'driver';
    $driver_ref     = trim($data['driver_ref']  ?? '');
    $cleaner_ref    = trim($data['cleaner_ref'] ?? '');

    if ($recipient_type === 'driver' && $driver_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Driver is required.']);
        exit;
    }
    if ($recipient_type === 'cleaner' && $cleaner_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Cleaner is required.']);
        exit;
    }

    $ref = consumeNextReference('driver_advance');

    $payment = supabase_post(SB_API . 'm_advance_payments', [
        'ref'            => $ref,
        'recipient_type' => $recipient_type,
        'driver_ref'     => $recipient_type === 'driver'  ? $driver_ref  : null,
        'cleaner_ref'    => $recipient_type === 'cleaner' ? $cleaner_ref : null,
        'date'           => $data['date']   ?? date('Y-m-d'),
        'amount'         => (float) ($data['amount'] ?? 0),
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ]);

    $amt = (float) ($data['amount'] ?? 0);
    if ($amt > 0) {
        jnl_create([
            'journal_date' => $data['date'] ?? date('Y-m-d'),
            'description'  => 'Advance to ' . $recipient_type . ': ' . $ref,
            'status'       => 'posted',
            'source_type'  => 'driver_advance',
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

    $recipient_type = $data['recipient_type'] ?? 'driver';
    $driver_ref     = trim($data['driver_ref']  ?? '');
    $cleaner_ref    = trim($data['cleaner_ref'] ?? '');

    if ($recipient_type === 'driver' && $driver_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Driver is required.']);
        exit;
    }
    if ($recipient_type === 'cleaner' && $cleaner_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Cleaner is required.']);
        exit;
    }

    return supabase_patch(SB_API . 'm_advance_payments?ref=eq.' . urlencode($ref), [
        'recipient_type' => $recipient_type,
        'driver_ref'     => $recipient_type === 'driver'  ? $driver_ref  : null,
        'cleaner_ref'    => $recipient_type === 'cleaner' ? $cleaner_ref : null,
        'date'           => $data['date']   ?? date('Y-m-d'),
        'amount'         => (float) ($data['amount'] ?? 0),
        'updated_by'     => current_user()['ref'] ?? null,
    ]);
}

function listAdvances(): array {
    return supabase_get(SB_API . 'm_advance_payments?select=id,ref,recipient_type,driver_ref,cleaner_ref,date,amount,m_drivers(ref,name),m_cleaners(ref,name)&order=id.desc');
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
