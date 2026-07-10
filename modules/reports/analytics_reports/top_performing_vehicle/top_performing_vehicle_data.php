<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($action !== 'report') {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$from  = $_GET['from']  ?? date('Y-m-01');
$to    = $_GET['to']    ?? date('Y-m-t');
$limit = $_GET['limit'] ?? '10';

$vehicles      = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model,year');
$trips         = supabase_get(SB_API . 'm_trips?select=vehicle_ref,amount,mileage&date=gte.' . $from . '&date=lte.' . $to);
$fuel_expenses = supabase_get(SB_API . 'm_fuel_expenses?select=vehicle_ref,total&date=gte.' . $from . '&date=lte.' . $to);
$veh_expenses  = supabase_get(SB_API . 'm_vehicle_expenses?select=vehicle_ref,amount&date=gte.' . $from . '&date=lte.' . $to);

// Index vehicles
$veh_map = [];
foreach ($vehicles as $v) {
    $veh_map[$v['ref']] = $v;
}

// Aggregate per vehicle
$data = [];
foreach ($trips as $t) {
    $vid = $t['vehicle_ref'];
    if (!isset($data[$vid])) {
        $data[$vid] = ['revenue' => 0, 'fuel_cost' => 0, 'veh_cost' => 0, 'trip_count' => 0, 'mileage' => 0];
    }
    $data[$vid]['revenue']    += floatval($t['amount'] ?? 0);
    $data[$vid]['mileage']    += floatval($t['mileage'] ?? 0);
    $data[$vid]['trip_count'] += 1;
}
foreach ($fuel_expenses as $f) {
    $vid = $f['vehicle_ref'];
    if (!isset($data[$vid])) {
        $data[$vid] = ['revenue' => 0, 'fuel_cost' => 0, 'veh_cost' => 0, 'trip_count' => 0, 'mileage' => 0];
    }
    $data[$vid]['fuel_cost'] += floatval($f['total'] ?? 0);
}
foreach ($veh_expenses as $e) {
    $vid = $e['vehicle_ref'];
    if (!isset($data[$vid])) {
        $data[$vid] = ['revenue' => 0, 'fuel_cost' => 0, 'veh_cost' => 0, 'trip_count' => 0, 'mileage' => 0];
    }
    $data[$vid]['veh_cost'] += floatval($e['amount'] ?? 0);
}

// Build rows
$rows = [];
foreach ($data as $vid => $d) {
    $total_cost = $d['fuel_cost'] + $d['veh_cost'];
    $profit     = $d['revenue'] - $total_cost;
    $veh        = $veh_map[$vid] ?? ['ref' => '', 'plate_number' => 'Unknown', 'make' => '', 'model' => '', 'year' => ''];
    $rows[] = [
        'ref'          => $vid,
        'plate_number' => $veh['plate_number'],
        'make'         => $veh['make'],
        'model'        => $veh['model'],
        'year'         => $veh['year'],
        'revenue'      => round($d['revenue'], 2),
        'fuel_cost'    => round($d['fuel_cost'], 2),
        'veh_cost'     => round($d['veh_cost'], 2),
        'total_cost'   => round($total_cost, 2),
        'profit'       => round($profit, 2),
        'trip_count'   => $d['trip_count'],
        'mileage'      => round($d['mileage'], 2),
    ];
}

// Sort by profit desc
usort($rows, fn($a, $b) => $b['profit'] <=> $a['profit']);

// Apply limit
if ($limit !== 'all' && is_numeric($limit)) {
    $rows = array_slice($rows, 0, intval($limit));
}

// Add rank
foreach ($rows as $i => &$row) {
    $row['rank'] = $i + 1;
}

echo json_encode(array_values($rows));
