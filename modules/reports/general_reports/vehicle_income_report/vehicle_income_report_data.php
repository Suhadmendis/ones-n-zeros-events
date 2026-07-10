<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    // Fetch trips in range
    $trips = supabase_get(
        SB_API . 'm_trips?select=vehicle_ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );

    // Fetch all vehicles
    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model&limit=10000');

    // Build vehicle lookup
    $vehicleMap = [];
    foreach ($vehicles as $v) {
        $vehicleMap[$v['ref']] = $v;
    }

    // Aggregate trips by vehicle_ref
    $agg = [];
    foreach ($trips as $t) {
        $vid = $t['vehicle_ref'] ?? null;
        if (!$vid) continue;
        if (!isset($agg[$vid])) {
            $agg[$vid] = ['trip_count' => 0, 'total_revenue' => 0.0];
        }
        $agg[$vid]['trip_count']++;
        $agg[$vid]['total_revenue'] += (float)($t['amount'] ?? 0);
    }

    // Build result rows (only vehicles that have trips)
    $result = [];
    foreach ($agg as $vid => $data) {
        $v = $vehicleMap[$vid] ?? null;
        $result[] = [
            'ref'           => $v['ref']          ?? $vid,
            'plate_number'  => $v['plate_number'] ?? '',
            'make'          => $v['make']         ?? '',
            'model'         => $v['model']        ?? '',
            'trip_count'    => $data['trip_count'],
            'total_revenue' => round($data['total_revenue'], 2),
        ];
    }

    // Sort by revenue descending
    usort($result, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
