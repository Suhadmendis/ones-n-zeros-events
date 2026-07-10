<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'report') {
    $year = (int)($_GET['year'] ?? date('Y'));

    $from = $year . '-01-01';
    $to   = $year . '-12-31';

    // Fetch all trips in the year (only the columns we need)
    $trips = supabase_get(
        SB_API . 'm_trips?select=date,amount&date=gte.' . $from . '&date=lte.' . $to . '&limit=100000'
    );

    // Month-name lookup
    $monthNames = [
        '01' => 'January', '02' => 'February', '03' => 'March',
        '04' => 'April',   '05' => 'May',       '06' => 'June',
        '07' => 'July',    '08' => 'August',     '09' => 'September',
        '10' => 'October', '11' => 'November',   '12' => 'December',
    ];

    // Aggregate by month
    $agg = [];
    foreach ($trips as $t) {
        $monthKey = substr($t['date'] ?? '', 0, 7); // YYYY-MM
        if (!$monthKey) continue;
        if (!isset($agg[$monthKey])) {
            $agg[$monthKey] = ['trip_count' => 0, 'revenue' => 0.0];
        }
        $agg[$monthKey]['trip_count']++;
        $agg[$monthKey]['revenue'] += (float)($t['amount'] ?? 0);
    }

    // Build 12-month result (include months with zero data for completeness)
    $result = [];
    for ($m = 1; $m <= 12; $m++) {
        $mm  = str_pad($m, 2, '0', STR_PAD_LEFT);
        $key = $year . '-' . $mm;
        $result[] = [
            'month_key'   => $key,
            'month_label' => $monthNames[$mm],
            'trip_count'  => $agg[$key]['trip_count'] ?? 0,
            'revenue'     => round($agg[$key]['revenue'] ?? 0.0, 2),
        ];
    }

    echo json_encode($result);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
