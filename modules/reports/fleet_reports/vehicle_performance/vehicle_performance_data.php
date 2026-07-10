<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action     = $_GET['action']     ?? 'report';
$from       = $_GET['from']       ?? date('Y-m-01');
$to         = $_GET['to']         ?? date('Y-m-d');
$vehicle_id = $_GET['vehicle_ref'] ?? '';

if ($action === 'list_vehicles') {
    $rows = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number,make&order=plate_number.asc");
    echo json_encode($rows);
    exit;
}

// report
$vehicles = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number,make,model");

$trip_filter = "date=gte.{$from}&date=lte.{$to}";
$fuel_filter = "date=gte.{$from}&date=lte.{$to}";
$ve_filter   = "date=gte.{$from}&date=lte.{$to}";

if ($vehicle_id !== '' && $vehicle_id !== 'all') {
    $trip_filter .= "&vehicle_ref=eq.{$vehicle_id}";
    $fuel_filter .= "&vehicle_ref=eq.{$vehicle_id}";
    $ve_filter   .= "&vehicle_ref=eq.{$vehicle_id}";
}

$trips = supabase_get("/rest/v1/m_trips?select=vehicle_ref,amount&{$trip_filter}");
$fuel  = supabase_get("/rest/v1/m_fuel_expenses?select=vehicle_ref,total&{$fuel_filter}");
$ve    = supabase_get("/rest/v1/m_vehicle_expenses?select=vehicle_ref,amount&{$ve_filter}");

// Build per-vehicle maps
$rev_map  = [];
$fuel_map = [];
$ve_map   = [];

foreach ($trips as $t) {
    $vid = $t['vehicle_ref'];
    $rev_map[$vid] = ($rev_map[$vid] ?? 0) + floatval($t['amount'] ?? 0);
}
foreach ($fuel as $f) {
    $vid = $f['vehicle_ref'];
    $fuel_map[$vid] = ($fuel_map[$vid] ?? 0) + floatval($f['total'] ?? 0);
}
foreach ($ve as $e) {
    $vid = $e['vehicle_ref'];
    $ve_map[$vid] = ($ve_map[$vid] ?? 0) + floatval($e['amount'] ?? 0);
}

$rows = [];
foreach ($vehicles as $v) {
    $vid     = $v['ref'];
    $revenue    = $rev_map[$vid]  ?? 0;
    $fuel_cost  = $fuel_map[$vid] ?? 0;
    $veh_exp    = $ve_map[$vid]   ?? 0;

    // Only include if there is any activity
    if ($vehicle_id === '' || $vehicle_id === 'all') {
        if ($revenue == 0 && $fuel_cost == 0 && $veh_exp == 0) continue;
    }

    $rows[] = [
        'ref'         => $v['ref'] ?? '',
        'plate_number'=> $v['plate_number'] ?? '',
        'make'        => $v['make'] ?? '',
        'model'       => $v['model'] ?? '',
        'revenue'     => $revenue,
        'fuel_cost'   => $fuel_cost,
        'veh_expense' => $veh_exp,
        'profit'      => $revenue - $fuel_cost - $veh_exp,
    ];
}

usort($rows, fn($a, $b) => $b['profit'] <=> $a['profit']);

$totals = [
    'ref'         => 'TOTALS',
    'plate_number'=> '',
    'make'        => '',
    'model'       => '',
    'revenue'     => array_sum(array_column($rows, 'revenue')),
    'fuel_cost'   => array_sum(array_column($rows, 'fuel_cost')),
    'veh_expense' => array_sum(array_column($rows, 'veh_expense')),
    'profit'      => array_sum(array_column($rows, 'profit')),
];

echo json_encode(['rows' => $rows, 'totals' => $totals]);
