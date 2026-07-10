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

$dateFilter = '&date=gte.' . urlencode($from) . '&date=lte.' . urlencode($to);

// Fetch all transaction sources
$trips    = supabase_get(SB_API . 'm_trips?select=id,date,ref,amount'            . $dateFilter . '&order=date.asc,id.asc');
$fuel     = supabase_get(SB_API . 'm_fuel_expenses?select=id,date,ref,total'     . $dateFilter . '&order=date.asc,id.asc');
$vehicle  = supabase_get(SB_API . 'm_vehicle_expenses?select=id,date,ref,amount' . $dateFilter . '&order=date.asc,id.asc');
$general  = supabase_get(SB_API . 'm_general_expenses?select=id,date,ref,amount' . $dateFilter . '&order=date.asc,id.asc');
$advances = supabase_get(SB_API . 'm_advance_payments?select=id,date,ref,amount' . $dateFilter . '&order=date.asc,id.asc');

// Normalise into unified array: [date, sort_id, type, ref, inflow, outflow]
$all = [];

foreach ($trips as $r) {
    $all[] = [
        'date'    => $r['date'] ?? '',
        'sort_id' => (int)($r['id'] ?? 0),
        'type'    => 'Trip Revenue',
        'ref'     => $r['ref'] ?? '',
        'inflow'  => (float)($r['amount'] ?? 0),
        'outflow' => 0.0,
    ];
}
foreach ($fuel as $r) {
    $all[] = [
        'date'    => $r['date'] ?? '',
        'sort_id' => (int)($r['id'] ?? 0),
        'type'    => 'Fuel Expense',
        'ref'     => $r['ref'] ?? '',
        'inflow'  => 0.0,
        'outflow' => (float)($r['total'] ?? 0),
    ];
}
foreach ($vehicle as $r) {
    $all[] = [
        'date'    => $r['date'] ?? '',
        'sort_id' => (int)($r['id'] ?? 0),
        'type'    => 'Vehicle Expense',
        'ref'     => $r['ref'] ?? '',
        'inflow'  => 0.0,
        'outflow' => (float)($r['amount'] ?? 0),
    ];
}
foreach ($general as $r) {
    $all[] = [
        'date'    => $r['date'] ?? '',
        'sort_id' => (int)($r['id'] ?? 0),
        'type'    => 'General Expense',
        'ref'     => $r['ref'] ?? '',
        'inflow'  => 0.0,
        'outflow' => (float)($r['amount'] ?? 0),
    ];
}
foreach ($advances as $r) {
    $all[] = [
        'date'    => $r['date'] ?? '',
        'sort_id' => (int)($r['id'] ?? 0),
        'type'    => 'Advance Payment',
        'ref'     => $r['ref'] ?? '',
        'inflow'  => 0.0,
        'outflow' => (float)($r['amount'] ?? 0),
    ];
}

// Sort: date asc, then sort_id asc
usort($all, function ($a, $b) {
    $cmp = strcmp($a['date'], $b['date']);
    return $cmp !== 0 ? $cmp : $a['sort_id'] - $b['sort_id'];
});

// Compute running balance
$balance       = 0.0;
$totalInflow   = 0.0;
$totalOutflow  = 0.0;
$rows = [];

foreach ($all as $row) {
    $balance      += $row['inflow'] - $row['outflow'];
    $totalInflow  += $row['inflow'];
    $totalOutflow += $row['outflow'];
    $rows[] = [
        'date'            => $row['date'],
        'type'            => $row['type'],
        'ref'             => $row['ref'],
        'inflow'          => $row['inflow'],
        'outflow'         => $row['outflow'],
        'running_balance' => round($balance, 2),
    ];
}

echo json_encode([
    'rows' => $rows,
    'totals' => [
        'total_inflow'  => round($totalInflow,  2),
        'total_outflow' => round($totalOutflow, 2),
        'final_balance' => round($balance,      2),
    ],
]);
