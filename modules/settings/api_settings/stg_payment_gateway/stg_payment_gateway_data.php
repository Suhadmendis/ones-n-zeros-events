<?php
// modules/settings/api_settings/stg_payment_gateway/stg_payment_gateway_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

const SETTING_GROUP = 'Payment Gateway';

function listSettings(): array {
    return supabase_get(
        SB_API . 'sys_integrations'
              . '?select=id,setting_key,setting_label,setting_value,setting_type,setting_group,options,description,is_system'
              . '&setting_group=eq.' . urlencode(SETTING_GROUP)
              . '&order=setting_group.asc,id.asc'
    );
}

function updateSetting(array $data): array {
    $key   = trim($data['setting_key'] ?? '');
    $value = $data['setting_value'] ?? '';

    if ($key === '') { http_response_code(422); echo json_encode(['error' => 'Setting key is required.']); exit; }

    return supabase_patch(SB_API . 'sys_integrations?setting_key=eq.' . urlencode($key) . '&setting_group=eq.' . urlencode(SETTING_GROUP), [
        'setting_value' => (string) $value,
        'updated_by'    => current_user()['ref'] ?? null,
        'updated_at'    => date('c'),
    ]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listSettings());
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateSetting($body));
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
