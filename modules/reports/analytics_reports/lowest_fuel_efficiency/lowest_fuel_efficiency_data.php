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
$trips         = supabase_get(SB_API . 'm_trips?select=vehicle_ref,mileage&date=gte.' . $from . '&date=lte.' . $to);
$fuel_expenses = supabase_get(SB_API . 'm_fuel_expenses?select=vehicle_ref,liters&date=gte.' . $from . '&date=lte.' . $to);

$veh_map = [];
foreach ($vehicles as $v) {
    $veh_map[$v['ref']] = $v;
}

$km_data     = [];
$litre_data  = [];

foreach ($trips as $t) {
    $vid = $t['vehicle_ref'];
    $km_data[$vid] = ($km_data[$vid] ?? 0) + floatval($t['mileage'] ?? 0);
}
foreach ($fuel_expenses as $f) {
    $vid = $f['vehicle_ref'];
    $litre_data[$vid] = ($litre_data[$vid] ?? 0) + floatval($f['liters'] ?? 0);
}

$rows = [];
foreach ($veh_map as $vid => $v) {
    $total_km     = $km_data[$vid] ?? 0;
    $total_litres = $litre_data[$vid] ?? 0;
    if ($total_km <= 0 || $total_litres <= 0) continue;
    $km_per_litre = round($total_km / $total_litres, 2);
    $rows[] = [
        'ref'          => $vid,
        'plate_number' => $v['plate_number'],
        'make'         => $v['make'],
        'model'        => $v['model'],
        'year'         => $v['year'],
        'total_km'     => round($total_km, 2),
        'total_litres' => round($total_litres, 2),
        'km_per_litre' => $km_per_litre,
    ];
}

// Sort by km_per_litre asc (worst first)
usort($rows, fn($a, $b) => $a['km_per_litre'] <=> $b['km_per_litre']);

foreach ($rows as $i => &$row) {
    $row['rank'] = $i + 1;
}

echo json_encode(array_values($rows));
