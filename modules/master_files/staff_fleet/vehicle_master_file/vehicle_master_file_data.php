<?php
// vehicle_master_file_data.php — Vehicle module endpoint

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveVehicle(array $data): array {
    $ref = consumeNextReference('vehicle_master_file');

    $vehicle = supabase_post(SB_API . 'm_vehicles', [
        'ref'           => $ref,
        'plate_number'  => $data['plate_number']  ?? '',
        'make'          => $data['make']           ?? '',
        'model'         => $data['model']          ?? '',
        'type'          => $data['type']           ?? 'lorry',
        'fuel_type'     => $data['fuel_type']      ?? 'diesel',
        'status'        => $data['status']         ?? 'active',
        'last_location' => $data['last_location']  ?? 'Depot',
        'mileage'       => (int) ($data['mileage']  ?? 0),
        'year'          => $data['year'] !== '' ? (int) $data['year'] : null,
        'capacity'      => (int) ($data['capacity'] ?? 0),
        'created_by'    => current_user()['ref'] ?? null,
        'updated_by'    => current_user()['ref'] ?? null,
    ]);

    return $vehicle;
}

function updateVehicle(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_vehicles', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_vehicles?ref=eq.' . urlencode($ref), [
        'plate_number'  => $data['plate_number']  ?? '',
        'make'          => $data['make']           ?? '',
        'model'         => $data['model']          ?? '',
        'type'          => $data['type']           ?? 'lorry',
        'fuel_type'     => $data['fuel_type']      ?? 'diesel',
        'status'        => $data['status']         ?? 'active',
        'last_location' => $data['last_location']  ?? 'Depot',
        'mileage'       => (int) ($data['mileage']  ?? 0),
        'year'          => $data['year'] !== '' ? (int) $data['year'] : null,
        'capacity'      => (int) ($data['capacity'] ?? 0),
        'updated_by'    => current_user()['ref'] ?? null,
    ]);
}

function listVehicles(): array {
    return supabase_get(SB_API . 'm_vehicles?select=ref,plate_number,make,model,type,fuel_type,status,last_location,mileage,year,capacity&order=id.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listVehicles());

} elseif ($action === 'get_ref') {
    echo json_encode(['ref' => previewNextReference('vehicle_master_file')['ref']]);

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveVehicle($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateVehicle($body));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_vehicles', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
