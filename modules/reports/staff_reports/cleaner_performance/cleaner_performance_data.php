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

// All cleaners (for zero-trip rows)
$cleaners = supabase_get(SB_API . 'm_cleaners?select=ref,name,status&order=name.asc');
$cmap = [];
foreach ($cleaners as $c) $cmap[$c['ref']] = $c;

// Trips in date range
$qs = SB_API . 'm_trips?select=cleaner_ref,cleaner_salary&date=gte.'.$from.'&date=lte.'.$to;
if ($cleaner_ref !== '') $qs .= '&cleaner_ref=eq.'.$cleaner_ref;

$trips = supabase_get($qs);

// Aggregate per cleaner
$stats = [];
foreach ($trips as $t) {
    $cid = $t['cleaner_ref'];
    if (!isset($stats[$cid])) $stats[$cid] = ['trip_count' => 0, 'total_cleaner_earning' => 0];
    $stats[$cid]['trip_count']++;
    $stats[$cid]['total_cleaner_earning'] += floatval($t['cleaner_salary'] ?? 0);
}

// Build rows — include all cleaners (or just filtered one) with 0 if no trips
$rows = [];
$total_trips = 0;
$total_earning = 0;

$cleaner_pool = ($cleaner_ref !== '') ? array_filter($cleaners, fn($c) => $c['ref'] == $cleaner_ref) : $cleaners;
foreach ($cleaner_pool as $c) {
    $s = $stats[$c['ref']] ?? ['trip_count' => 0, 'total_cleaner_earning' => 0];
    $rows[] = [
        'cleaner_ref'           => $c['ref']    ?? '',
        'cleaner_name'          => $c['name']   ?? '',
        'status'                => $c['status'] ?? '',
        'trip_count'            => $s['trip_count'],
        'total_cleaner_earning' => $s['total_cleaner_earning'],
    ];
    $total_trips   += $s['trip_count'];
    $total_earning += $s['total_cleaner_earning'];
}

usort($rows, fn($a,$b) => $b['trip_count'] <=> $a['trip_count']);

echo json_encode([
    'rows'    => $rows,
    'summary' => ['total_trips' => $total_trips, 'total_earning' => $total_earning, 'cleaner_count' => count($rows)],
]);
