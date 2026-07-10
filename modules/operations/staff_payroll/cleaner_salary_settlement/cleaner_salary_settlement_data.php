<?php
require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $rows = supabase_get(SB_API . 'm_cleaner_salary_settlements?select=id,ref,cleaner_ref,month,gross_earnings,advances,net_payable,status,m_cleaners(ref,name)&order=id.desc');
    echo json_encode($rows);
    exit;
}

if ($action === 'list_cleaners') {
    $rows = supabase_get(SB_API . 'm_cleaners?select=ref,name,phone,status&status=eq.active&order=name.asc');
    echo json_encode($rows);
    exit;
}

if ($action === 'calculate') {
    $cleaner_ref = trim($_GET['cleaner_ref'] ?? '');
    $month       = trim($_GET['month'] ?? '');

    if (!$cleaner_ref || !$month) {
        echo json_encode(['error' => 'cleaner_ref and month are required']);
        exit;
    }

    $month_start = $month . '-01';
    $next = date('Y-m', strtotime($month_start . ' +1 month'));
    $month_end = $next . '-01';

    // gross_earnings from trips: camount
    $trips = supabase_get(SB_API . 'm_trips?select=cleaner_salary&cleaner_ref=eq.'.urlencode($cleaner_ref).'&date=gte.'.$month_start.'&date=lt.'.$month_end);
    $gross_earnings = 0;
    foreach ($trips as $t) {
        $gross_earnings += floatval($t['cleaner_salary'] ?? 0);
    }

    // advances
    $adv_rows = supabase_get(SB_API . 'm_advance_payments?select=amount&cleaner_ref=eq.'.urlencode($cleaner_ref).'&date=gte.'.$month_start.'&date=lt.'.$month_end);
    $advances = 0;
    foreach ($adv_rows as $a) {
        $advances += floatval($a['amount'] ?? 0);
    }

    $net_payable = $gross_earnings - $advances;

    echo json_encode([
        'gross_earnings' => round($gross_earnings, 2),
        'advances'       => round($advances, 2),
        'net_payable'    => round($net_payable, 2),
    ]);
    exit;
}

if ($action === 'save') {
    $data = json_decode(file_get_contents('php://input'), true);

    $ref = consumeNextReference('cleaner_salary_settlement');

    $insert = [
        'ref'            => $ref,
        'cleaner_ref'    => $data['cleaner_ref'],
        'month'          => $data['month'],
        'gross_earnings' => floatval($data['gross_earnings']),
        'advances'       => floatval($data['advances']),
        'net_payable'    => floatval($data['net_payable']),
        'status'         => $data['status'] ?? 'pending',
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ];

    $row   = supabase_post(SB_API . 'm_cleaner_salary_settlements', $insert);
    $gross = floatval($data['gross_earnings']);
    if ($gross > 0) {
        jnl_create([
            'journal_date' => $data['month'] . '-01',
            'description'  => 'Cleaner salary settlement: ' . $ref,
            'status'       => 'posted',
            'source_type'  => 'cleaner_settlement',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => '5310', 'debit_amount' => $gross, 'credit_amount' => 0,      'description' => 'Cleaner Salary Expense'],
                ['account_code' => '2210', 'debit_amount' => 0,       'credit_amount' => $gross, 'description' => 'Cleaner Salaries Payable'],
            ],
        ]);
    }
    echo json_encode(['success' => true, 'ref' => $ref, 'row' => $row]);
    exit;
}

if ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_cleaner_salary_settlements', $ref)) {
        echo json_encode(['success' => false, 'error' => 'Record not found.']);
        exit;
    }

    $update = [
        'cleaner_ref'    => $data['cleaner_ref'],
        'month'          => $data['month'],
        'gross_earnings' => floatval($data['gross_earnings']),
        'advances'       => floatval($data['advances']),
        'net_payable'    => floatval($data['net_payable']),
        'status'         => $data['status'] ?? 'pending',
        'updated_by'     => current_user()['ref'] ?? null,
    ];

    $row = supabase_patch(SB_API . 'm_cleaner_salary_settlements?ref=eq.' . urlencode($ref), $update);
    echo json_encode(['success' => true, 'ref' => $ref, 'row' => $row]);
    exit;
}

if ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_cleaner_salary_settlements', $ref)]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
