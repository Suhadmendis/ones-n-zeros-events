<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');

    $trips = supabase_get(
        SB_API . 'm_trips?select=department,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );

    $groups = [];
    foreach ($trips as $t) {
        $dept = !empty($t['department']) ? $t['department'] : 'Unassigned';
        if (!isset($groups[$dept])) {
            $groups[$dept] = ['trip_count' => 0, 'total_revenue' => 0.0];
        }
        $groups[$dept]['trip_count']++;
        $groups[$dept]['total_revenue'] += (float)($t['amount'] ?? 0);
    }

    $grandTotal = array_sum(array_column($groups, 'total_revenue'));

    $result = [];
    foreach ($groups as $dept => $g) {
        $result[] = [
            'department'    => $dept,
            'trip_count'    => $g['trip_count'],
            'total_revenue' => round($g['total_revenue'], 2),
            'pct_of_total'  => $grandTotal > 0 ? round($g['total_revenue'] / $grandTotal * 100, 2) : 0,
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
