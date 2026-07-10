<?php
// employee_payslips_data.php — read-only. No own table: looks up a saved payroll run's
// t_employee_payrolls row for the chosen employee + payroll period (and its
// t_employee_payroll_lines breakdown), both populated by the Payroll Runs screen.

require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_employees') {
    echo json_encode(supabase_get(SB_API . 'm_employees?select=ref,full_name&status=eq.active&order=full_name.asc'));
    exit;
}

if ($action === 'list_periods') {
    echo json_encode(supabase_get(SB_API . 'm_payroll_periods?select=ref,name&order=start_date.desc'));
    exit;
}

$employee_ref       = $_GET['employee_ref']       ?? '';
$payroll_period_ref = $_GET['payroll_period_ref'] ?? '';

if ($employee_ref === '' || $payroll_period_ref === '') {
    echo json_encode(['error' => 'employee_ref and payroll_period_ref are required']);
    exit;
}

$employee_rows = supabase_get(SB_API . 'm_employees?select=ref,full_name&ref=eq.' . urlencode($employee_ref));
$employee = $employee_rows[0] ?? [];

$period_rows = supabase_get(SB_API . 'm_payroll_periods?select=ref,name&ref=eq.' . urlencode($payroll_period_ref));
$period = $period_rows[0] ?? [];

// t_employee_payrolls links to the employee via m_employment_records, not directly —
// resolve the employee's employment record ref(s) first.
$employment_records     = supabase_get(SB_API . 'm_employment_records?select=ref&employee_ref=eq.' . urlencode($employee_ref));
$employment_record_refs = array_column($employment_records, 'ref');

if (empty($employment_record_refs)) {
    echo json_encode(['error' => 'This employee has no employment record.']);
    exit;
}

// A period may have more than one run (e.g. a corrected re-run) — use the latest.
$runs = supabase_get(SB_API . 'm_payroll_runs?select=ref&payroll_period_ref=eq.' . urlencode($payroll_period_ref) . '&order=id.desc');
if (empty($runs)) {
    echo json_encode(['error' => 'No payroll run has been processed for this period yet.']);
    exit;
}

$employmentRecordFilter = 'in.(' . implode(',', array_map('urlencode', $employment_record_refs)) . ')';

$payslip = null;
foreach ($runs as $run) {
    $rows = supabase_get(SB_API . 't_employee_payrolls?select=ref,basic_salary,gross_earnings,total_deductions,net_salary,payment_status,paid_date&payroll_run_ref=eq.' . urlencode($run['ref']) . '&employment_record_ref=' . $employmentRecordFilter . '&limit=1');
    if (!empty($rows)) {
        $payslip = $rows[0];
        $payslip['payroll_run_ref'] = $run['ref'];
        break;
    }
}

if (!$payslip) {
    echo json_encode(['error' => 'This employee was not included in any payroll run for this period.']);
    exit;
}

$lines = supabase_get(SB_API . 't_employee_payroll_lines?select=salary_component_ref,component_name,component_type,quantity,rate,amount,m_salary_components(name)&employee_payroll_ref=eq.' . urlencode($payslip['ref']) . '&order=line_no.asc');
foreach ($lines as &$line) {
    $line['component_name'] = $line['component_name'] ?? ($line['m_salary_components']['name'] ?? '');
}
unset($line);

echo json_encode([
    'employee_ref'        => $employee['ref']  ?? $employee_ref,
    'employee_name'       => $employee['full_name'] ?? '',
    'payroll_period_ref'  => $period['ref']  ?? $payroll_period_ref,
    'payroll_period_name' => $period['name'] ?? '',
    'payroll_run_ref'     => $payslip['payroll_run_ref'],
    'basic_salary'        => floatval($payslip['basic_salary'] ?? 0),
    'gross_earnings'      => floatval($payslip['gross_earnings'] ?? 0),
    'total_deductions'    => floatval($payslip['total_deductions'] ?? 0),
    'net_salary'          => floatval($payslip['net_salary'] ?? 0),
    'payment_status'      => $payslip['payment_status'] ?? '',
    'paid_date'           => $payslip['paid_date'] ?? null,
    'lines'                => $lines,
]);
