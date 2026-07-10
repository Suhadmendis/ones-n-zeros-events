<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $trips = supabase_get(
        SB_API . 'm_trips?select=item_name,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );

    $groups = [];
    foreach ($trips as $t) {
        $item = !empty($t['item_name']) ? $t['item_name'] : 'Unspecified';
        if (!isset($groups[$item])) {
            $groups[$item] = ['trip_count' => 0, 'total_revenue' => 0.0];
        }
        $groups[$item]['trip_count']++;
        $groups[$item]['total_revenue'] += (float)($t['amount'] ?? 0);
    }

    $grandTotal = array_sum(array_column($groups, 'total_revenue'));

    $result = [];
    foreach ($groups as $item => $g) {
        $cnt = $g['trip_count'];
        $tot = $g['total_revenue'];
        $result[] = [
            'item_name'     => $item,
            'trip_count'    => $cnt,
            'total_revenue' => round($tot, 2),
            'avg_revenue'   => round($cnt > 0 ? $tot / $cnt : 0, 2),
            'pct_of_total'  => $grandTotal > 0 ? round($tot / $grandTotal * 100, 2) : 0,
        ];
    }

    usort($result, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

    echo json_encode([
        'rows'  => $result,
        'total' => round($grandTotal, 2),
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
