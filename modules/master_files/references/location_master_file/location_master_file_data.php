<?php
// location_master_file_data.php — Location module endpoint

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveLocation(array $data): array {
    $ref = consumeNextReference('location_master_file');

    $location = supabase_post(SB_API . 'm_locations', [
        'ref'      => $ref,
        'name'     => $data['name']     ?? '',
        'district' => $data['district'] ?? '',
        'province' => $data['province'] ?? '',
        'status'   => $data['status']   ?? 'active',
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    return $location;
}

function updateLocation(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_locations', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_locations?ref=eq.' . urlencode($ref), [
        'name'     => $data['name']     ?? '',
        'district' => $data['district'] ?? '',
        'province' => $data['province'] ?? '',
        'status'   => $data['status']   ?? 'active',
        'updated_by' => current_user()['ref'] ?? null,
    ]);
}

function listLocations(): array {
    return supabase_get(SB_API . 'm_locations?select=ref,name,district,province,status&order=id.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listLocations());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveLocation($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateLocation($body));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_locations', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
