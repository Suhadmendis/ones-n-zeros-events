<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_drivers') {
    echo json_encode(supabase_get(SB_API . 'm_drivers?select=ref,name&status=eq.active&order=name.asc'));
    exit;
}

$from       = $_GET['from']       ?? date('Y-m-01');
$to         = $_GET['to']         ?? date('Y-m-t');
$driver_ref = $_GET['driver_ref'] ?? '';

$drivers = supabase_get(SB_API . 'm_drivers?select=ref,name&order=name.asc');
$dmap = [];
foreach ($drivers as $d) $dmap[$d['ref']] = $d;

$qs = SB_API . 'm_deductions?select=id,ref,date,driver_ref,amount,reason&recipient_type=eq.driver&date=gte.'.$from.'&date=lte.'.$to;
if ($driver_ref !== '') $qs .= '&driver_ref=eq.'.$driver_ref;
$qs .= '&order=date.asc';

$deductions = supabase_get($qs);

$rows = [];
$total_amount = 0;
foreach ($deductions as $ded) {
    $d = $dmap[$ded['driver_ref']] ?? [];
    $rows[] = [
        'driver_ref'  => $d['ref']    ?? '',
        'driver_name' => $d['name']   ?? '',
        'ded_ref'     => $ded['ref']  ?? '',
        'date'        => $ded['date'] ?? '',
        'amount'      => floatval($ded['amount']),
        'reason'      => $ded['reason'] ?? '',
    ];
    $total_amount += floatval($ded['amount']);
}

usort($rows, fn($a,$b) => [$a['driver_name'],$a['date']] <=> [$b['driver_name'],$b['date']]);

echo json_encode([
    'rows'    => $rows,
    'summary' => ['total_amount' => $total_amount, 'entry_count' => count($rows)],
]);
