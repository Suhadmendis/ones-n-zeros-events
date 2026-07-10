<?php
require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_employees') {
    echo json_encode(supabase_get(SB_API . 'm_employees?select=ref,full_name&order=full_name.asc'));
    exit;
}

// report
$from         = $_GET['from']         ?? date('Y-m-01');
$to           = $_GET['to']           ?? date('Y-m-t');
$employee_ref = $_GET['employee_ref'] ?? '';

$employees = supabase_get(SB_API . 'm_employees?select=ref,full_name&order=full_name.asc');
$emap = [];
foreach ($employees as $e) $emap[$e['ref']] = $e;

$qs = SB_API . 'm_advance_payments?select=id,ref,date,employee_ref,amount&date=gte.'.$from.'&date=lte.'.$to;
if ($employee_ref !== '') $qs .= '&employee_ref=eq.'.$employee_ref;
$qs .= '&order=date.asc';

$advances = supabase_get($qs);

$rows = [];
$total_amount = 0;
foreach ($advances as $a) {
    $e = $emap[$a['employee_ref']] ?? [];
    $rows[] = [
        'employee_ref'  => $e['ref']       ?? '',
        'employee_name' => $e['full_name'] ?? '',
        'advance_ref'   => $a['ref']  ?? '',
        'date'          => $a['date'] ?? '',
        'amount'        => floatval($a['amount']),
    ];
    $total_amount += floatval($a['amount']);
}

usort($rows, fn($a,$b) => [$a['employee_name'],$a['date']] <=> [$b['employee_name'],$b['date']]);

echo json_encode([
    'rows'    => $rows,
    'summary' => ['total_amount' => $total_amount, 'entry_count' => count($rows)],
]);
