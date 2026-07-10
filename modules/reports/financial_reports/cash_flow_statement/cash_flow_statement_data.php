<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-t');

    $rows = [];

    // Trips → inflow
    $trips = supabase_get(SB_API . 'm_trips?select=date,ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($trips as $t) {
        $rows[] = [
            'date'    => $t['date'] ?? '',
            'ref'     => $t['ref'] ?? '',
            'inflow'  => (float)($t['amount'] ?? 0),
            'outflow' => 0.0,
            'type'    => 'Trip Revenue',
        ];
    }

    // Fuel expenses → outflow
    $fuel = supabase_get(SB_API . 'm_fuel_expenses?select=date,ref,total&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($fuel as $f) {
        $rows[] = [
            'date'    => $f['date'] ?? '',
            'ref'     => $f['ref'] ?? '',
            'inflow'  => 0.0,
            'outflow' => (float)($f['total'] ?? 0),
            'type'    => 'Fuel Expense',
        ];
    }

    // Vehicle expenses → outflow
    $vexp = supabase_get(SB_API . 'm_vehicle_expenses?select=date,ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($vexp as $v) {
        $rows[] = [
            'date'    => $v['date'] ?? '',
            'ref'     => $v['ref'] ?? '',
            'inflow'  => 0.0,
            'outflow' => (float)($v['amount'] ?? 0),
            'type'    => 'Vehicle Expense',
        ];
    }

    // General expenses → outflow
    $gexp = supabase_get(SB_API . 'm_general_expenses?select=date,ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($gexp as $g) {
        $rows[] = [
            'date'    => $g['date'] ?? '',
            'ref'     => $g['ref'] ?? '',
            'inflow'  => 0.0,
            'outflow' => (float)($g['amount'] ?? 0),
            'type'    => 'General Expense',
        ];
    }

    // Advance payments → outflow
    $adv = supabase_get(SB_API . 'm_advance_payments?select=date,ref,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($adv as $a) {
        $rows[] = [
            'date'    => $a['date'] ?? '',
            'ref'     => $a['ref'] ?? '',
            'inflow'  => 0.0,
            'outflow' => (float)($a['amount'] ?? 0),
            'type'    => 'Advance Payment',
        ];
    }

    // Sort by date ascending
    usort($rows, fn($a, $b) => strcmp($a['date'], $b['date']));

    // Compute running balance
    $balance = 0.0;
    $total_inflow  = 0.0;
    $total_outflow = 0.0;
    foreach ($rows as &$row) {
        $balance += $row['inflow'] - $row['outflow'];
        $row['running_balance'] = round($balance, 2);
        $total_inflow  += $row['inflow'];
        $total_outflow += $row['outflow'];
    }
    unset($row);

    echo json_encode([
        'rows'   => $rows,
        'totals' => [
            'total_inflow'   => round($total_inflow, 2),
            'total_outflow'  => round($total_outflow, 2),
            'final_balance'  => round($total_inflow - $total_outflow, 2),
        ],
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
