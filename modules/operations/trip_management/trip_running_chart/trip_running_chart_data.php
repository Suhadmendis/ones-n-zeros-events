<?php
// trip_running_chart_data.php — Trip / Running Chart API endpoint

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveTrip(array $data): array {
    $vehicle_ref = trim($data['vehicle_ref'] ?? '');
    $driver_ref  = trim($data['driver_ref']  ?? '');

    if ($vehicle_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Vehicle is required.']);
        exit;
    }
    if ($driver_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Driver is required.']);
        exit;
    }
    if (empty($data['date'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Date is required.']);
        exit;
    }
    if (empty($data['from_loc']) || empty($data['to_loc'])) {
        http_response_code(422);
        echo json_encode(['error' => 'From and To locations are required.']);
        exit;
    }

    $opening_km = (float) ($data['opening_km'] ?? 0);
    $closing_km = (float) ($data['closing_km'] ?? 0);
    $mileage    = round($closing_km - $opening_km, 2);

    $ref = consumeNextReference('trip_running_chart');

    $row = supabase_post(SB_API . 'm_trips', [
        'ref'         => $ref,
        'vehicle_ref' => $vehicle_ref,
        'date'        => $data['date'],
        'opening_km'  => $opening_km,
        'closing_km'  => $closing_km,
        'mileage'     => $mileage,
        'driver_ref'  => $driver_ref,
        'cleaner_ref' => !empty($data['cleaner_ref']) ? $data['cleaner_ref'] : null,
        'item_ref'    => !empty($data['item_ref'])    ? $data['item_ref']    : null,
        'item_name'  => $data['item_name']  ?: null,
        'run_no'     => $data['run_no']     ?: null,
        'from_loc'   => $data['from_loc'],
        'to_loc'     => $data['to_loc'],
        'amount'         => (float) ($data['amount']         ?? 0),
        'driver_salary'  => (float) ($data['driver_salary']  ?? 0),
        'cleaner_salary' => (float) ($data['cleaner_salary'] ?? 0),
        'department' => $data['department'] ?: null,
        'remark'     => $data['remark']     ?: null,
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    $amt = (float) ($data['amount'] ?? 0);
    if ($amt > 0) {
        jnl_create([
            'journal_date' => $data['date'],
            'description'  => 'Trip revenue: ' . $ref . ' (' . ($data['from_loc'] ?? '') . ' → ' . ($data['to_loc'] ?? '') . ')',
            'status'       => 'posted',
            'source_type'  => 'trip',
            'source_ref'   => $ref,
            'lines'        => [
                ['account_code' => '1200', 'debit_amount' => $amt, 'credit_amount' => 0,    'description' => 'Accounts Receivable'],
                ['account_code' => '4000', 'debit_amount' => 0,    'credit_amount' => $amt, 'description' => 'Transport Revenue'],
            ],
        ]);
    }

    return $row;
}

function updateTrip(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_trips', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $vehicle_ref = trim($data['vehicle_ref'] ?? '');
    $driver_ref  = trim($data['driver_ref']  ?? '');

    if ($vehicle_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Vehicle is required.']);
        exit;
    }
    if ($driver_ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Driver is required.']);
        exit;
    }
    if (empty($data['date'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Date is required.']);
        exit;
    }
    if (empty($data['from_loc']) || empty($data['to_loc'])) {
        http_response_code(422);
        echo json_encode(['error' => 'From and To locations are required.']);
        exit;
    }

    $opening_km = (float) ($data['opening_km'] ?? 0);
    $closing_km = (float) ($data['closing_km'] ?? 0);
    $mileage    = round($closing_km - $opening_km, 2);

    // Note: unlike saveTrip(), this does not call jnl_create() — re-posting the
    // revenue journal entry on every edit would create a duplicate GL entry per
    // save. Journal correction for an edited trip is a separate, deliberate action,
    // not an automatic side effect of updating trip fields.
    return supabase_patch(SB_API . 'm_trips?ref=eq.' . urlencode($ref), [
        'vehicle_ref' => $vehicle_ref,
        'date'        => $data['date'],
        'opening_km'  => $opening_km,
        'closing_km'  => $closing_km,
        'mileage'     => $mileage,
        'driver_ref'  => $driver_ref,
        'cleaner_ref' => !empty($data['cleaner_ref']) ? $data['cleaner_ref'] : null,
        'item_ref'    => !empty($data['item_ref'])    ? $data['item_ref']    : null,
        'item_name'  => $data['item_name']  ?: null,
        'run_no'     => $data['run_no']     ?: null,
        'from_loc'   => $data['from_loc'],
        'to_loc'     => $data['to_loc'],
        'amount'         => (float) ($data['amount']         ?? 0),
        'driver_salary'  => (float) ($data['driver_salary']  ?? 0),
        'cleaner_salary' => (float) ($data['cleaner_salary'] ?? 0),
        'department' => $data['department'] ?: null,
        'remark'     => $data['remark']     ?: null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);
}

function listTrips(): array {
    return supabase_get(
        SB_API . 'm_trips' .
        '?select=id,ref,vehicle_ref,date,opening_km,closing_km,mileage,driver_ref,cleaner_ref,item_ref,item_name,run_no,from_loc,to_loc,amount,driver_salary,cleaner_salary,department,remark' .
        ',vehicles:m_vehicles(ref,plate_number)' .
        ',drivers:m_drivers(ref,name)' .
        ',cleaners:m_cleaners(ref,name)' .
        ',items:m_items(ref,name)' .
        '&order=id.desc'
    );
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listTrips());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveTrip($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateTrip($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_trips', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
