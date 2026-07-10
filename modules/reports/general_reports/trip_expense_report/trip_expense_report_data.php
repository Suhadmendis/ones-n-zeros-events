<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');

if ($action !== 'report') {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Fetch trips in range
$trips = supabase_get(
    SB_API . 'm_trips?select=ref,date,vehicle_ref,driver_ref,amount,driver_salary,cleaner_salary,from_loc,to_loc'
    . '&date=gte.' . urlencode($from)
    . '&date=lte.' . urlencode($to)
    . '&order=date.asc,id.asc'
);

// Fetch vehicles and drivers (no date filter needed)
$vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number');
$drivers  = supabase_get(SB_API . 'm_drivers?select=ref,name');

// Build lookup maps
$vehicleMap = [];
foreach ($vehicles as $v) {
    $vehicleMap[$v['ref']] = $v['plate_number'] ?? '';
}
$driverMap = [];
foreach ($drivers as $d) {
    $driverMap[$d['ref']] = $d['name'] ?? '';
}

// Build result rows and summary
$rows = [];
$totalAmount        = 0;
$totalDriverSalary  = 0;
$totalCleanerSalary = 0;

foreach ($trips as $t) {
    $amount         = (float)($t['amount']         ?? 0);
    $driver_salary  = (float)($t['driver_salary']  ?? 0);
    $cleaner_salary = (float)($t['cleaner_salary'] ?? 0);

    $totalAmount        += $amount;
    $totalDriverSalary  += $driver_salary;
    $totalCleanerSalary += $cleaner_salary;

    $rows[] = [
        'ref'            => $t['ref']      ?? '',
        'date'           => $t['date']     ?? '',
        'plate_number'   => $vehicleMap[$t['vehicle_ref']] ?? '',
        'driver_name'    => $driverMap[$t['driver_ref']]   ?? '',
        'from_loc'       => $t['from_loc'] ?? '',
        'to_loc'         => $t['to_loc']   ?? '',
        'amount'         => $amount,
        'driver_salary'  => $driver_salary,
        'cleaner_salary' => $cleaner_salary,
    ];
}

echo json_encode([
    'rows' => $rows,
    'summary' => [
        'trip_count'           => count($rows),
        'total_amount'         => $totalAmount,
        'total_driver_salary'  => $totalDriverSalary,
        'total_cleaner_salary' => $totalCleanerSalary,
    ],
]);
