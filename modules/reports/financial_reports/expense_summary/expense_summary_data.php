<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-t');

    // Fuel total
    $fuel_total = 0.0;
    $fuel = supabase_get(SB_API . 'm_fuel_expenses?select=total&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($fuel as $f) {
        $fuel_total += (float)($f['total'] ?? 0);
    }

    // Vehicle expenses grouped by category
    $vcat = [];
    $vexp = supabase_get(SB_API . 'm_vehicle_expenses?select=amount,category&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($vexp as $v) {
        $cat = strtolower(trim($v['category'] ?? 'other'));
        $vcat[$cat] = ($vcat[$cat] ?? 0.0) + (float)($v['amount'] ?? 0);
    }

    // General expenses total
    $gen_total = 0.0;
    $gexp = supabase_get(SB_API . 'm_general_expenses?select=amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($gexp as $g) {
        $gen_total += (float)($g['amount'] ?? 0);
    }

    // Advance payments total
    $adv_total = 0.0;
    $adv = supabase_get(SB_API . 'm_advance_payments?select=amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($adv as $a) {
        $adv_total += (float)($a['amount'] ?? 0);
    }

    $rows = [
        ['category' => 'Fuel',              'amount' => round($fuel_total, 2)],
        ['category' => 'Vehicle Repair',    'amount' => round($vcat['repair']    ?? 0, 2)],
        ['category' => 'Vehicle Service',   'amount' => round($vcat['service']   ?? 0, 2)],
        ['category' => 'Vehicle Tyre',      'amount' => round($vcat['tyre']      ?? 0, 2)],
        ['category' => 'Vehicle Battery',   'amount' => round($vcat['battery']   ?? 0, 2)],
        ['category' => 'Vehicle Insurance', 'amount' => round($vcat['insurance'] ?? 0, 2)],
        ['category' => 'Vehicle Other',     'amount' => round($vcat['other']     ?? 0, 2)],
        ['category' => 'General Expenses',  'amount' => round($gen_total, 2)],
        ['category' => 'Advance Payments',  'amount' => round($adv_total, 2)],
    ];

    $total = array_sum(array_column($rows, 'amount'));

    foreach ($rows as &$row) {
        $row['pct_of_total'] = $total > 0 ? round($row['amount'] / $total * 100, 2) : 0;
    }
    unset($row);

    echo json_encode(['rows' => $rows, 'total' => round($total, 2)]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
