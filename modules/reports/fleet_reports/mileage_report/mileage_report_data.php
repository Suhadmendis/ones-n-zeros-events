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

$filter = "date=gte.{$from}&date=lte.{$to}";
if ($vehicle_id !== '' && $vehicle_id !== 'all') {
    $filter .= "&vehicle_ref=eq.{$vehicle_id}";
}

$trips = supabase_get("/rest/v1/m_trips?select=vehicle_ref,mileage,opening_km,closing_km&{$filter}");

// Build per-vehicle maps
$mileage_map     = [];
$opening_map     = [];
$closing_map     = [];
$trip_count_map  = [];

foreach ($trips as $t) {
    $vid = $t['vehicle_ref'];
    $trip_count_map[$vid] = ($trip_count_map[$vid] ?? 0) + 1;
    $mileage_map[$vid]    = ($mileage_map[$vid] ?? 0) + floatval($t['mileage'] ?? 0);

    $okm = floatval($t['opening_km'] ?? 0);
    $ckm = floatval($t['closing_km'] ?? 0);
    if ($okm > 0) {
        if (!isset($opening_map[$vid]) || $okm < $opening_map[$vid]) {
            $opening_map[$vid] = $okm;
        }
    }
    if ($ckm > 0) {
        if (!isset($closing_map[$vid]) || $ckm > $closing_map[$vid]) {
            $closing_map[$vid] = $ckm;
        }
    }
}

$rows = [];
foreach ($vehicles as $v) {
    $vid = $v['ref'];
    $trip_count = $trip_count_map[$vid] ?? 0;
    if ($trip_count === 0 && ($vehicle_id === '' || $vehicle_id === 'all')) continue;

    $rows[] = [
        'ref'          => $v['ref'] ?? '',
        'plate_number' => $v['plate_number'] ?? '',
        'make'         => $v['make'] ?? '',
        'model'        => $v['model'] ?? '',
        'opening_km'   => $opening_map[$vid] ?? 0,
        'closing_km'   => $closing_map[$vid] ?? 0,
        'total_mileage'=> $mileage_map[$vid] ?? 0,
        'trip_count'   => $trip_count,
    ];
}

usort($rows, fn($a, $b) => $b['total_mileage'] <=> $a['total_mileage']);

$totals = [
    'ref'          => 'TOTALS',
    'plate_number' => '',
    'make'         => '',
    'model'        => '',
    'opening_km'   => '',
    'closing_km'   => '',
    'total_mileage'=> array_sum(array_column($rows, 'total_mileage')),
    'trip_count'   => array_sum(array_column($rows, 'trip_count')),
];

echo json_encode(['rows' => $rows, 'totals' => $totals]);
