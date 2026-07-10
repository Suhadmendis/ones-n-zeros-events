<?php
// fuel_entry_data.php — Fuel entry module endpoint

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveFuelEntry(array $data): array {
    $vehicle_ref = trim($data['vehicle_ref'] ?? '');
    if ($vehicle_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Vehicle is required.']);
        exit;
    }

    $liters = (float) ($data['liters'] ?? 0);
    $rate   = (float) ($data['rate']   ?? 0);
    $total  = round($liters * $rate, 2);

    $ref = consumeNextReference('fuel_entry');

    $entry = supabase_post(SB_API . 'm_fuel_expenses', [
        'ref'            => $ref,
        'vehicle_ref'    => $vehicle_ref,
        'date'           => $data['date']           ?? date('Y-m-d'),
        'liters'         => $liters,
        'rate'           => $rate,
        'total'          => $total,
        'voucher_number' => $data['voucher_number'] ?: null,
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ]);

    if ($total > 0) {
        jnl_create([
            'journal_date' => $data['date'] ?? date('Y-m-d'),
            'description'  => 'Fuel purchase: ' . $ref . ' (' . $liters . 'L)',
            'status'       => 'posted',
            'source_type'  => 'fuel_entry',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => '5100', 'debit_amount' => $total, 'credit_amount' => 0,      'description' => 'Fuel Expense'],
                ['account_code' => '1100', 'debit_amount' => 0,       'credit_amount' => $total, 'description' => 'Cash in Hand'],
            ],
        ]);
    }

    return $entry;
}

function updateFuelEntry(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_fuel_expenses', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $vehicle_ref = trim($data['vehicle_ref'] ?? '');
    if ($vehicle_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Vehicle is required.']);
        exit;
    }

    $liters = (float) ($data['liters'] ?? 0);
    $rate   = (float) ($data['rate']   ?? 0);
    $total  = round($liters * $rate, 2);

    return supabase_patch(SB_API . 'm_fuel_expenses?ref=eq.' . urlencode($ref), [
        'vehicle_ref'    => $vehicle_ref,
        'date'           => $data['date']           ?? date('Y-m-d'),
        'liters'         => $liters,
        'rate'           => $rate,
        'total'          => $total,
        'voucher_number' => $data['voucher_number'] ?: null,
        'updated_by'     => current_user()['ref'] ?? null,
    ]);
}

function listFuelEntries(): array {
    return supabase_get(SB_API . 'm_fuel_expenses?select=id,ref,vehicle_ref,date,liters,rate,total,voucher_number,m_vehicles(ref,plate_number,make,model)&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listFuelEntries());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveFuelEntry($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateFuelEntry($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_fuel_expenses', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
