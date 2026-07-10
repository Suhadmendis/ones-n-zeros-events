<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_cleaners') {
    echo json_encode(supabase_get(SB_API . 'm_cleaners?select=ref,name&status=eq.active&order=name.asc'));
    exit;
}

$from        = $_GET['from']        ?? date('Y-m-01');
$to          = $_GET['to']          ?? date('Y-m-t');
$cleaner_ref = $_GET['cleaner_ref'] ?? '';

$cleaners = supabase_get(SB_API . 'm_cleaners?select=ref,name&order=name.asc');
$cmap = [];
foreach ($cleaners as $c) $cmap[$c['ref']] = $c;

$qs = SB_API . 'm_advance_payments?select=id,ref,date,cleaner_ref,amount&recipient_type=eq.cleaner&date=gte.'.$from.'&date=lte.'.$to;
if ($cleaner_ref !== '') $qs .= '&cleaner_ref=eq.'.$cleaner_ref;
$qs .= '&order=date.asc';

$advances = supabase_get($qs);

$rows = [];
$total_amount = 0;
foreach ($advances as $a) {
    $c = $cmap[$a['cleaner_ref']] ?? [];
    $rows[] = [
        'cleaner_ref'  => $c['ref']  ?? '',
        'cleaner_name' => $c['name'] ?? '',
        'advance_ref'  => $a['ref']  ?? '',
        'date'         => $a['date'] ?? '',
        'amount'       => floatval($a['amount']),
    ];
    $total_amount += floatval($a['amount']);
}

usort($rows, fn($a,$b) => [$a['cleaner_name'],$a['date']] <=> [$b['cleaner_name'],$b['date']]);

echo json_encode([
    'rows'    => $rows,
    'summary' => ['total_amount' => $total_amount, 'entry_count' => count($rows)],
]);
