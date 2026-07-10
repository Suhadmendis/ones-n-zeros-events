<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list_vehicles') {
    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make&order=plate_number.asc&limit=10000');
    echo json_encode($vehicles);
    exit;
}

if ($action === 'report') {
    $from       = $_GET['from']        ?? date('Y-m-01');
    $to         = $_GET['to']          ?? date('Y-m-d');
    $vehicleRef = $_GET['vehicle_ref'] ?? '';

    // Build fuel_expenses query
    $qs = SB_API . 'm_fuel_expenses?select=ref,date,vehicle_ref,liters,rate,total'
        . '&date=gte.' . urlencode($from)
        . '&date=lte.' . urlencode($to)
        . '&order=date.desc'
        . '&limit=100000';
    if ($vehicleRef !== '') {
        $qs .= '&vehicle_ref=eq.' . urlencode($vehicleRef);
    }
    $expenses = supabase_get($qs);

    // Fetch vehicles for plate_number lookup
    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make&limit=10000');
    $vehicleMap = [];
    foreach ($vehicles as $v) {
        $vehicleMap[$v['ref']] = $v;
    }

    $rows          = [];
    $totalLitres   = 0.0;
    $totalCost     = 0.0;

    foreach ($expenses as $e) {
        $vid  = $e['vehicle_ref'] ?? null;
        $veh  = $vehicleMap[$vid] ?? null;
        $liters = (float)($e['liters'] ?? 0);
        $total  = (float)($e['total']  ?? 0);

        $totalLitres += $liters;
        $totalCost   += $total;

        $rows[] = [
            'ref'          => $e['ref']  ?? '',
            'date'         => $e['date'] ?? '',
            'plate_number' => $veh['plate_number'] ?? ($vid ?? ''),
            'liters'       => round($liters, 2),
            'rate'         => round((float)($e['rate'] ?? 0), 2),
            'total'        => round($total, 2),
        ];
    }

    echo json_encode([
        'rows'    => $rows,
        'summary' => [
            'entry_count'  => count($rows),
            'total_litres' => round($totalLitres, 2),
            'total_cost'   => round($totalCost, 2),
        ],
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
