<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $trips = supabase_get(
        SB_API . 'm_trips?select=from_loc,to_loc,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
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
                'amounts'       => [],
            ];
        }
        $a = (float)($t['amount'] ?? 0);
        $groups[$key]['trip_count']++;
        $groups[$key]['total_revenue'] += $a;
        $groups[$key]['amounts'][] = $a;
    }

    $result = [];
    $summaryTrips   = 0;
    $summaryRevenue = 0.0;

    foreach ($groups as $row) {
        $cnt  = $row['trip_count'];
        $tot  = $row['total_revenue'];
        $amts = $row['amounts'];
        $result[] = [
            'from_loc'      => $row['from_loc'],
            'to_loc'        => $row['to_loc'],
            'trip_count'    => $cnt,
            'total_revenue' => round($tot, 2),
            'avg_revenue'   => round($cnt > 0 ? $tot / $cnt : 0, 2),
            'max_revenue'   => round(max($amts), 2),
            'min_revenue'   => round(min($amts), 2),
        ];
        $summaryTrips   += $cnt;
        $summaryRevenue += $tot;
    }

    usort($result, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

    echo json_encode([
        'rows'    => $result,
        'summary' => ['total_trips' => $summaryTrips, 'total_revenue' => round($summaryRevenue, 2)],
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
