<?php
// general_expense_type_data.php — General expense type module endpoint

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveExpenseType(array $data): array {
    $ref = consumeNextReference('general_expense_type');

    $type = supabase_post(SB_API . 'm_general_expense_types', [
        'ref'    => $ref,
        'name'   => $data['name']   ?? '',
        'status' => $data['status'] ?? 'active',
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    return $type;
}

function updateExpenseType(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_general_expense_types', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_general_expense_types?ref=eq.' . urlencode($ref), [
        'name'   => $data['name']   ?? '',
        'status' => $data['status'] ?? 'active',
        'updated_by' => current_user()['ref'] ?? null,
    ]);
}

function listExpenseTypes(): array {
    return supabase_get(SB_API . 'm_general_expense_types?select=id,ref,name,status&order=id.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listExpenseTypes());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveExpenseType($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateExpenseType($body));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_general_expense_types', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
