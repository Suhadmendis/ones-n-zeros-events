<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$rows = supabase_get(SB_API . 'm_general_expenses?select=ref,date,amount,description&date=gte.'.$from.'&date=lte.'.$to.'&order=date.desc');
$total = array_sum(array_column(array_map(fn($r)=>['a'=>floatval($r['amount'])],$rows),'a'));
echo json_encode(['rows' => $rows, 'summary' => ['total_amount' => $total, 'entry_count' => count($rows)]]);
