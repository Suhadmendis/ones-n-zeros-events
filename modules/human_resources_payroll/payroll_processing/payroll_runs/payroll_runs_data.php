<?php
// payroll_runs_data.php — header (m_payroll_runs) + nested employee payroll rows
// (t_employee_payrolls) + their nested salary-component lines (t_employee_payroll_lines).
// Both child tables have no module or number series of their own (by design — they're
// wired into this screen, not standalone modules), so their refs are derived from their
// parent's ref, following the t_work_order_lines convention in work_order_entries_data.php.

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveEmployeePayrollLines(string $employeePayrollRef, array $lines): void {
    $lineNo = 0;
    foreach ($lines as $line) {
        if (empty($line['salary_component_ref']) && empty($line['amount'])) continue;

        $lineNo++;

        supabase_post(SB_API . 't_employee_payroll_lines', [
            'ref' => $employeePayrollRef . '-C' . $lineNo,
            'employee_payroll_ref' => $employeePayrollRef,
            'line_no' => $lineNo,
            'salary_component_ref' => !empty($line['salary_component_ref']) ? $line['salary_component_ref'] : null,
            'component_name' => $line['salary_component_name'] ?? null,
            'component_type' => $line['component_type'] ?? null,
            'quantity' => $line['quantity'] ?? 0,
            'rate' => $line['rate'] ?? 0,
            'amount' => $line['amount'] ?? 0,
        ]);
    }
}

function saveEmployeePayrolls(string $payrollRunRef, array $employees): void {
    $empNo = 0;
    foreach ($employees as $emp) {
        if (empty($emp['employment_record_ref'])) continue;

        $empNo++;
        $employeePayrollRef = $payrollRunRef . '-E' . $empNo;

        supabase_post(SB_API . 't_employee_payrolls', [
            'ref' => $employeePayrollRef,
            'payroll_run_ref' => $payrollRunRef,
            'employment_record_ref' => $emp['employment_record_ref'],
            'line_no' => $empNo,
            'basic_salary' => $emp['basic_salary'] ?? 0,
            'gross_earnings' => $emp['gross_earnings'] ?? 0,
            'total_deductions' => $emp['total_deductions'] ?? 0,
            'net_salary' => $emp['net_salary'] ?? 0,
            'payment_status' => $emp['payment_status'] ?? 'Pending',
            'paid_date' => !empty($emp['paid_date']) ? $emp['paid_date'] : null,
            'remarks' => $emp['remarks'] ?? '',
            'created_by' => current_user()['ref'] ?? null,
            'updated_by' => current_user()['ref'] ?? null,
        ]);

        saveEmployeePayrollLines($employeePayrollRef, $emp['lines'] ?? []);
    }
}

// Most recent salary assignment whose effective range overlaps the payroll period.
function findEffectiveSalaryAssignment(string $employmentRecordRef, string $periodStart, string $periodEnd): ?array {
    $assignments = supabase_get(SB_API . 'm_salary_assignments?select=ref,effective_from,effective_to&employment_record_ref=eq.' . urlencode($employmentRecordRef) . '&status=eq.active&order=effective_from.desc');
    foreach ($assignments as $a) {
        if (!empty($a['effective_from']) && $a['effective_from'] > $periodEnd) continue;
        if (!empty($a['effective_to']) && $a['effective_to'] < $periodStart) continue;
        return $a;
    }
    return null;
}

function generatePayrollForPeriod(string $payrollPeriodRef): array {
    $periods = supabase_get(SB_API . 'm_payroll_periods?select=ref,payroll_profile_ref,start_date,end_date&ref=eq.' . urlencode($payrollPeriodRef) . '&limit=1');
    if (empty($periods)) return ['error' => 'Payroll period not found.'];

    $period = $periods[0];
    if (empty($period['payroll_profile_ref'])) return ['error' => 'Payroll period has no payroll profile assigned.'];

    $records = supabase_get(SB_API . 'm_employment_records?select=ref,joining_date,termination_date,m_employees(full_name)&payroll_profile_ref=eq.' . urlencode($period['payroll_profile_ref']) . '&status=eq.active&order=ref.asc');

    $employees        = [];
    $skipped          = [];
    $totalGross       = 0.0;
    $totalDeductions  = 0.0;
    $totalNet         = 0.0;

    foreach ($records as $rec) {
        $name = $rec['m_employees']['full_name'] ?? $rec['ref'];

        if (!empty($rec['joining_date']) && $rec['joining_date'] > $period['end_date']) {
            $skipped[] = ['employment_record_ref' => $rec['ref'], 'employment_record_name' => $name, 'reason' => 'Joins after this payroll period ends.'];
            continue;
        }
        if (!empty($rec['termination_date']) && $rec['termination_date'] < $period['start_date']) {
            $skipped[] = ['employment_record_ref' => $rec['ref'], 'employment_record_name' => $name, 'reason' => 'Terminated before this payroll period starts.'];
            continue;
        }

        $assignment = findEffectiveSalaryAssignment($rec['ref'], $period['start_date'], $period['end_date']);
        if (!$assignment) {
            $skipped[] = ['employment_record_ref' => $rec['ref'], 'employment_record_name' => $name, 'reason' => 'No effective salary assignment for this period.'];
            continue;
        }

        $details = supabase_get(SB_API . 'm_salary_assignment_details?select=line_no,salary_component_ref,value,m_salary_components(name,component_type,affects_gross_salary,affects_net_salary)&salary_assignment_ref=eq.' . urlencode($assignment['ref']) . '&order=line_no.asc');
        $inputs  = supabase_get(SB_API . 'm_payroll_inputs?select=salary_component_ref,quantity,rate,amount,m_salary_components(name,component_type,affects_gross_salary,affects_net_salary)&employment_record_ref=eq.' . urlencode($rec['ref']) . '&payroll_period_ref=eq.' . urlencode($payrollPeriodRef) . '&approval_status=eq.Approved');

        $lines       = [];
        $basicSalary = 0.0;
        $gross       = 0.0;
        $deductions  = 0.0;

        foreach ($details as $d) {
            $component = $d['m_salary_components'] ?? [];
            $amount    = (float) ($d['value'] ?? 0);
            $type      = $component['component_type'] ?? '';
            $compName  = $component['name'] ?? '';

            $lines[] = [
                'salary_component_ref'  => $d['salary_component_ref'],
                'salary_component_name' => $compName,
                'component_type'        => $type,
                'quantity'              => 1,
                'rate'                  => $amount,
                'amount'                => $amount,
            ];

            if (stripos($compName, 'basic') !== false) $basicSalary += $amount;
            if ($type === 'Earning'   && ($component['affects_gross_salary'] ?? true)) $gross      += $amount;
            if ($type === 'Deduction' && ($component['affects_net_salary']   ?? true)) $deductions += $amount;
        }

        foreach ($inputs as $inp) {
            $component = $inp['m_salary_components'] ?? [];
            $quantity  = $inp['quantity'] ?? 1;
            $rate      = $inp['rate'] ?? 0;
            $amount    = ($inp['amount'] !== null && $inp['amount'] !== '') ? (float) $inp['amount'] : (float) $quantity * (float) $rate;
            $type      = $component['component_type'] ?? '';
            $compName  = $component['name'] ?? '';

            $lines[] = [
                'salary_component_ref'  => $inp['salary_component_ref'],
                'salary_component_name' => $compName,
                'component_type'        => $type,
                'quantity'              => $quantity,
                'rate'                  => $rate,
                'amount'                => $amount,
            ];

            if ($type === 'Earning'   && ($component['affects_gross_salary'] ?? true)) $gross      += $amount;
            if ($type === 'Deduction' && ($component['affects_net_salary']   ?? true)) $deductions += $amount;
        }

        $employees[] = [
            'employment_record_ref'  => $rec['ref'],
            'employment_record_name' => $name,
            'basic_salary'           => $basicSalary,
            'gross_earnings'         => $gross,
            'total_deductions'       => $deductions,
            'net_salary'             => $gross - $deductions,
            'payment_status'         => 'Pending',
            'paid_date'              => null,
            'remarks'                => '',
            'lines'                  => $lines,
        ];

        $totalGross      += $gross;
        $totalDeductions += $deductions;
        $totalNet        += $gross - $deductions;
    }

    return [
        'employees'              => $employees,
        'skipped'                => $skipped,
        'total_gross_amount'     => $totalGross,
        'total_deduction_amount' => $totalDeductions,
        'total_net_amount'       => $totalNet,
    ];
}

function savePayrollRuns(array $data): array {
    $ref = consumeNextReference('payroll_runs');

    $run = supabase_post(SB_API . 'm_payroll_runs', [
        'ref' => $ref,
        'payroll_period_ref' => !empty($data['payroll_period_ref']) ? $data['payroll_period_ref'] : null,
        'run_date' => !empty($data['run_date']) ? $data['run_date'] : null,
        'description' => $data['description'] ?? '',
        'payroll_status' => $data['payroll_status'] ?? '',
        'total_gross_amount' => $data['total_gross_amount'] ?? 0,
        'total_deduction_amount' => $data['total_deduction_amount'] ?? 0,
        'total_net_amount' => $data['total_net_amount'] ?? 0,
        'remarks' => $data['remarks'] ?? '',
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    if (!empty($run['ref'])) {
        saveEmployeePayrolls($run['ref'], $data['employees'] ?? []);
    }

    return $run;
}

function updatePayrollRuns(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_payroll_runs', $ref)) {
        return ['error' => 'Payroll run not found.'];
    }

    $run = supabase_patch(SB_API . 'm_payroll_runs?ref=eq.' . urlencode($ref), [
        'payroll_period_ref' => !empty($data['payroll_period_ref']) ? $data['payroll_period_ref'] : null,
        'run_date' => !empty($data['run_date']) ? $data['run_date'] : null,
        'description' => $data['description'] ?? '',
        'payroll_status' => $data['payroll_status'] ?? '',
        'total_gross_amount' => $data['total_gross_amount'] ?? 0,
        'total_deduction_amount' => $data['total_deduction_amount'] ?? 0,
        'total_net_amount' => $data['total_net_amount'] ?? 0,
        'remarks' => $data['remarks'] ?? '',
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    // Wipe the old employee/line set for this run before reinserting the current
    // form's set — otherwise stale rows from a prior save linger alongside the new
    // ones (duplicates). Lines first (they FK to employee payrolls), matched via a
    // PostgREST `like` wildcard on the employee-payroll-ref prefix, then the
    // employee payrolls themselves via a direct FK match.
    supabase_delete(SB_API . 't_employee_payroll_lines?employee_payroll_ref=like.' . urlencode($ref) . '-E*');
    supabase_delete(SB_API . 't_employee_payrolls?payroll_run_ref=eq.' . urlencode($ref));

    saveEmployeePayrolls($ref, $data['employees'] ?? []);

    return $run;
}

function cancelPayrollRun(string $ref): array {
    if ($ref === '' || !recordExists('m_payroll_runs', $ref)) {
        return ['error' => 'Payroll run not found.'];
    }

    $existing = supabase_get(SB_API . 'm_payroll_runs?select=payroll_status&ref=eq.' . urlencode($ref) . '&limit=1');
    $status = $existing[0]['payroll_status'] ?? '';

    if ($status === 'Cancelled') return ['error' => 'This payroll run is already cancelled.'];
    if ($status === 'Paid')      return ['error' => 'A paid payroll run cannot be cancelled.'];

    return supabase_patch(SB_API . 'm_payroll_runs?ref=eq.' . urlencode($ref), [
        'payroll_status' => 'Cancelled',
        'updated_by' => current_user()['ref'] ?? null,
    ]);
}

function listPayrollRuns(): array {
    return supabase_get(SB_API . 'm_payroll_runs?select=id,ref,payroll_period_ref,run_date,description,payroll_status,total_gross_amount,total_deduction_amount,total_net_amount,remarks,m_payroll_periods(name)&order=id.asc');
}

function getPayrollRunWithEmployees(string $ref): array {
    $headers = supabase_get(SB_API . 'm_payroll_runs?select=ref,payroll_period_ref,run_date,description,payroll_status,total_gross_amount,total_deduction_amount,total_net_amount,remarks,m_payroll_periods(name)&ref=eq.' . urlencode($ref) . '&limit=1');
    if (empty($headers)) return [];
    $run = $headers[0];

    $employees = supabase_get(SB_API . 't_employee_payrolls?select=ref,employment_record_ref,line_no,basic_salary,gross_earnings,total_deductions,net_salary,payment_status,paid_date,remarks,m_employment_records(ref,m_employees(full_name))&payroll_run_ref=eq.' . urlencode($ref) . '&order=line_no.asc');

    foreach ($employees as &$emp) {
        $emp['lines'] = supabase_get(SB_API . 't_employee_payroll_lines?select=ref,salary_component_ref,line_no,component_name,component_type,quantity,rate,amount,m_salary_components(name)&employee_payroll_ref=eq.' . urlencode($emp['ref']) . '&order=line_no.asc');
    }
    unset($emp);

    $run['employees'] = $employees;
    return $run;
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listPayrollRuns());

} elseif ($action === 'generate') {
    $payrollPeriodRef = $_GET['payroll_period_ref'] ?? '';
    if ($payrollPeriodRef === '') {
        http_response_code(400);
        echo json_encode(['error' => 'payroll_period_ref is required.']);
    } else {
        echo json_encode(generatePayrollForPeriod($payrollPeriodRef));
    }

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(savePayrollRuns($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updatePayrollRuns($body));

} elseif ($action === 'get') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(getPayrollRunWithEmployees($ref));

} elseif ($action === 'cancel') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $ref = $body['ref'] ?? '';
    echo json_encode(cancelPayrollRun($ref));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_payroll_runs', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
