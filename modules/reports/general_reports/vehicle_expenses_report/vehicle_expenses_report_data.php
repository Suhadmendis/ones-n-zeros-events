<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list_vehicles') {
    echo json_encode(supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make&order=plate_number.asc'));
    exit;
}

if ($action === 'report') {
    $from        = $_GET['from']        ?? date('Y-m-01');
    $to          = $_GET['to']          ?? date('Y-m-d');
    $vehicle_ref = isset($_GET['vehicle_ref']) && $_GET['vehicle_ref'] !== '' ? $_GET['vehicle_ref'] : null;

    $url = SB_API . 'm_vehicle_expenses?select=ref,date,vehicle_ref,amount,category,remark&date=gte.'.$from.'&date=lte.'.$to.'&order=date.desc';
    if ($vehicle_ref) $url .= '&vehicle_ref=eq.'.$vehicle_ref;
    $expenses = supabase_get($url);

    $vehicles = supabase_get(SB_API . 'm_vehicles?select=ref,plate_number&order=ref.asc');
    $vmap = [];
    foreach ($vehicles as $v) $vmap[$v['ref']] = $v['plate_number'];

    $total = 0;
    $cats  = [];
    $rows  = [];
    foreach ($expenses as $e) {
        $amt  = floatval($e['amount']);
        $total += $amt;
        $cat = $e['category'] ?? 'other';
        $cats[$cat] = ($cats[$cat] ?? 0) + $amt;
        $rows[] = [
            'ref'          => $e['ref'],
            'date'         => $e['date'],
            'plate_number' => $vmap[$e['vehicle_ref']] ?? '',
            'category'     => $cat,
            'amount'       => $amt,
            'description'  => $e['remark'] ?? '',
        ];
    }
    echo json_encode(['rows' => $rows, 'summary' => ['total_amount' => $total, 'entry_count' => count($rows), 'category_totals' => $cats]]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
