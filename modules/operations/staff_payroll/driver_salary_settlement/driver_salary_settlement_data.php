<?php
require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $rows = supabase_get(SB_API . 'm_driver_salary_settlements?select=id,ref,driver_ref,month,gross_earnings,advances,deductions_total,loan_recovery,net_payable,status,m_drivers(ref,name)&order=id.desc');
    echo json_encode($rows);
    exit;
}

if ($action === 'list_drivers') {
    $rows = supabase_get(SB_API . 'm_drivers?select=ref,name,phone,status&status=eq.active&order=name.asc');
    echo json_encode($rows);
    exit;
}

if ($action === 'calculate') {
    $driver_ref = trim($_GET['driver_ref'] ?? '');
    $month      = $_GET['month'] ?? '';

    if (!$driver_ref || !$month) {
        echo json_encode(['error' => 'driver_ref and month are required']);
        exit;
    }

    // gross_earnings from trips
    $trips = supabase_get(SB_API . 'm_trips?select=driver_salary&driver_ref=eq.'.urlencode($driver_ref).'&date=gte.'.$month.'-01&date=lte.'.$month.'-31');
    $gross = 0;
    foreach ($trips as $t) {
        $gross += floatval($t['driver_salary'] ?? 0);
    }

    // advances
    $adv_rows = supabase_get(SB_API . 'm_advance_payments?select=amount&driver_ref=eq.'.urlencode($driver_ref).'&date=gte.'.$month.'-01&date=lte.'.$month.'-31');
    $advances = 0;
    foreach ($adv_rows as $a) {
        $advances += floatval($a['amount'] ?? 0);
    }

    // deductions
    $ded_rows = supabase_get(SB_API . 'm_deductions?select=amount&driver_ref=eq.'.urlencode($driver_ref).'&date=gte.'.$month.'-01&date=lte.'.$month.'-31');
    $deductions = 0;
    foreach ($ded_rows as $d) {
        $deductions += floatval($d['amount'] ?? 0);
    }

    $loan_recovery = 0;
    $net_payable = $gross - $advances - $deductions - $loan_recovery;

    echo json_encode([
        'gross_earnings'   => round($gross, 2),
        'advances'         => round($advances, 2),
        'deductions_total' => round($deductions, 2),
        'loan_recovery'    => round($loan_recovery, 2),
        'net_payable'      => round($net_payable, 2),
    ]);
    exit;
}

if ($action === 'save') {
    $data = json_decode(file_get_contents('php://input'), true);

    $ref = consumeNextReference('driver_salary_settlement');

    $insert = [
        'ref'              => $ref,
        'driver_ref'       => $data['driver_ref'],
        'month'            => $data['month'],
        'gross_earnings'   => floatval($data['gross_earnings']),
        'advances'         => floatval($data['advances']),
        'deductions_total' => floatval($data['deductions_total']),
        'loan_recovery'    => floatval($data['loan_recovery']),
        'net_payable'      => floatval($data['net_payable']),
        'status'           => $data['status'] ?? 'pending',
        'created_by'       => current_user()['ref'] ?? null,
        'updated_by'       => current_user()['ref'] ?? null,
    ];

    $row   = supabase_post(SB_API . 'm_driver_salary_settlements', $insert);
    $gross = floatval($data['gross_earnings']);
    if ($gross > 0) {
        jnl_create([
            'journal_date' => $data['month'] . '-01',
            'description'  => 'Driver salary settlement: ' . $ref,
            'status'       => 'posted',
            'source_type'  => 'driver_settlement',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => '5300', 'debit_amount' => $gross, 'credit_amount' => 0,      'description' => 'Driver Salary Expense'],
                ['account_code' => '2200', 'debit_amount' => 0,       'credit_amount' => $gross, 'description' => 'Driver Salaries Payable'],
            ],
        ]);
    }
    echo json_encode(['success' => true, 'ref' => $ref, 'row' => $row]);
    exit;
}

if ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_driver_salary_settlements', $ref)) {
        echo json_encode(['success' => false, 'error' => 'Record not found.']);
        exit;
    }

    $update = [
        'driver_ref'       => $data['driver_ref'],
        'month'            => $data['month'],
        'gross_earnings'   => floatval($data['gross_earnings']),
        'advances'         => floatval($data['advances']),
        'deductions_total' => floatval($data['deductions_total']),
        'loan_recovery'    => floatval($data['loan_recovery']),
        'net_payable'      => floatval($data['net_payable']),
        'status'           => $data['status'] ?? 'pending',
        'updated_by'       => current_user()['ref'] ?? null,
    ];

    $row = supabase_patch(SB_API . 'm_driver_salary_settlements?ref=eq.' . urlencode($ref), $update);
    echo json_encode(['success' => true, 'ref' => $ref, 'row' => $row]);
    exit;
}

if ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_driver_salary_settlements', $ref)]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
