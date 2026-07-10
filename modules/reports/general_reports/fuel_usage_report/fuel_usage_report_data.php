<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model&order=ref.asc');
    $fuel     = supabase_get(SB_API . 'm_fuel_expenses?select=vehicle_ref,liters,total&date=gte.'.$from.'&date=lte.'.$to);
    $trips    = supabase_get(SB_API . 'm_trips?select=vehicle_ref,mileage&date=gte.'.$from.'&date=lte.'.$to);

    $fuel_map = [];
    foreach ($fuel as $f) {
        $vid = $f['vehicle_ref'];
        $fuel_map[$vid]['litres'] = ($fuel_map[$vid]['litres'] ?? 0) + floatval($f['liters']);
        $fuel_map[$vid]['cost']   = ($fuel_map[$vid]['cost']   ?? 0) + floatval($f['total']);
    }
    $km_map = [];
    foreach ($trips as $t) {
        $vid = $t['vehicle_ref'];
        $km_map[$vid] = ($km_map[$vid] ?? 0) + floatval($t['mileage']);
    }

    $rows = [];
    foreach ($vehicles as $v) {
        $vid     = $v['ref'];
        $litres  = $fuel_map[$vid]['litres'] ?? 0;
        $cost    = $fuel_map[$vid]['cost']   ?? 0;
        if ($litres == 0) continue;
        $km      = $km_map[$vid] ?? 0;
        $kpl     = $litres > 0 ? round($km / $litres, 2) : null;
        $rows[]  = [
            'ref'          => $v['ref'],
            'plate_number' => $v['plate_number'],
            'make'         => $v['make'],
            'model'        => $v['model'],
            'total_litres' => round($litres, 2),
            'total_km'     => round($km, 2),
            'fuel_cost'    => round($cost, 2),
            'km_per_litre' => $kpl,
        ];
    }
    usort($rows, fn($a,$b) => ($a['km_per_litre'] ?? 999) <=> ($b['km_per_litre'] ?? 999));
    echo json_encode($rows);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
