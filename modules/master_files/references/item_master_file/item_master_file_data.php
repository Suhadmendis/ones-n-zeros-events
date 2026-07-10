<?php
// item_master_file_data.php — Item module endpoint

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveItem(array $data): array {
    $ref = consumeNextReference('item_master_file');

    $item = supabase_post(SB_API . 'm_items', [
        'ref'         => $ref,
        'name'        => $data['name']        ?? '',
        'category'    => $data['category']    ?? 'other',
        'unit'        => $data['unit']        ?? '',
        'description' => $data['description'] ?: null,
        'status'      => $data['status']      ?? 'active',
        'created_by'  => current_user()['ref'] ?? null,
        'updated_by'  => current_user()['ref'] ?? null,
    ]);

    return $item;
}

function updateItem(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_items', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_items?ref=eq.' . urlencode($ref), [
        'name'        => $data['name']        ?? '',
        'category'    => $data['category']    ?? 'other',
        'unit'        => $data['unit']        ?? '',
        'description' => $data['description'] ?: null,
        'status'      => $data['status']      ?? 'active',
        'updated_by'  => current_user()['ref'] ?? null,
    ]);
}

function listItems(): array {
    return supabase_get(SB_API . 'm_items?select=ref,name,category,unit,description,status&order=id.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listItems());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveItem($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateItem($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_items', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
