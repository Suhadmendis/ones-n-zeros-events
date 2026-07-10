<?php
require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $loans    = supabase_get(SB_API . 'm_loans?select=id,ref,recipient_type,driver_ref,cleaner_ref,date,principal_amount,recovered_amount,status&order=id.desc');
    $drivers  = supabase_get(SB_API . 'm_drivers?select=ref,name');
    $cleaners = supabase_get(SB_API . 'm_cleaners?select=ref,name');

    $driverMap  = [];
    foreach ($drivers  as $d) $driverMap[$d['ref']]  = $d;
    $cleanerMap = [];
    foreach ($cleaners as $c) $cleanerMap[$c['ref']] = $c;

    foreach ($loans as &$loan) {
        if ($loan['recipient_type'] === 'driver') {
            $loan['recipient_ref']  = $loan['driver_ref'] ?? '';
            $loan['recipient_name'] = isset($driverMap[$loan['driver_ref']]) ? $driverMap[$loan['driver_ref']]['name'] : '';
        } else {
            $loan['recipient_ref']  = $loan['cleaner_ref'] ?? '';
            $loan['recipient_name'] = isset($cleanerMap[$loan['cleaner_ref']]) ? $cleanerMap[$loan['cleaner_ref']]['name'] : '';
        }
    }
    echo json_encode($loans);

} elseif ($action === 'list_drivers') {
    $rows = supabase_get(SB_API . 'm_drivers?select=ref,name,phone,status&status=eq.active&order=name.asc');
    echo json_encode($rows);

} elseif ($action === 'list_cleaners') {
    $rows = supabase_get(SB_API . 'm_cleaners?select=ref,name,phone,status&status=eq.active&order=name.asc');
    echo json_encode($rows);

} elseif ($action === 'save') {
    $data = json_decode(file_get_contents('php://input'), true);

    $recipientType = trim($data['recipient_type'] ?? '');
    $date          = trim($data['date'] ?? '');
    $principal     = $data['principal_amount'] ?? null;
    $recovered     = $data['recovered_amount'] ?? 0;
    $status        = trim($data['status'] ?? 'active');
    $driverRef     = trim($data['driver_ref']  ?? '');
    $cleanerRef    = trim($data['cleaner_ref'] ?? '');

    $errors = [];
    if (!$date)           $errors[] = 'Date is required.';
    if (!$recipientType)  $errors[] = 'Recipient type is required.';
    if ($recipientType === 'driver'  && !$driverRef)  $errors[] = 'Driver is required.';
    if ($recipientType === 'cleaner' && !$cleanerRef) $errors[] = 'Cleaner is required.';
    if ($principal === null || $principal === '') $errors[] = 'Principal amount is required.';

    if ($errors) { echo json_encode(['success' => false, 'errors' => $errors]); exit; }

    $ref = consumeNextReference('loan');

    $payload = [
        'ref'              => $ref,
        'recipient_type'   => $recipientType,
        'driver_ref'       => $recipientType === 'driver'  ? $driverRef  : null,
        'cleaner_ref'      => $recipientType === 'cleaner' ? $cleanerRef : null,
        'date'             => $date,
        'principal_amount' => (float)$principal,
        'recovered_amount' => (float)$recovered,
        'status'           => $status,
        'created_by'       => current_user()['ref'] ?? null,
        'updated_by'       => current_user()['ref'] ?? null,
    ];

    $result = supabase_post(SB_API . 'm_loans', $payload);
    if (!empty($result)) {
        $loanAmt = (float)$principal;
        if ($loanAmt > 0) {
            jnl_create([
                'journal_date' => $date,
                'description'  => 'Loan disbursed to ' . $recipientType . ': ' . $ref,
                'status'       => 'posted',
                'source_type'  => 'loan',
                'source_ref'   => $ref,
                'lines'        => [
                    ['account_code' => '1220', 'debit_amount' => $loanAmt, 'credit_amount' => 0,        'description' => 'Staff Loans Receivable'],
                    ['account_code' => '1100', 'debit_amount' => 0,        'credit_amount' => $loanAmt, 'description' => 'Cash in Hand'],
                ],
            ]);
        }
        echo json_encode(['success' => true, 'ref' => $ref]);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Failed to save loan.']]);
    }

} elseif ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);

    $ref           = trim($data['ref'] ?? '');
    $recipientType = trim($data['recipient_type'] ?? '');
    $date          = trim($data['date'] ?? '');
    $principal     = $data['principal_amount'] ?? null;
    $recovered     = $data['recovered_amount'] ?? 0;
    $status        = trim($data['status'] ?? 'active');
    $driverRef     = trim($data['driver_ref']  ?? '');
    $cleanerRef    = trim($data['cleaner_ref'] ?? '');

    if ($ref === '' || !recordExists('m_loans', $ref)) {
        echo json_encode(['success' => false, 'errors' => ['Record not found.']]);
        exit;
    }

    $errors = [];
    if (!$date)           $errors[] = 'Date is required.';
    if (!$recipientType)  $errors[] = 'Recipient type is required.';
    if ($recipientType === 'driver'  && !$driverRef)  $errors[] = 'Driver is required.';
    if ($recipientType === 'cleaner' && !$cleanerRef) $errors[] = 'Cleaner is required.';
    if ($principal === null || $principal === '') $errors[] = 'Principal amount is required.';

    if ($errors) { echo json_encode(['success' => false, 'errors' => $errors]); exit; }

    $payload = [
        'recipient_type'   => $recipientType,
        'driver_ref'       => $recipientType === 'driver'  ? $driverRef  : null,
        'cleaner_ref'      => $recipientType === 'cleaner' ? $cleanerRef : null,
        'date'             => $date,
        'principal_amount' => (float)$principal,
        'recovered_amount' => (float)$recovered,
        'status'           => $status,
        'updated_by'       => current_user()['ref'] ?? null,
    ];

    $result = supabase_patch(SB_API . 'm_loans?ref=eq.' . urlencode($ref), $payload);
    if (!empty($result)) {
        echo json_encode(['success' => true, 'ref' => $ref]);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Failed to update loan.']]);
    }

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_loans', $ref)]);

} else {
    echo json_encode(['error' => 'Unknown action']);
}
