<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $year = (int)($_GET['year'] ?? date('Y'));
    $from = $year . '-01-01';
    $to   = $year . '-12-31';

    $monthNames = [
        '01' => 'January', '02' => 'February', '03' => 'March',
        '04' => 'April',   '05' => 'May',       '06' => 'June',
        '07' => 'July',    '08' => 'August',     '09' => 'September',
        '10' => 'October', '11' => 'November',   '12' => 'December',
    ];

    $fuel_m = $vehicle_m = $general_m = $advance_m = [];

    // Fuel
    $fuel = supabase_get(SB_API . 'm_fuel_expenses?select=date,total&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($fuel as $f) {
        $mk = substr($f['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $fuel_m[$mk] = ($fuel_m[$mk] ?? 0.0) + (float)($f['total'] ?? 0);
    }

    // Vehicle
    $vexp = supabase_get(SB_API . 'm_vehicle_expenses?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($vexp as $v) {
        $mk = substr($v['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $vehicle_m[$mk] = ($vehicle_m[$mk] ?? 0.0) + (float)($v['amount'] ?? 0);
    }

    // General
    $gexp = supabase_get(SB_API . 'm_general_expenses?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($gexp as $g) {
        $mk = substr($g['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $general_m[$mk] = ($general_m[$mk] ?? 0.0) + (float)($g['amount'] ?? 0);
    }

    // Advances
    $adv = supabase_get(SB_API . 'm_advance_payments?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($adv as $a) {
        $mk = substr($a['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $advance_m[$mk] = ($advance_m[$mk] ?? 0.0) + (float)($a['amount'] ?? 0);
    }

    // Build 12 months and compute MoM on total
    $result = [];
    $prev_total = null;
    for ($m = 1; $m <= 12; $m++) {
        $mm      = str_pad($m, 2, '0', STR_PAD_LEFT);
        $key     = $year . '-' . $mm;
        $fuel    = round($fuel_m[$key]    ?? 0.0, 2);
        $vehicle = round($vehicle_m[$key] ?? 0.0, 2);
        $general = round($general_m[$key] ?? 0.0, 2);
        $advance = round($advance_m[$key] ?? 0.0, 2);
        $total   = round($fuel + $vehicle + $general + $advance, 2);
        $mom     = null;
        if ($prev_total !== null && $prev_total > 0) {
            $mom = round(($total - $prev_total) / $prev_total * 100, 2);
        }
        $result[] = [
            'month_label' => $monthNames[$mm],
            'fuel'        => $fuel,
            'vehicle_exp' => $vehicle,
            'general_exp' => $general,
            'advances'    => $advance,
            'total'       => $total,
            'mom_change'  => $mom,
        ];
        $prev_total = $total;
    }

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
