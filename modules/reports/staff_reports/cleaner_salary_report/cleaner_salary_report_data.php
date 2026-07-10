<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_cleaners') {
    echo json_encode(supabase_get(SB_API . 'm_cleaners?select=ref,name&status=eq.active&order=name.asc'));
    exit;
}

$month       = $_GET['month']       ?? date('Y-m');
$cleaner_ref = $_GET['cleaner_ref'] ?? '';

if ($cleaner_ref === '') {
    echo json_encode(['error' => 'cleaner_ref is required']);
    exit;
}

$month_start = $month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));

// Cleaner info
$cleaner_rows = supabase_get(SB_API . 'm_cleaners?select=ref,name&ref=eq.'.$cleaner_ref);
$cleaner = $cleaner_rows[0] ?? [];

// Trip earnings (cleaner_salary) in month
$trips = supabase_get(SB_API . 'm_trips?select=cleaner_salary&cleaner_ref=eq.'.$cleaner_ref.'&date=gte.'.$month_start.'&date=lte.'.$month_end);
$trip_earnings = 0;
$trip_count    = count($trips);
foreach ($trips as $t) $trip_earnings += floatval($t['cleaner_salary'] ?? 0);

// Advances in month
$adv_rows = supabase_get(SB_API . 'm_advance_payments?select=amount&cleaner_ref=eq.'.$cleaner_ref.'&recipient_type=eq.cleaner&date=gte.'.$month_start.'&date=lte.'.$month_end);
$advances = array_sum(array_column($adv_rows, 'amount'));

$net_salary = $trip_earnings - floatval($advances);

echo json_encode([
    'cleaner_ref'   => $cleaner['ref']  ?? '',
    'cleaner_name'  => $cleaner['name'] ?? '',
    'month'         => $month,
    'trip_count'    => $trip_count,
    'trip_earnings' => $trip_earnings,
    'advances'      => floatval($advances),
    'net_salary'    => $net_salary,
]);
