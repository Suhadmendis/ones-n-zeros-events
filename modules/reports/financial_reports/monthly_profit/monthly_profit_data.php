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

    // Revenue from trips
    $monthly_revenue = [];
    $trips = supabase_get(SB_API . 'm_trips?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($trips as $t) {
        $mk = substr($t['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $monthly_revenue[$mk] = ($monthly_revenue[$mk] ?? 0.0) + (float)($t['amount'] ?? 0);
    }

    // Expenses — all 4 tables summed per month
    $monthly_expenses = [];

    $fuel = supabase_get(SB_API . 'm_fuel_expenses?select=date,total&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($fuel as $f) {
        $mk = substr($f['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $monthly_expenses[$mk] = ($monthly_expenses[$mk] ?? 0.0) + (float)($f['total'] ?? 0);
    }

    $vexp = supabase_get(SB_API . 'm_vehicle_expenses?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($vexp as $v) {
        $mk = substr($v['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $monthly_expenses[$mk] = ($monthly_expenses[$mk] ?? 0.0) + (float)($v['amount'] ?? 0);
    }

    $gexp = supabase_get(SB_API . 'm_general_expenses?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($gexp as $g) {
        $mk = substr($g['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $monthly_expenses[$mk] = ($monthly_expenses[$mk] ?? 0.0) + (float)($g['amount'] ?? 0);
    }

    $adv = supabase_get(SB_API . 'm_advance_payments?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($adv as $a) {
        $mk = substr($a['date'] ?? '', 0, 7);
        if (!$mk) continue;
        $monthly_expenses[$mk] = ($monthly_expenses[$mk] ?? 0.0) + (float)($a['amount'] ?? 0);
    }

    // Build 12-month result
    $result = [];
    for ($m = 1; $m <= 12; $m++) {
        $mm       = str_pad($m, 2, '0', STR_PAD_LEFT);
        $key      = $year . '-' . $mm;
        $revenue  = round($monthly_revenue[$key]  ?? 0.0, 2);
        $expenses = round($monthly_expenses[$key] ?? 0.0, 2);
        $profit   = round($revenue - $expenses, 2);
        $margin   = $revenue > 0 ? round($profit / $revenue * 100, 2) : null;
        $result[] = [
            'month_label' => $monthNames[$mm],
            'revenue'     => $revenue,
            'expenses'    => $expenses,
            'profit'      => $profit,
            'margin_pct'  => $margin,
        ];
    }

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
