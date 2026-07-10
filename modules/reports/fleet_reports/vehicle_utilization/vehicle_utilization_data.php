<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';
$from   = $_GET['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? date('Y-m-d');

if ($action === 'report') {
    $vehicles = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number,make,model,status");
    $trips    = supabase_get("/rest/v1/m_trips?select=vehicle_ref,mileage,date&date=gte.{$from}&date=lte.{$to}");

    // Build per-vehicle stats
    $trip_count_map  = [];
    $mileage_map     = [];
    $days_map        = [];

    foreach ($trips as $t) {
        $vid = $t['vehicle_ref'];
        $trip_count_map[$vid] = ($trip_count_map[$vid] ?? 0) + 1;
        $mileage_map[$vid]    = ($mileage_map[$vid] ?? 0) + floatval($t['mileage'] ?? 0);
        $days_map[$vid][$t['date']] = true;
    }

    $rows = [];
    foreach ($vehicles as $v) {
        $vid = $v['ref'];
        $rows[] = [
            'ref'         => $v['ref'] ?? '',
            'plate_number'=> $v['plate_number'] ?? '',
            'make'        => $v['make'] ?? '',
            'model'       => $v['model'] ?? '',
            'status'      => $v['status'] ?? '',
            'trip_count'  => $trip_count_map[$vid] ?? 0,
            'total_mileage'=> $mileage_map[$vid] ?? 0,
            'days_active' => isset($days_map[$vid]) ? count($days_map[$vid]) : 0,
        ];
    }

    usort($rows, fn($a, $b) => $b['trip_count'] <=> $a['trip_count']);

    $totals = [
        'ref'          => 'TOTALS',
        'plate_number' => '',
        'make'         => '',
        'model'        => '',
        'status'       => '',
        'trip_count'   => array_sum(array_column($rows, 'trip_count')),
        'total_mileage'=> array_sum(array_column($rows, 'total_mileage')),
        'days_active'  => '',
    ];

    echo json_encode(['rows' => $rows, 'totals' => $totals]);
}
