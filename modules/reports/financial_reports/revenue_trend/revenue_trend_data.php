<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $year = (int)($_GET['year'] ?? date('Y'));
    $from = $year . '-01-01';
    $to   = $year . '-12-31';

    $monthNames = [
        '01' => 'January', '02' => 'February', '03' => 'March',
        '04' => 'April',   '05' => 'May',       '06' => 'June',
        '07' => 'July',    '08' => 'August',     '09' => 'September',
        '10' => 'October', '11' => 'November',   '12' => 'December',
    ];

    $agg = [];
    $trips = supabase_get(SB_API . 'm_trips?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000');
    foreach ($trips as $t) {
        $mk = substr($t['date'] ?? '', 0, 7);
        if (!$mk) continue;
        if (!isset($agg[$mk])) $agg[$mk] = ['revenue' => 0.0, 'trip_count' => 0];
        $agg[$mk]['revenue']     += (float)($t['amount'] ?? 0);
        $agg[$mk]['trip_count']  += 1;
    }

    // Build 12 months and compute MoM
    $result = [];
    $prev_revenue = null;
    for ($m = 1; $m <= 12; $m++) {
        $mm    = str_pad($m, 2, '0', STR_PAD_LEFT);
        $key   = $year . '-' . $mm;
        $rev   = round($agg[$key]['revenue']    ?? 0.0, 2);
        $cnt   = $agg[$key]['trip_count'] ?? 0;
        $avg   = $cnt > 0 ? round($rev / $cnt, 2) : 0.0;
        $mom   = null;
        if ($prev_revenue !== null && $prev_revenue > 0) {
            $mom = round(($rev - $prev_revenue) / $prev_revenue * 100, 2);
        }
        $result[] = [
            'month_label'  => $monthNames[$mm],
            'revenue'      => $rev,
            'trip_count'   => $cnt,
            'avg_per_trip' => $avg,
            'mom_change'   => $mom,
        ];
        $prev_revenue = $rev;
    }

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
