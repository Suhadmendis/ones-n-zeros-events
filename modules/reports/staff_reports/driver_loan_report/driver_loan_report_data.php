<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_drivers') {
    echo json_encode(supabase_get(SB_API . 'm_drivers?select=ref,name&status=eq.active&order=name.asc'));
    exit;
}

$driver_ref = $_GET['driver_ref'] ?? '';
$status     = $_GET['status']     ?? '';

$drivers = supabase_get(SB_API . 'm_drivers?select=ref,name&order=name.asc');
$dmap = [];
foreach ($drivers as $d) $dmap[$d['ref']] = $d;

$qs = SB_API . 'm_loans?select=id,ref,date,driver_ref,principal_amount,recovered_amount,status&recipient_type=eq.driver';
if ($driver_ref !== '') $qs .= '&driver_ref=eq.'.$driver_ref;
if ($status !== '' && $status !== 'all') $qs .= '&status=eq.'.$status;
$qs .= '&order=date.asc';

$loans = supabase_get($qs);

$rows = [];
foreach ($loans as $l) {
    $d = $dmap[$l['driver_ref']] ?? [];
    $principal  = floatval($l['principal_amount']);
    $recovered  = floatval($l['recovered_amount']);
    $remaining  = $principal - $recovered;
    $rows[] = [
        'driver_ref'        => $d['ref']   ?? '',
        'driver_name'       => $d['name']  ?? '',
        'loan_ref'          => $l['ref']   ?? '',
        'date'              => $l['date']  ?? '',
        'principal_amount'  => $principal,
        'recovered_amount'  => $recovered,
        'remaining_balance' => $remaining,
        'status'            => $l['status'] ?? '',
    ];
}

usort($rows, fn($a,$b) => [$a['driver_name'],$a['date']] <=> [$b['driver_name'],$b['date']]);

echo json_encode(['rows' => $rows]);
