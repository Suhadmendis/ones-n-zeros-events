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

// Fetch all vehicles
$vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model,status&order=ref.asc');

// Fetch trips in range (only need vehicle_ref, amount, mileage)
$trips = supabase_get(
    SB_API . 'm_trips?select=vehicle_ref,amount,mileage'
    . '&date=gte.' . urlencode($from)
    . '&date=lte.' . urlencode($to)
);

// Aggregate trips per vehicle
$agg = []; // vehicle_ref => [trip_count, total_revenue, total_mileage]
foreach ($trips as $t) {
    $vid = $t['vehicle_ref'];
    if (!isset($agg[$vid])) {
        $agg[$vid] = ['trip_count' => 0, 'total_revenue' => 0.0, 'total_mileage' => 0.0];
    }
    $agg[$vid]['trip_count']++;
    $agg[$vid]['total_revenue'] += (float)($t['amount']  ?? 0);
    $agg[$vid]['total_mileage'] += (float)($t['mileage'] ?? 0);
}

// Build result rows for all vehicles
$rows = [];
foreach ($vehicles as $v) {
    $vid   = $v['ref'];
    $stats = $agg[$vid] ?? ['trip_count' => 0, 'total_revenue' => 0.0, 'total_mileage' => 0.0];
    $rows[] = [
        'ref'           => $v['ref']          ?? '',
        'plate_number'  => $v['plate_number'] ?? '',
        'make'          => $v['make']         ?? '',
        'model'         => $v['model']        ?? '',
        'status'        => $v['status']       ?? '',
        'trip_count'    => $stats['trip_count'],
        'total_revenue' => round($stats['total_revenue'], 2),
        'total_mileage' => round($stats['total_mileage'], 2),
    ];
}

// Sort by trip_count descending
usort($rows, fn($a, $b) => $b['trip_count'] - $a['trip_count']);

// Totals
$totTrips   = array_sum(array_column($rows, 'trip_count'));
$totRevenue = array_sum(array_column($rows, 'total_revenue'));
$totMileage = array_sum(array_column($rows, 'total_mileage'));

echo json_encode([
    'rows' => $rows,
    'totals' => [
        'trip_count'    => $totTrips,
        'total_revenue' => round($totRevenue, 2),
        'total_mileage' => round($totMileage, 2),
    ],
]);
