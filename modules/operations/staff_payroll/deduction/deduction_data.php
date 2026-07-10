<?php
require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'next_ref';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('deduction'));
    exit;
}

if ($action === 'list') {
    $rows = supabase_get(SB_API . 'm_deductions?select=id,ref,recipient_type,driver_ref,cleaner_ref,date,amount,reason,m_drivers(ref,name),m_cleaners(ref,name)&order=id.desc');
    $result = array_map(function($r) {
        $recipient_ref  = $r['driver_ref'] ?? $r['cleaner_ref'] ?? '';
        $recipient_name = $r['drivers']['name'] ?? $r['cleaners']['name'] ?? '';
        return [
            'id'             => $r['id'],
            'ref'            => $r['ref'],
            'date'           => $r['date'],
            'recipient_type' => $r['recipient_type'],
            'driver_ref'     => $r['driver_ref'],
            'cleaner_ref'    => $r['cleaner_ref'],
            'recipient_ref'  => $recipient_ref,
            'recipient_name' => $recipient_name,
            'amount'         => $r['amount'],
            'reason'         => $r['reason'],
        ];
    }, $rows);
    echo json_encode($result);
    exit;
}

if ($action === 'list_drivers') {
    $rows = supabase_get(SB_API . 'm_drivers?select=ref,name,phone,status&status=eq.active&order=name.asc');
    echo json_encode($rows);
    exit;
}

if ($action === 'list_cleaners') {
    $rows = supabase_get(SB_API . 'm_cleaners?select=ref,name,phone,status&status=eq.active&order=name.asc');
    echo json_encode($rows);
    exit;
}

if ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $recipient_type = trim($input['recipient_type'] ?? '');
    $driver_ref     = trim($input['driver_ref']  ?? '');
    $cleaner_ref    = trim($input['cleaner_ref'] ?? '');
    $date           = trim($input['date'] ?? '');
    $amount         = $input['amount'] ?? null;
    $reason         = trim($input['reason'] ?? '');

    if (!$recipient_type || !$date || $amount === null || $reason === '') {
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }
    if ($recipient_type === 'driver' && !$driver_ref) {
        echo json_encode(['success' => false, 'error' => 'Driver is required.']);
        exit;
    }
    if ($recipient_type === 'cleaner' && !$cleaner_ref) {
        echo json_encode(['success' => false, 'error' => 'Cleaner is required.']);
        exit;
    }

    $ref = consumeNextReference('deduction');

    $payload = [
        'ref'            => $ref,
        'recipient_type' => $recipient_type,
        'driver_ref'     => $recipient_type === 'driver'  ? $driver_ref  : null,
        'cleaner_ref'    => $recipient_type === 'cleaner' ? $cleaner_ref : null,
        'date'           => $date,
        'amount'         => (float)$amount,
        'reason'         => $reason,
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ];

    $row = supabase_post(SB_API . 'm_deductions', $payload);

    if (empty($row['id'])) {
        echo json_encode(['success' => false, 'error' => 'Insert failed.']);
        exit;
    }

    $dedAmt         = (float)$amount;
    $payableAccount = $recipient_type === 'driver' ? '2200' : '2210';
    $payableDesc    = $recipient_type === 'driver' ? 'Driver Salaries Payable' : 'Cleaner Salaries Payable';
    if ($dedAmt > 0) {
        jnl_create([
            'journal_date' => $date,
            'description'  => 'Deduction (' . $reason . '): ' . $ref,
            'status'       => 'posted',
            'source_type'  => 'deduction',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => $payableAccount, 'debit_amount' => $dedAmt, 'credit_amount' => 0,       'description' => $payableDesc],
                ['account_code' => '1210',           'debit_amount' => 0,       'credit_amount' => $dedAmt, 'description' => 'Staff Advances Receivable'],
            ],
        ]);
    }

    echo json_encode(['success' => true, 'id' => $row['id'], 'ref' => $row['ref']]);
    exit;
}

if ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $ref            = trim($input['ref'] ?? '');
    $recipient_type = trim($input['recipient_type'] ?? '');
    $driver_ref     = trim($input['driver_ref']  ?? '');
    $cleaner_ref    = trim($input['cleaner_ref'] ?? '');
    $date           = trim($input['date'] ?? '');
    $amount         = $input['amount'] ?? null;
    $reason         = trim($input['reason'] ?? '');

    if ($ref === '' || !recordExists('m_deductions', $ref)) {
        echo json_encode(['success' => false, 'error' => 'Record not found.']);
        exit;
    }
    if (!$recipient_type || !$date || $amount === null || $reason === '') {
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }
    if ($recipient_type === 'driver' && !$driver_ref) {
        echo json_encode(['success' => false, 'error' => 'Driver is required.']);
        exit;
    }
    if ($recipient_type === 'cleaner' && !$cleaner_ref) {
        echo json_encode(['success' => false, 'error' => 'Cleaner is required.']);
        exit;
    }

    $payload = [
        'recipient_type' => $recipient_type,
        'driver_ref'     => $recipient_type === 'driver'  ? $driver_ref  : null,
        'cleaner_ref'    => $recipient_type === 'cleaner' ? $cleaner_ref : null,
        'date'           => $date,
        'amount'         => (float)$amount,
        'reason'         => $reason,
        'updated_by'     => current_user()['ref'] ?? null,
    ];

    $row = supabase_patch(SB_API . 'm_deductions?ref=eq.' . urlencode($ref), $payload);

    if (empty($row['id'])) {
        echo json_encode(['success' => false, 'error' => 'Update failed.']);
        exit;
    }

    echo json_encode(['success' => true, 'id' => $row['id'], 'ref' => $row['ref']]);
    exit;
}

if ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_deductions', $ref)]);
    exit;
}

echo json_encode(['error' => 'Unknown action.']);
