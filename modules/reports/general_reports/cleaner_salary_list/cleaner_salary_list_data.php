<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list_cleaners') {
    echo json_encode(supabase_get(SB_API . 'm_cleaners?select=ref,name&status=eq.active&order=name.asc'));
    exit;
}

$from        = $_GET['from']        ?? date('Y-m-01');
$to          = $_GET['to']          ?? date('Y-m-d');
$cleaner_ref = isset($_GET['cleaner_ref']) && $_GET['cleaner_ref'] !== '' ? $_GET['cleaner_ref'] : null;

$url = SB_API . 'm_trips?select=ref,date,cleaner_ref,amount,cleaner_salary,from_loc,to_loc&date=gte.'.$from.'&date=lte.'.$to.'&order=date.asc';
if ($cleaner_ref) $url .= '&cleaner_ref=eq.'.$cleaner_ref;
$trips = supabase_get($url);

$cleaners = supabase_get(SB_API . 'm_cleaners?select=ref,name&order=name.asc');
$cmap = [];
foreach ($cleaners as $c) $cmap[$c['ref']] = $c;

$rows = [];
$total_cleaner_salary = $trip_count = 0;
foreach ($trips as $t) {
    $c = $cmap[$t['cleaner_ref']] ?? [];
    $rows[] = [
        'ref'          => $t['ref'],
        'date'         => $t['date'],
        'cleaner_ref'  => $c['ref']  ?? '',
        'cleaner_name' => $c['name'] ?? '',
        'from_loc'     => $t['from_loc'],
        'to_loc'       => $t['to_loc'],
        'trip_amount'    => floatval($t['amount']),
        'cleaner_salary' => floatval($t['cleaner_salary'] ?? 0),
    ];
    $total_cleaner_salary += floatval($t['cleaner_salary'] ?? 0);
    $trip_count++;
}

echo json_encode(['rows' => $rows, 'summary' => ['total_cleaner_salary' => $total_cleaner_salary, 'trip_count' => $trip_count]]);
