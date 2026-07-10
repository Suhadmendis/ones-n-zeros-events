<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action     = $_GET['action']     ?? 'report';
$from       = $_GET['from']       ?? date('Y-m-01');
$to         = $_GET['to']         ?? date('Y-m-d');
$vehicle_id = $_GET['vehicle_ref'] ?? '';

if ($action === 'list_vehicles') {
    $rows = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number,make&order=plate_number.asc");
    echo json_encode($rows);
    exit;
}

// report
$vehicles = supabase_get("/rest/v1/m_vehicles?select=ref,plate_number");

$filter = "date=gte.{$from}&date=lte.{$to}";
if ($vehicle_id !== '' && $vehicle_id !== 'all') {
    $filter .= "&vehicle_ref=eq.{$vehicle_id}";
}

$expenses = supabase_get("/rest/v1/m_vehicle_expenses?select=vehicle_ref,amount,category&{$filter}");

$cats = ['repair', 'service', 'tyre', 'battery', 'insurance', 'other'];
$veh_map = [];
foreach ($vehicles as $v) {
    $veh_map[$v['ref']] = ['ref' => $v['ref'] ?? '', 'plate_number' => $v['plate_number'] ?? ''];
}

$data_map = [];
foreach ($expenses as $e) {
    $vid = $e['vehicle_ref'];
    $cat = strtolower(trim($e['category'] ?? 'other'));
    if (!in_array($cat, $cats)) $cat = 'other';
    if (!isset($data_map[$vid])) {
        $data_map[$vid] = array_fill_keys($cats, 0);
    }
    $data_map[$vid][$cat] += floatval($e['amount'] ?? 0);
}

$rows = [];
foreach ($data_map as $vid => $catdata) {
    $total = array_sum($catdata);
    $veh   = $veh_map[$vid] ?? ['ref' => $vid, 'plate_number' => ''];
    $rows[] = array_merge([
        'ref'          => $veh['ref'],
        'plate_number' => $veh['plate_number'],
    ], $catdata, ['total' => $total]);
}

usort($rows, fn($a, $b) => $b['total'] <=> $a['total']);

$totals = ['ref' => 'TOTALS', 'plate_number' => ''];
foreach ($cats as $c) {
    $totals[$c] = array_sum(array_column($rows, $c));
}
$totals['total'] = array_sum(array_column($rows, 'total'));

echo json_encode(['rows' => $rows, 'totals' => $totals]);
