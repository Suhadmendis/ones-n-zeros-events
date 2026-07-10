<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$month = $_GET['month'] ?? date('Y-m');

$settlements = supabase_get(SB_API . 'm_driver_salary_settlements?select=ref,month,driver_ref,gross_earnings,advances,deductions_total,loan_recovery,net_payable,status&month=eq.'.$month.'&order=ref.asc');
$drivers     = supabase_get(SB_API . 'm_drivers?select=ref,name&order=name.asc');
$dmap = [];
foreach ($drivers as $d) $dmap[$d['ref']] = $d;

$rows = [];
$total_gross = $total_net = $paid = $pending = 0;
foreach ($settlements as $s) {
    $d = $dmap[$s['driver_ref']] ?? [];
    $rows[] = [
        'ref'              => $s['ref'],
        'driver_ref'       => $d['ref'] ?? '',
        'driver_name'      => $d['name'] ?? '',
        'month'            => $s['month'],
        'gross_earnings'   => floatval($s['gross_earnings']),
        'advances'         => floatval($s['advances']),
        'deductions_total' => floatval($s['deductions_total']),
        'loan_recovery'    => floatval($s['loan_recovery']),
        'net_payable'      => floatval($s['net_payable']),
        'status'           => $s['status'],
    ];
    $total_gross += floatval($s['gross_earnings']);
    $total_net   += floatval($s['net_payable']);
    if ($s['status'] === 'paid') $paid++; else $pending++;
}

echo json_encode([
    'rows'    => $rows,
    'summary' => ['total_gross' => $total_gross, 'total_net' => $total_net, 'settlement_count' => count($rows), 'paid_count' => $paid, 'pending_count' => $pending],
]);
