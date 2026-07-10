<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($action !== 'report') {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-t');

$vehicles      = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model,year');
$trips         = supabase_get(SB_API . 'm_trips?select=vehicle_ref,amount,mileage&date=gte.' . $from . '&date=lte.' . $to);
$fuel_expenses = supabase_get(SB_API . 'm_fuel_expenses?select=vehicle_ref,total&date=gte.' . $from . '&date=lte.' . $to);
$veh_expenses  = supabase_get(SB_API . 'm_vehicle_expenses?select=vehicle_ref,amount&date=gte.' . $from . '&date=lte.' . $to);

$veh_map = [];
foreach ($vehicles as $v) {
    $veh_map[$v['ref']] = $v;
}

$data = [];
foreach ($trips as $t) {
    $vid = $t['vehicle_ref'];
    if (!isset($data[$vid])) {
        $data[$vid] = ['revenue' => 0, 'fuel_cost' => 0, 'veh_cost' => 0, 'total_km' => 0];
    }
    $data[$vid]['revenue']  += floatval($t['amount'] ?? 0);
    $data[$vid]['total_km'] += floatval($t['mileage'] ?? 0);
}
foreach ($fuel_expenses as $f) {
    $vid = $f['vehicle_ref'];
    if (!isset($data[$vid])) {
        $data[$vid] = ['revenue' => 0, 'fuel_cost' => 0, 'veh_cost' => 0, 'total_km' => 0];
    }
    $data[$vid]['fuel_cost'] += floatval($f['total'] ?? 0);
}
foreach ($veh_expenses as $e) {
    $vid = $e['vehicle_ref'];
    if (!isset($data[$vid])) {
        $data[$vid] = ['revenue' => 0, 'fuel_cost' => 0, 'veh_cost' => 0, 'total_km' => 0];
    }
    $data[$vid]['veh_cost'] += floatval($e['amount'] ?? 0);
}

$rows = [];
foreach ($data as $vid => $d) {
    $revenue    = $d['revenue'];
    $total_cost = $d['fuel_cost'] + $d['veh_cost'];
    $profit     = $revenue - $total_cost;
    $total_km   = $d['total_km'];
    $profit_per_km = $total_km > 0 ? round($profit / $total_km, 2) : null;
    $veh = $veh_map[$vid] ?? ['ref' => '', 'plate_number' => 'Unknown', 'make' => '', 'model' => '', 'year' => ''];
    $rows[] = [
        'ref'           => $vid,
        'plate_number'  => $veh['plate_number'],
        'make'          => $veh['make'],
        'model'         => $veh['model'],
        'year'          => $veh['year'],
        'revenue'       => round($revenue, 2),
        'total_cost'    => round($total_cost, 2),
        'profit'        => round($profit, 2),
        'total_km'      => round($total_km, 2),
        'profit_per_km' => $profit_per_km,
    ];
}

// Sort by profit_per_km desc, nulls last
usort($rows, function ($a, $b) {
    if ($a['profit_per_km'] === null && $b['profit_per_km'] === null) return 0;
    if ($a['profit_per_km'] === null) return 1;
    if ($b['profit_per_km'] === null) return -1;
    return $b['profit_per_km'] <=> $a['profit_per_km'];
});

foreach ($rows as $i => &$row) {
    $row['rank'] = $i + 1;
}

echo json_encode(array_values($rows));
