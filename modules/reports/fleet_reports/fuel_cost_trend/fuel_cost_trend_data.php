<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action     = $_GET['action']     ?? 'report';
$year       = (int)($_GET['year'] ?? date('Y'));
$vehicle_id = $_GET['vehicle_ref'] ?? '';

if ($action === 'list_vehicles') {
    $rows = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number,make&order=plate_number.asc");
    echo json_encode($rows);
    exit;
}

// report
$from = "{$year}-01-01";
$to   = "{$year}-12-31";
$filter = "date=gte.{$from}&date=lte.{$to}";
if ($vehicle_id !== '' && $vehicle_id !== 'all') {
    $filter .= "&vehicle_ref=eq.{$vehicle_id}";
}

$fuel = supabase_get("/rest/v1/m_fuel_expenses?select=date,liters,rate,total&{$filter}");

// Group by YYYY-MM
$by_month = [];
foreach ($fuel as $f) {
    $ym = substr($f['date'], 0, 7); // YYYY-MM
    if (!isset($by_month[$ym])) {
        $by_month[$ym] = ['litres' => 0, 'rate_sum' => 0, 'rate_count' => 0, 'cost' => 0];
    }
    $by_month[$ym]['litres']     += floatval($f['liters'] ?? 0);
    $by_month[$ym]['cost']       += floatval($f['total'] ?? 0);
    if (floatval($f['rate'] ?? 0) > 0) {
        $by_month[$ym]['rate_sum']   += floatval($f['rate']);
        $by_month[$ym]['rate_count'] += 1;
    }
}

$month_names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$rows = [];
for ($m = 1; $m <= 12; $m++) {
    $ym  = sprintf('%04d-%02d', $year, $m);
    $d   = $by_month[$ym] ?? ['litres' => 0, 'rate_sum' => 0, 'rate_count' => 0, 'cost' => 0];
    $avg_rate = $d['rate_count'] > 0 ? round($d['rate_sum'] / $d['rate_count'], 2) : 0;
    $rows[] = [
        'month_key'    => $ym,
        'month_label'  => $month_names[$m - 1] . ' ' . $year,
        'total_litres' => $d['litres'],
        'avg_rate'     => $avg_rate,
        'total_cost'   => $d['cost'],
    ];
}

echo json_encode(['rows' => $rows]);
