<?php
// vehicle_expense_entry_data.php — Vehicle expense module endpoint

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveVehicleExpense(array $data): array {
    $vehicle_ref = trim($data['vehicle_ref'] ?? '');
    if ($vehicle_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Vehicle is required.']);
        exit;
    }

    $ref = consumeNextReference('vehicle_expense_entry');

    $expense = supabase_post(SB_API . 'm_vehicle_expenses', [
        'ref'         => $ref,
        'vehicle_ref' => $vehicle_ref,
        'category'   => $data['category'] ?? 'repair',
        'remark'     => $data['remark']   ?? '',
        'amount'     => (float) ($data['amount'] ?? 0),
        'date'       => $data['date'] ?? date('Y-m-d'),
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    $amt = (float) ($data['amount'] ?? 0);
    if ($amt > 0) {
        jnl_create([
            'journal_date' => $data['date'] ?? date('Y-m-d'),
            'description'  => 'Vehicle expense (' . ($data['category'] ?? 'repair') . '): ' . $ref,
            'status'       => 'posted',
            'source_type'  => 'vehicle_expense',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => '5200', 'debit_amount' => $amt, 'credit_amount' => 0,    'description' => 'Vehicle Maintenance & Repair'],
                ['account_code' => '1100', 'debit_amount' => 0,    'credit_amount' => $amt, 'description' => 'Cash in Hand'],
            ],
        ]);
    }

    return $expense;
}

function updateVehicleExpense(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_vehicle_expenses', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $vehicle_ref = trim($data['vehicle_ref'] ?? '');
    if ($vehicle_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Vehicle is required.']);
        exit;
    }

    return supabase_patch(SB_API . 'm_vehicle_expenses?ref=eq.' . urlencode($ref), [
        'vehicle_ref' => $vehicle_ref,
        'category'   => $data['category'] ?? 'repair',
        'remark'     => $data['remark']   ?? '',
        'amount'     => (float) ($data['amount'] ?? 0),
        'date'       => $data['date'] ?? date('Y-m-d'),
        'updated_by' => current_user()['ref'] ?? null,
    ]);
}

function listVehicleExpenses(): array {
    return supabase_get(SB_API . 'm_vehicle_expenses?select=id,ref,vehicle_ref,category,remark,amount,date,m_vehicles(ref,plate_number,make,model)&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listVehicleExpenses());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveVehicleExpense($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateVehicleExpense($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_vehicle_expenses', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
