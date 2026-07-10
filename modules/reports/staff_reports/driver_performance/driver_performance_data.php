<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list_drivers') {
    $rows = supabase_get(SB_API . 'm_drivers?select=ref,name&order=name.asc&limit=10000');
    echo json_encode($rows);
    exit;
}

if ($action === 'report') {
    $from       = $_GET['from']       ?? date('Y-m-01');
    $to         = $_GET['to']         ?? date('Y-m-d');
    $driver_ref = $_GET['driver_ref'] ?? '';

    $drivers = supabase_get(SB_API . 'm_drivers?select=ref,name,status&limit=10000');

    $qs = SB_API . 'm_trips?select=driver_ref,amount,driver_salary,mileage'
        . '&date=gte.' . $from . '&date=lte.' . $to;
    if ($driver_ref !== '') $qs .= '&driver_ref=eq.' . $driver_ref;
    $qs .= '&limit=100000';
    $trips = supabase_get($qs);

    // Aggregate trips by driver
    $agg = [];
    foreach ($trips as $t) {
        $did = $t['driver_ref'] ?? null;
        if (!$did) continue;
        if (!isset($agg[$did])) {
            $agg[$did] = ['trip_count' => 0, 'total_mileage' => 0.0, 'total_revenue' => 0.0, 'total_driver_salary' => 0.0];
        }
        $agg[$did]['trip_count']++;
        $agg[$did]['total_mileage']       += (float)($t['mileage']       ?? 0);
        $agg[$did]['total_revenue']       += (float)($t['amount']        ?? 0);
        $agg[$did]['total_driver_salary'] += (float)($t['driver_salary'] ?? 0);
    }

    $result = [];
    $totals = ['trip_count' => 0, 'total_mileage' => 0, 'total_revenue' => 0, 'total_driver_salary' => 0, 'gross_earning' => 0];

    foreach ($drivers as $drv) {
        // Skip if filtered to specific driver and this isn't them
        if ($driver_ref !== '' && $drv['ref'] !== $driver_ref) continue;

        $did = $drv['ref'];
        $a   = $agg[$did] ?? ['trip_count' => 0, 'total_mileage' => 0, 'total_revenue' => 0, 'total_driver_salary' => 0];
        $gross = $a['total_driver_salary'];

        $result[] = [
            'ref'                 => $drv['ref']    ?? '',
            'name'                => $drv['name']   ?? '',
            'status'              => $drv['status'] ?? '',
            'trip_count'          => $a['trip_count'],
            'total_mileage'       => round($a['total_mileage'], 2),
            'total_revenue'       => round($a['total_revenue'], 2),
            'total_driver_salary' => round($a['total_driver_salary'], 2),
            'gross_earning'       => round($gross, 2),
        ];

        $totals['trip_count']          += $a['trip_count'];
        $totals['total_mileage']       += $a['total_mileage'];
        $totals['total_revenue']       += $a['total_revenue'];
        $totals['total_driver_salary'] += $a['total_driver_salary'];
        $totals['gross_earning']       += $gross;
    }

    usort($result, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

    foreach ($totals as $k => $v) $totals[$k] = round($v, 2);

    echo json_encode(['rows' => $result, 'totals' => $totals]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
