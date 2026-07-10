<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_drivers') {
    echo json_encode(supabase_get(SB_API . 'm_drivers?select=ref,name&status=eq.active&order=name.asc'));
    exit;
}

$month      = $_GET['month']      ?? date('Y-m');
$driver_ref = $_GET['driver_ref'] ?? '';

if ($driver_ref === '') {
    echo json_encode(['error' => 'driver_ref is required']);
    exit;
}

// Date range for the month
$month_start = $month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));

// Driver info
$driver_rows = supabase_get(SB_API . 'm_drivers?select=ref,name&ref=eq.'.$driver_ref);
$driver = $driver_rows[0] ?? [];

// Step 1 — trip earnings for driver in month
$trips = supabase_get(SB_API . 'm_trips?select=driver_salary&driver_ref=eq.'.$driver_ref.'&date=gte.'.$month_start.'&date=lte.'.$month_end);
$trip_earnings = 0;
$trip_count = count($trips);
foreach ($trips as $t) {
    $trip_earnings += floatval($t['driver_salary'] ?? 0);
}

// Step 2 — advances for driver in month
$adv_rows = supabase_get(SB_API . 'm_advance_payments?select=amount&driver_ref=eq.'.$driver_ref.'&recipient_type=eq.driver&date=gte.'.$month_start.'&date=lte.'.$month_end);
$advances = array_sum(array_column($adv_rows, 'amount'));

// Step 3 — deductions for driver in month
$ded_rows = supabase_get(SB_API . 'm_deductions?select=amount&driver_ref=eq.'.$driver_ref.'&recipient_type=eq.driver&date=gte.'.$month_start.'&date=lte.'.$month_end);
$deductions = array_sum(array_column($ded_rows, 'amount'));

// Step 4 — loan recovery (no per-month recovery tracking in schema; using 0)
$loan_recovery = 0;

$gross_earnings = $trip_earnings;
$total_deductions = floatval($advances) + floatval($deductions) + $loan_recovery;
$net_salary = $gross_earnings - $total_deductions;

echo json_encode([
    'driver_ref'      => $driver['ref']  ?? '',
    'driver_name'     => $driver['name'] ?? '',
    'month'           => $month,
    'trip_count'      => $trip_count,
    'trip_earnings'   => $trip_earnings,
    'gross_earnings'  => $gross_earnings,
    'advances'        => floatval($advances),
    'deductions'      => floatval($deductions),
    'loan_recovery'   => $loan_recovery,
    'net_salary'      => $net_salary,
    'note'            => 'loan_recovery is 0 — loans table tracks total recovered_amount, not per-month recovery.',
]);
