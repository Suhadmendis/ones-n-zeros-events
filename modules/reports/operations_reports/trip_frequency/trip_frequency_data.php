<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $trips = supabase_get(
        SB_API . 'm_trips?select=from_loc,to_loc,amount,date&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );

    $groups = [];
    foreach ($trips as $t) {
        $key = ($t['from_loc'] ?? '') . '|||' . ($t['to_loc'] ?? '');
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'from_loc'      => $t['from_loc'] ?? '',
                'to_loc'        => $t['to_loc']   ?? '',
                'trip_count'    => 0,
                'total_revenue' => 0.0,
                'dates'         => [],
            ];
        }
        $groups[$key]['trip_count']++;
        $groups[$key]['total_revenue'] += (float)($t['amount'] ?? 0);
        if (!empty($t['date'])) $groups[$key]['dates'][] = $t['date'];
    }

    $result = [];
    $summaryTrips   = 0;
    $summaryRevenue = 0.0;

    foreach ($groups as $row) {
        $dates = $row['dates'];
        sort($dates);
        $result[] = [
            'from_loc'      => $row['from_loc'],
            'to_loc'        => $row['to_loc'],
            'trip_count'    => $row['trip_count'],
            'total_revenue' => round($row['total_revenue'], 2),
            'first_trip'    => $dates[0]               ?? '',
            'last_trip'     => end($dates) ?: '',
        ];
        $summaryTrips   += $row['trip_count'];
        $summaryRevenue += $row['total_revenue'];
    }

    usort($result, fn($a, $b) => $b['trip_count'] <=> $a['trip_count']);

    echo json_encode([
        'rows'    => $result,
        'summary' => ['total_trips' => $summaryTrips, 'total_revenue' => round($summaryRevenue, 2)],
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
