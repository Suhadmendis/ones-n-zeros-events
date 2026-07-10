<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    // Fetch trips in range (driver earnings columns)
    $trips = supabase_get(
        SB_API . 'm_trips?select=driver_ref,amount,driver_salary&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );

    // Fetch all drivers
    $drivers = supabase_get(SB_API . 'm_drivers?select=ref,name,status&limit=10000');

    // Build driver lookup
    $driverMap = [];
    foreach ($drivers as $d) {
        $driverMap[$d['ref']] = $d;
    }

    // Aggregate trips by driver_ref
    $agg = [];
    foreach ($trips as $t) {
        $did = $t['driver_ref'] ?? null;
        if (!$did) continue;
        if (!isset($agg[$did])) {
            $agg[$did] = [
                'trip_count'           => 0,
                'total_revenue'        => 0.0,
                'total_driver_salary'  => 0.0,
            ];
        }
        $agg[$did]['trip_count']++;
        $agg[$did]['total_revenue']       += (float)($t['amount']        ?? 0);
        $agg[$did]['total_driver_salary'] += (float)($t['driver_salary'] ?? 0);
    }

    // Build result rows
    $result = [];
    foreach ($agg as $did => $data) {
        $d = $driverMap[$did] ?? null;
        $result[] = [
            'ref'                 => $d['ref']    ?? $did,
            'name'                => $d['name']   ?? '',
            'status'              => $d['status'] ?? '',
            'trip_count'          => $data['trip_count'],
            'total_revenue'       => round($data['total_revenue'], 2),
            'total_driver_salary' => round($data['total_driver_salary'], 2),
            'gross_earning'       => round($data['total_driver_salary'], 2),
        ];
    }

    // Sort by gross earning descending
    usort($result, fn($a, $b) => $b['gross_earning'] <=> $a['gross_earning']);

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
