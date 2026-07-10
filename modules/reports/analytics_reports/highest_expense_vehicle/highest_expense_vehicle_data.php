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
$fuel_expenses = supabase_get(SB_API . 'm_fuel_expenses?select=vehicle_ref,total&date=gte.' . $from . '&date=lte.' . $to);
$veh_expenses  = supabase_get(SB_API . 'm_vehicle_expenses?select=vehicle_ref,amount&date=gte.' . $from . '&date=lte.' . $to);

$veh_map = [];
foreach ($vehicles as $v) {
    $veh_map[$v['ref']] = $v;
}

$fuel_data = [];
$maint_data = [];

foreach ($fuel_expenses as $f) {
    $vid = $f['vehicle_ref'];
    $fuel_data[$vid] = ($fuel_data[$vid] ?? 0) + floatval($f['total'] ?? 0);
}
foreach ($veh_expenses as $e) {
    $vid = $e['vehicle_ref'];
    $maint_data[$vid] = ($maint_data[$vid] ?? 0) + floatval($e['amount'] ?? 0);
}

// Collect all vehicle IDs that appear in either expense table
$all_vids = array_unique(array_merge(array_keys($fuel_data), array_keys($maint_data)));

$rows = [];
foreach ($all_vids as $vid) {
    $fuel  = $fuel_data[$vid] ?? 0;
    $maint = $maint_data[$vid] ?? 0;
    $total = $fuel + $maint;
    $veh   = $veh_map[$vid] ?? ['ref' => '', 'plate_number' => 'Unknown', 'make' => '', 'model' => '', 'year' => ''];
    $rows[] = [
        'ref'                => $vid,
        'plate_number'       => $veh['plate_number'],
        'make'               => $veh['make'],
        'model'              => $veh['model'],
        'year'               => $veh['year'],
        'fuel_cost'          => round($fuel, 2),
        'maintenance_cost'   => round($maint, 2),
        'total_operating_cost' => round($total, 2),
    ];
}

usort($rows, fn($a, $b) => $b['total_operating_cost'] <=> $a['total_operating_cost']);

foreach ($rows as $i => &$row) {
    $row['rank'] = $i + 1;
}

echo json_encode(array_values($rows));
