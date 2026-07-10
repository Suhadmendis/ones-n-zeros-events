<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($action !== 'report') {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$from  = $_GET['from']  ?? date('Y-m-01');
$to    = $_GET['to']    ?? date('Y-m-t');
$limit = $_GET['limit'] ?? '10';

$drivers = supabase_get(SB_API . 'm_drivers?select=ref,name,status');
$trips   = supabase_get(SB_API . 'm_trips?select=driver_ref,amount,damount,day_pay&date=gte.' . $from . '&date=lte.' . $to);

$driver_map = [];
foreach ($drivers as $d) {
    $driver_map[$d['ref']] = $d;
}

$data = [];
foreach ($trips as $t) {
    $did = $t['driver_ref'];
    if (!$did) continue;
    if (!isset($data[$did])) {
        $data[$did] = ['trip_count' => 0, 'total_revenue' => 0, 'total_earning' => 0];
    }
    $data[$did]['trip_count']    += 1;
    $data[$did]['total_revenue'] += floatval($t['amount'] ?? 0);
    $data[$did]['total_earning'] += floatval($t['damount'] ?? 0) + floatval($t['day_pay'] ?? 0);
}

$rows = [];
foreach ($data as $did => $d) {
    $trip_count    = $d['trip_count'];
    $total_revenue = $d['total_revenue'];
    $avg_per_trip  = $trip_count > 0 ? round($total_revenue / $trip_count, 2) : 0;
    $driver        = $driver_map[$did] ?? ['ref' => '', 'name' => 'Unknown', 'status' => ''];
    $rows[] = [
        'ref'           => $did,
        'name'          => $driver['name'],
        'status'        => $driver['status'],
        'trip_count'    => $trip_count,
        'total_revenue' => round($total_revenue, 2),
        'total_earning' => round($d['total_earning'], 2),
        'avg_per_trip'  => $avg_per_trip,
    ];
}

usort($rows, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

if ($limit !== 'all' && is_numeric($limit)) {
    $rows = array_slice($rows, 0, intval($limit));
}

foreach ($rows as $i => &$row) {
    $row['rank'] = $i + 1;
}

echo json_encode(array_values($rows));
