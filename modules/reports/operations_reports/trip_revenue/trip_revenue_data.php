<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list_vehicles') {
    $rows = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number&order=ref.asc&limit=10000');
    echo json_encode($rows);
    exit;
}

if ($action === 'list_drivers') {
    $rows = supabase_get(SB_API . 'm_drivers?select=ref,name&order=name.asc&limit=10000');
    echo json_encode($rows);
    exit;
}

if ($action === 'report') {
    $from        = $_GET['from']        ?? date('Y-m-01');
    $to          = $_GET['to']          ?? date('Y-m-d');
    $vehicle_ref = $_GET['vehicle_ref'] ?? '';
    $driver_ref  = $_GET['driver_ref']  ?? '';

    $qs = SB_API . 'm_trips?select=ref,date,vehicle_ref,driver_ref,from_loc,to_loc,item_name,run_no,amount,driver_salary,cleaner_salary'
        . '&date=gte.' . $from . '&date=lte.' . $to;
    if ($vehicle_ref !== '') $qs .= '&vehicle_ref=eq.' . $vehicle_ref;
    if ($driver_ref  !== '') $qs .= '&driver_ref=eq.'  . $driver_ref;
    $qs .= '&order=date.asc&limit=100000';

    $trips    = supabase_get($qs);
    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number&limit=10000');
    $drivers  = supabase_get(SB_API . 'm_drivers?select=ref,name&limit=10000');

    $vMap = [];
    foreach ($vehicles as $v) $vMap[$v['ref']] = $v['plate_number'];
    $dMap = [];
    foreach ($drivers as $d) $dMap[$d['ref']] = $d['name'];

    $rows = [];
    $summary = ['total_amount' => 0, 'total_driver_salary' => 0, 'total_cleaner_salary' => 0, 'trip_count' => 0];

    foreach ($trips as $t) {
        $amount         = (float)($t['amount']         ?? 0);
        $driver_salary  = (float)($t['driver_salary']  ?? 0);
        $cleaner_salary = (float)($t['cleaner_salary'] ?? 0);

        $rows[] = [
            'ref'            => $t['ref']       ?? '',
            'date'           => $t['date']      ?? '',
            'plate_number'   => $vMap[$t['vehicle_ref'] ?? null] ?? '',
            'driver_name'    => $dMap[$t['driver_ref']  ?? null] ?? '',
            'from_loc'       => $t['from_loc']  ?? '',
            'to_loc'         => $t['to_loc']    ?? '',
            'item_name'      => $t['item_name'] ?? '',
            'run_no'         => $t['run_no']    ?? '',
            'amount'         => round($amount, 2),
            'driver_salary'  => round($driver_salary, 2),
            'cleaner_salary' => round($cleaner_salary, 2),
        ];

        $summary['total_amount']         += $amount;
        $summary['total_driver_salary']  += $driver_salary;
        $summary['total_cleaner_salary'] += $cleaner_salary;
        $summary['trip_count']++;
    }

    $summary['total_amount']         = round($summary['total_amount'],         2);
    $summary['total_driver_salary']  = round($summary['total_driver_salary'],  2);
    $summary['total_cleaner_salary'] = round($summary['total_cleaner_salary'], 2);

    echo json_encode(['rows' => $rows, 'summary' => $summary]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
