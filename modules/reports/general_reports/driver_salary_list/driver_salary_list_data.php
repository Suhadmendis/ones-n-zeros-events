<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action     = $_GET['action']     ?? '';
$from       = $_GET['from']       ?? date('Y-m-01');
$to         = $_GET['to']         ?? date('Y-m-d');
$driver_id  = $_GET['driver_ref'] ?? '';

if ($action === 'list_drivers') {
    $drivers = supabase_get(SB_API . 'm_drivers?select=ref,name&status=eq.active&order=name.asc');
    echo json_encode($drivers);
    exit;
}

if ($action !== 'report') {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Fetch trips in date range, optionally filtered by driver
$tripPath = SB_API . 'm_trips?select=ref,date,driver_ref,amount,driver_salary,from_loc,to_loc'
    . '&date=gte.' . urlencode($from)
    . '&date=lte.' . urlencode($to)
    . '&order=date.asc,id.asc';
if ($driver_id !== '') {
    $tripPath .= '&driver_ref=eq.' . urlencode($driver_id);
}
$trips = supabase_get($tripPath);

// Fetch all drivers for name lookup
$drivers = supabase_get(SB_API . 'm_drivers?select=ref,name&order=name.asc');
$driverMap = [];
foreach ($drivers as $d) {
    $driverMap[$d['ref']] = ['ref' => $d['ref'] ?? '', 'name' => $d['name'] ?? ''];
}

// Build rows
$rows = [];
$totalDriverSalary = 0;
$totalEarning      = 0;

foreach ($trips as $t) {
    $driver_salary = (float)($t['driver_salary'] ?? 0);
    $amount        = (float)($t['amount']        ?? 0);
    $drv           = $driverMap[$t['driver_ref']] ?? ['ref' => '', 'name' => 'Unknown'];

    $totalDriverSalary += $driver_salary;
    $totalEarning      += $driver_salary;

    $rows[] = [
        'ref'           => $t['ref']      ?? '',
        'date'          => $t['date']     ?? '',
        'driver_ref'    => $drv['ref'],
        'driver_name'   => $drv['name'],
        'from_loc'      => $t['from_loc'] ?? '',
        'to_loc'        => $t['to_loc']   ?? '',
        'trip_amount'   => $amount,
        'driver_salary' => $driver_salary,
        'total_earning' => $driver_salary,
    ];
}

// Sort by driver_name then date
usort($rows, fn($a, $b) =>
    ($a['driver_name'] <=> $b['driver_name']) ?: ($a['date'] <=> $b['date'])
);

echo json_encode([
    'rows' => $rows,
    'summary' => [
        'trip_count'          => count($rows),
        'total_driver_salary' => $totalDriverSalary,
        'total_earning'       => $totalEarning,
    ],
]);
