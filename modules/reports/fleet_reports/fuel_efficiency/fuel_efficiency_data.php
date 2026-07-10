<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';
$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');

if ($action === 'report') {
    $vehicles = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number,make,model,fuel_type");
    $trips    = supabase_get("/rest/v1/m_trips?select=vehicle_ref,mileage&date=gte.{$from}&date=lte.{$to}");
    $fuel     = supabase_get("/rest/v1/m_fuel_expenses?select=vehicle_ref,liters,total&date=gte.{$from}&date=lte.{$to}");

    $km_map     = [];
    $litre_map  = [];

    foreach ($trips as $t) {
        $vid = $t['vehicle_ref'];
        $km_map[$vid] = ($km_map[$vid] ?? 0) + floatval($t['mileage'] ?? 0);
    }
    foreach ($fuel as $f) {
        $vid = $f['vehicle_ref'];
        $litre_map[$vid] = ($litre_map[$vid] ?? 0) + floatval($f['liters'] ?? 0);
    }

    $rows = [];
    foreach ($vehicles as $v) {
        $vid      = $v['ref'];
        $total_km = $km_map[$vid]    ?? 0;
        $total_lt = $litre_map[$vid] ?? 0;

        if ($total_km == 0 && $total_lt == 0) continue;

        $kpl = ($total_lt > 0) ? round($total_km / $total_lt, 2) : null;

        $rows[] = [
            'ref'          => $v['ref'] ?? '',
            'plate_number' => $v['plate_number'] ?? '',
            'make'         => $v['make'] ?? '',
            'model'        => $v['model'] ?? '',
            'fuel_type'    => $v['fuel_type'] ?? '',
            'total_km'     => $total_km,
            'total_litres' => $total_lt,
            'km_per_litre' => $kpl,
        ];
    }

    // Sort by km_per_litre asc, nulls last
    usort($rows, function($a, $b) {
        if ($a['km_per_litre'] === null && $b['km_per_litre'] === null) return 0;
        if ($a['km_per_litre'] === null) return 1;
        if ($b['km_per_litre'] === null) return -1;
        return $a['km_per_litre'] <=> $b['km_per_litre'];
    });

    echo json_encode(['rows' => $rows]);
}
