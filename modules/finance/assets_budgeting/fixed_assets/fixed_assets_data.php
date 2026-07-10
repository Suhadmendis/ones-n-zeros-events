<?php
// modules/finance/fixed_assets/fixed_assets_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $asset_name    = trim($data['asset_name']    ?? '');
    $asset_category= trim($data['asset_category']?? '');
    $purchase_date = trim($data['purchase_date'] ?? '');
    $purchase_cost = (float) ($data['purchase_cost']    ?? 0);
    $useful_life   = (int)   ($data['useful_life_years'] ?? 1);

    if ($asset_name     === '') { http_response_code(422); echo json_encode(['error' => 'Asset name is required.']); exit; }
    if ($asset_category === '') { http_response_code(422); echo json_encode(['error' => 'Asset category is required.']); exit; }
    if ($purchase_date  === '') { http_response_code(422); echo json_encode(['error' => 'Purchase date is required.']); exit; }
    if ($purchase_cost  <= 0)   { http_response_code(422); echo json_encode(['error' => 'Purchase cost must be greater than zero.']); exit; }
    if ($useful_life    < 1)    { http_response_code(422); echo json_encode(['error' => 'Useful life must be at least 1 year.']); exit; }

    $ref = consumeNextReference('fixed_assets');

    $record = supabase_post(SB_API . 'm_fixed_assets', [
        'ref'                      => $ref,
        'asset_name'               => $asset_name,
        'asset_category'           => $asset_category,
        'purchase_date'            => $purchase_date,
        'purchase_cost'            => $purchase_cost,
        'salvage_value'            => (float) ($data['salvage_value']            ?? 0),
        'useful_life_years'        => $useful_life,
        'depreciation_method'      => $data['depreciation_method']               ?? 'Straight Line',
        'accumulated_depreciation' => (float) ($data['accumulated_depreciation'] ?? 0),
        'location'                 => trim($data['location']    ?? '') ?: null,
        'description'              => trim($data['description'] ?? '') ?: null,
        'status'                   => $data['status'] ?? 'active',
        'created_by'               => current_user()['ref'] ?? null,
        'updated_by'               => current_user()['ref'] ?? null,
    ]);

    return $record;
}

function updateRecord(array $data): array {
    $asset_name    = trim($data['asset_name']    ?? '');
    $asset_category= trim($data['asset_category']?? '');
    $purchase_date = trim($data['purchase_date'] ?? '');
    $purchase_cost = (float) ($data['purchase_cost']    ?? 0);
    $useful_life   = (int)   ($data['useful_life_years'] ?? 1);

    if ($asset_name     === '') { http_response_code(422); echo json_encode(['error' => 'Asset name is required.']); exit; }
    if ($asset_category === '') { http_response_code(422); echo json_encode(['error' => 'Asset category is required.']); exit; }
    if ($purchase_date  === '') { http_response_code(422); echo json_encode(['error' => 'Purchase date is required.']); exit; }
    if ($purchase_cost  <= 0)   { http_response_code(422); echo json_encode(['error' => 'Purchase cost must be greater than zero.']); exit; }
    if ($useful_life    < 1)    { http_response_code(422); echo json_encode(['error' => 'Useful life must be at least 1 year.']); exit; }

    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_fixed_assets', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . 'm_fixed_assets?ref=eq.' . urlencode($ref), [
        'asset_name'               => $asset_name,
        'asset_category'           => $asset_category,
        'purchase_date'            => $purchase_date,
        'purchase_cost'            => $purchase_cost,
        'salvage_value'            => (float) ($data['salvage_value']            ?? 0),
        'useful_life_years'        => $useful_life,
        'depreciation_method'      => $data['depreciation_method']               ?? 'Straight Line',
        'accumulated_depreciation' => (float) ($data['accumulated_depreciation'] ?? 0),
        'location'                 => trim($data['location']    ?? '') ?: null,
        'description'              => trim($data['description'] ?? '') ?: null,
        'status'                   => $data['status'] ?? 'active',
        'updated_by'               => current_user()['ref'] ?? null,
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_fixed_assets?select=ref,asset_name,asset_category,purchase_date,purchase_cost,salvage_value,useful_life_years,depreciation_method,accumulated_depreciation,net_book_value,location,description,status&order=purchase_date.desc,id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listRecords());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveRecord($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateRecord($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_fixed_assets', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
