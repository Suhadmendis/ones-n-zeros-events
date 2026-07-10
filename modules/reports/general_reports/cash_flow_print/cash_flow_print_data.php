<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$rows = supabase_get(SB_API . 'm_cash_flow_entries?select=ref,date,flow_type,category,amount,description&date=gte.'.$from.'&date=lte.'.$to.'&order=date.asc');

$inflow = $outflow = 0;
foreach ($rows as &$r) {
    $r['amount'] = floatval($r['amount']);
    if ($r['flow_type'] === 'inflow') $inflow  += $r['amount'];
    else                               $outflow += $r['amount'];
}

echo json_encode(['rows' => $rows, 'summary' => ['total_inflow' => $inflow, 'total_outflow' => $outflow, 'net_position' => $inflow - $outflow]]);
