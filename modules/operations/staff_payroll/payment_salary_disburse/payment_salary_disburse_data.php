<?php
require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $payments = supabase_get(SB_API . 'm_payments?select=id,ref,recipient_type,driver_ref,cleaner_ref,date,amount,payment_type,remark&order=id.desc');
    $drivers  = supabase_get(SB_API . 'm_drivers?select=ref,name');
    $cleaners = supabase_get(SB_API . 'm_cleaners?select=ref,name');

    $driverMap  = [];
    foreach ($drivers  as $d) $driverMap[$d['ref']]  = $d;
    $cleanerMap = [];
    foreach ($cleaners as $c) $cleanerMap[$c['ref']] = $c;

    foreach ($payments as &$payment) {
        if ($payment['recipient_type'] === 'driver') {
            $payment['recipient_ref']  = $payment['driver_ref'] ?? '';
            $payment['recipient_name'] = isset($driverMap[$payment['driver_ref']]) ? $driverMap[$payment['driver_ref']]['name'] : '';
        } else {
            $payment['recipient_ref']  = $payment['cleaner_ref'] ?? '';
            $payment['recipient_name'] = isset($cleanerMap[$payment['cleaner_ref']]) ? $cleanerMap[$payment['cleaner_ref']]['name'] : '';
        }
    }
    echo json_encode($payments);

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
    $amount        = $data['amount'] ?? null;
    $paymentType   = trim($data['payment_type'] ?? '');
    $remark        = trim($data['remark'] ?? '');
    $driverRef     = trim($data['driver_ref']  ?? '');
    $cleanerRef    = trim($data['cleaner_ref'] ?? '');

    $errors = [];
    if (!$date)          $errors[] = 'Date is required.';
    if (!$recipientType) $errors[] = 'Recipient type is required.';
    if ($recipientType === 'driver'  && !$driverRef)  $errors[] = 'Driver is required.';
    if ($recipientType === 'cleaner' && !$cleanerRef) $errors[] = 'Cleaner is required.';
    if ($amount === null || $amount === '') $errors[] = 'Amount is required.';
    if (!$paymentType)   $errors[] = 'Payment type is required.';

    if ($errors) { echo json_encode(['success' => false, 'errors' => $errors]); exit; }

    $ref = consumeNextReference('payment_salary_disburse');

    $payload = [
        'ref'            => $ref,
        'recipient_type' => $recipientType,
        'driver_ref'     => $recipientType === 'driver'  ? $driverRef  : null,
        'cleaner_ref'    => $recipientType === 'cleaner' ? $cleanerRef : null,
        'date'           => $date,
        'amount'         => (float)$amount,
        'payment_type'   => $paymentType,
        'remark'         => $remark ?: null,
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ];

    $result = supabase_post(SB_API . 'm_payments', $payload);
    if (!empty($result)) {
        $payAmt         = (float)$amount;
        $payableAccount = $recipientType === 'driver' ? '2200' : '2210';
        $payableDesc    = $recipientType === 'driver' ? 'Driver Salaries Payable' : 'Cleaner Salaries Payable';
        if ($payAmt > 0) {
            jnl_create([
                'journal_date' => $date,
                'description'  => ucfirst($recipientType) . ' salary payment: ' . $ref,
                'status'       => 'posted',
                'source_type'  => 'payment',
                'source_ref'   => $ref,
                'lines'        => [
                    ['account_code' => $payableAccount, 'debit_amount' => $payAmt, 'credit_amount' => 0,       'description' => $payableDesc],
                    ['account_code' => '1100',           'debit_amount' => 0,       'credit_amount' => $payAmt, 'description' => 'Cash in Hand'],
                ],
            ]);
        }
        echo json_encode(['success' => true, 'ref' => $ref]);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Failed to save payment.']]);
    }

} elseif ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);

    $ref           = trim($data['ref'] ?? '');
    $recipientType = trim($data['recipient_type'] ?? '');
    $date          = trim($data['date'] ?? '');
    $amount        = $data['amount'] ?? null;
    $paymentType   = trim($data['payment_type'] ?? '');
    $remark        = trim($data['remark'] ?? '');
    $driverRef     = trim($data['driver_ref']  ?? '');
    $cleanerRef    = trim($data['cleaner_ref'] ?? '');

    if ($ref === '' || !recordExists('m_payments', $ref)) {
        echo json_encode(['success' => false, 'errors' => ['Record not found.']]);
        exit;
    }

    $errors = [];
    if (!$date)          $errors[] = 'Date is required.';
    if (!$recipientType) $errors[] = 'Recipient type is required.';
    if ($recipientType === 'driver'  && !$driverRef)  $errors[] = 'Driver is required.';
    if ($recipientType === 'cleaner' && !$cleanerRef) $errors[] = 'Cleaner is required.';
    if ($amount === null || $amount === '') $errors[] = 'Amount is required.';
    if (!$paymentType)   $errors[] = 'Payment type is required.';

    if ($errors) { echo json_encode(['success' => false, 'errors' => $errors]); exit; }

    $payload = [
        'recipient_type' => $recipientType,
        'driver_ref'     => $recipientType === 'driver'  ? $driverRef  : null,
        'cleaner_ref'    => $recipientType === 'cleaner' ? $cleanerRef : null,
        'date'           => $date,
        'amount'         => (float)$amount,
        'payment_type'   => $paymentType,
        'remark'         => $remark ?: null,
        'updated_by'     => current_user()['ref'] ?? null,
    ];

    $result = supabase_patch(SB_API . 'm_payments?ref=eq.' . urlencode($ref), $payload);
    if (!empty($result)) {
        echo json_encode(['success' => true, 'ref' => $ref]);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Failed to update payment.']]);
    }

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_payments', $ref)]);

} else {
    echo json_encode(['error' => 'Unknown action']);
}
