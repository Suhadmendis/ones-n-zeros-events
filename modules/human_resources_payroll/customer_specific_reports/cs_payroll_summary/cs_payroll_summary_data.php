<?php
// cs_payroll_summary_data.php — read-only. No own table: summarizes a saved Payroll Run's
// results for a Payroll Period, drawing from m_payroll_runs (header totals) and
// t_employee_payrolls (per-employee rows), both populated by the Payroll Runs screen.
// Never recalculates — only reads what Payroll Runs already saved. Same data/logic as
// payroll_inquiry/payroll_summary — this is the Customer-Specific Reports copy of it.

require_once __DIR__ . '/../../../../server/supabase.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'report';

if ($action === 'list_periods') {
    echo json_encode(supabase_get(SB_API . 'm_payroll_periods?select=ref,name&order=start_date.desc'));
    exit;
}

$payroll_period_ref = $_GET['payroll_period_ref'] ?? '';

if ($payroll_period_ref === '') {
    echo json_encode(['error' => 'payroll_period_ref is required']);
    exit;
}

$period_rows = supabase_get(SB_API . 'm_payroll_periods?select=ref,name&ref=eq.' . urlencode($payroll_period_ref));
$period = $period_rows[0] ?? [];

// A period may have more than one run (e.g. a corrected re-run) — use the latest.
$runs = supabase_get(SB_API . 'm_payroll_runs?select=ref,run_date,payroll_status,total_gross_amount,total_deduction_amount,total_net_amount&payroll_period_ref=eq.' . urlencode($payroll_period_ref) . '&order=id.desc&limit=1');
if (empty($runs)) {
    echo json_encode(['error' => 'No payroll run has been processed for this period yet.']);
    exit;
}
$run = $runs[0];

// Salary component refs for the extra breakdown columns — matched against the
// payroll run's own saved t_employee_payroll_lines, never recalculated.
const SALC_TRANSPORT_ALLOWANCE      = 'SALC-0000009';
const SALC_BUDGET_ALLOWANCE_2025    = 'SALC-0000011';
const SALC_BUDGET_ALLOW_GOV_ALLOW   = 'SALC-0000012';
const SALC_EPF_EMPLOYEE             = 'SALC-0000014';

$employees = supabase_get(SB_API . 't_employee_payrolls?select=basic_salary,gross_earnings,total_deductions,net_salary,payment_status,line_no,m_employment_records(m_employees(full_name)),t_employee_payroll_lines(salary_component_ref,amount)&payroll_run_ref=eq.' . urlencode($run['ref']) . '&order=line_no.asc');

$rows = array_map(function ($e) {
    $byComponent = array_column($e['t_employee_payroll_lines'] ?? [], 'amount', 'salary_component_ref');
    return [
        'employee_name'              => $e['m_employment_records']['m_employees']['full_name'] ?? '',
        'basic_salary'               => floatval($e['basic_salary'] ?? 0),
        'budget_allowance_2025'      => floatval($byComponent[SALC_BUDGET_ALLOWANCE_2025] ?? 0),
        'budget_allow_gov_allow'     => floatval($byComponent[SALC_BUDGET_ALLOW_GOV_ALLOW] ?? 0),
        'transport_allowance'        => floatval($byComponent[SALC_TRANSPORT_ALLOWANCE] ?? 0),
        'gross_earnings'             => floatval($e['gross_earnings'] ?? 0),
        'epf_employee_contribution'  => floatval($byComponent[SALC_EPF_EMPLOYEE] ?? 0),
        'total_deductions'           => floatval($e['total_deductions'] ?? 0),
        'net_salary'                 => floatval($e['net_salary'] ?? 0),
        'payment_status'             => $e['payment_status'] ?? '',
    ];
}, $employees);

echo json_encode([
    'payroll_period_ref'     => $period['ref']  ?? $payroll_period_ref,
    'payroll_period_name'    => $period['name'] ?? '',
    'payroll_run_ref'        => $run['ref'],
    'payroll_status'         => $run['payroll_status'] ?? '',
    'employee_count'         => count($rows),
    'total_gross_amount'     => floatval($run['total_gross_amount'] ?? 0),
    'total_deduction_amount' => floatval($run['total_deduction_amount'] ?? 0),
    'total_net_amount'       => floatval($run['total_net_amount'] ?? 0),
    'employees'              => $rows,
]);
