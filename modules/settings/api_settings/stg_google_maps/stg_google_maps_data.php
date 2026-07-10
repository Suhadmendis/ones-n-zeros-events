<?php
// modules/settings/api_settings/stg_google_maps/stg_google_maps_data.php — Google Maps API settings endpoint
// Shares sys_integrations with modules/settings/integrations/integrations.php,
// scoped to the 'Maps' setting_group.

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/rbac.php';

header('Content-Type: application/json');

const GMAPS_GROUP = 'Maps';

function listSettings(): array {
    return supabase_get(
        SB_API . 'sys_integrations'
              . '?select=id,setting_key,setting_label,setting_value,setting_type,setting_group,options,description,is_system'
              . '&setting_group=eq.' . urlencode(GMAPS_GROUP)
              . '&order=id.asc'
    );
}

function updateSetting(array $data): array {
    $key   = trim($data['setting_key'] ?? '');
    $value = $data['setting_value'] ?? '';

    if ($key === '') { http_response_code(422); echo json_encode(['error' => 'Setting key is required.']); exit; }

    // Only settings belonging to this module's group may be updated here.
    $existing = supabase_get(
        SB_API . 'sys_integrations?select=id&setting_key=eq.' . urlencode($key)
              . '&setting_group=eq.' . urlencode(GMAPS_GROUP)
    );
    if (empty($existing)) { http_response_code(404); echo json_encode(['error' => 'Setting not found.']); exit; }

    return supabase_patch(SB_API . 'sys_integrations?setting_key=eq.' . urlencode($key), [
        'setting_value' => (string) $value,
        'updated_by'    => current_user()['ref'] ?? null,
        'updated_at'    => date('c'),
    ]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    requireModulePermission('can_view');
    echo json_encode(listSettings());
} elseif ($action === 'update') {
    requireModulePermission('can_edit');
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateSetting($body));
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
