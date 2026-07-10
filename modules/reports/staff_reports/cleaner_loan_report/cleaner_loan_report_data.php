<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_cleaners') {
    echo json_encode(supabase_get(SB_API . 'm_cleaners?select=ref,name&status=eq.active&order=name.asc'));
    exit;
}

$cleaner_ref = $_GET['cleaner_ref'] ?? '';
$status      = $_GET['status']      ?? '';

$cleaners = supabase_get(SB_API . 'm_cleaners?select=ref,name&order=name.asc');
$cmap = [];
foreach ($cleaners as $c) $cmap[$c['ref']] = $c;

$qs = SB_API . 'm_loans?select=id,ref,date,cleaner_ref,principal_amount,recovered_amount,status&recipient_type=eq.cleaner';
if ($cleaner_ref !== '') $qs .= '&cleaner_ref=eq.'.$cleaner_ref;
if ($status !== '' && $status !== 'all') $qs .= '&status=eq.'.$status;
$qs .= '&order=date.asc';

$loans = supabase_get($qs);

$rows = [];
foreach ($loans as $l) {
    $c = $cmap[$l['cleaner_ref']] ?? [];
    $principal = floatval($l['principal_amount']);
    $recovered = floatval($l['recovered_amount']);
    $rows[] = [
        'cleaner_ref'       => $c['ref']    ?? '',
        'cleaner_name'      => $c['name']   ?? '',
        'loan_ref'          => $l['ref']    ?? '',
        'date'              => $l['date']   ?? '',
        'principal_amount'  => $principal,
        'recovered_amount'  => $recovered,
        'remaining_balance' => $principal - $recovered,
        'status'            => $l['status'] ?? '',
    ];
}

usort($rows, fn($a,$b) => [$a['cleaner_name'],$a['date']] <=> [$b['cleaner_name'],$b['date']]);

echo json_encode(['rows' => $rows]);
