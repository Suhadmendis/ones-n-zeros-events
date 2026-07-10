<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list_drivers') {
    $rows = supabase_get(SB_API . 'm_drivers?select=ref,name&order=name.asc&limit=10000');
    echo json_encode($rows);
    exit;
}

if ($action === 'report') {
    $from       = $_GET['from']       ?? date('Y-m-01');
    $to         = $_GET['to']         ?? date('Y-m-d');
    $driver_ref = $_GET['driver_ref'] ?? '';

    $qs = SB_API . 'm_trips?select=ref,date,driver_ref,from_loc,to_loc,amount,driver_salary'
        . '&date=gte.' . $from . '&date=lte.' . $to;
    if ($driver_ref !== '') $qs .= '&driver_ref=eq.' . $driver_ref;
    $qs .= '&order=date.asc&limit=100000';

    $trips   = supabase_get($qs);
    $drivers = supabase_get(SB_API . 'm_drivers?select=ref,name&limit=10000');

    $dMap = [];
    foreach ($drivers as $d) $dMap[$d['ref']] = $d;

    $rows = [];
    $summary = ['total_driver_salary' => 0, 'total_earning' => 0, 'trip_count' => 0];

    foreach ($trips as $t) {
        $did           = $t['driver_ref'] ?? null;
        $drv           = $did ? ($dMap[$did] ?? null) : null;
        $driver_salary = (float)($t['driver_salary'] ?? 0);

        $rows[] = [
            'driver_ref'    => $drv['ref']    ?? '',
            'driver_name'   => $drv['name']   ?? '',
            'trip_ref'      => $t['ref']       ?? '',
            'date'          => $t['date']      ?? '',
            'from_loc'      => $t['from_loc']  ?? '',
            'to_loc'        => $t['to_loc']    ?? '',
            'trip_amount'   => round((float)($t['amount'] ?? 0), 2),
            'driver_salary' => round($driver_salary, 2),
            'total_earning' => round($driver_salary, 2),
        ];

        $summary['total_driver_salary'] += $driver_salary;
        $summary['total_earning']       += $driver_salary;
        $summary['trip_count']++;
    }

    // Sort by driver_name asc, date asc (already date-ordered from API)
    usort($rows, function($a, $b) {
        $cmp = strcmp($a['driver_name'], $b['driver_name']);
        if ($cmp !== 0) return $cmp;
        return strcmp($a['date'], $b['date']);
    });

    $summary['total_driver_salary'] = round($summary['total_driver_salary'], 2);
    $summary['total_earning']       = round($summary['total_earning'], 2);

    echo json_encode(['rows' => $rows, 'summary' => $summary]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
