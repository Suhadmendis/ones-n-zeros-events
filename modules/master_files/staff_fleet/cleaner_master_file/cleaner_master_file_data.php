<?php
// cleaner_master_file_data.php — Cleaner module endpoint

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveCleaner(array $data): array {
    $ref = consumeNextReference('cleaner_master_file');

    $cleaner = supabase_post(SB_API . 'm_cleaners', [
        'ref'           => $ref,
        'name'          => $data['name']          ?? '',
        'phone'         => $data['phone']         ?? '',
        'status'        => $data['status']        ?? 'active',
        'date_of_birth' => $data['date_of_birth'] ?: null,
        'joining_date'  => $data['joining_date']  ?: null,
        'employee_ref'  => !empty($data['employee_ref']) ? $data['employee_ref'] : null,
        'created_by'    => current_user()['ref'] ?? null,
        'updated_by'    => current_user()['ref'] ?? null,
    ]);

    return $cleaner;
}

function updateCleaner(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_cleaners', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_cleaners?ref=eq.' . urlencode($ref), [
        'name'          => $data['name']          ?? '',
        'phone'         => $data['phone']         ?? '',
        'status'        => $data['status']        ?? 'active',
        'date_of_birth' => $data['date_of_birth'] ?: null,
        'joining_date'  => $data['joining_date']  ?: null,
        'employee_ref'  => !empty($data['employee_ref']) ? $data['employee_ref'] : null,
        'updated_by'    => current_user()['ref'] ?? null,
    ]);
}

function listCleaners(): array {
    return supabase_get(SB_API . 'm_cleaners?select=ref,name,phone,status,date_of_birth,joining_date,employee_ref,m_employees(ref,full_name)&order=id.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listCleaners());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveCleaner($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateCleaner($body));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_cleaners', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
