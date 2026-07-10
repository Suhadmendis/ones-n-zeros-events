<?php
// user_permissions_data.php — Role → module permission grid endpoint

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/general/rbac.php';

header('Content-Type: application/json');

const RMP_ACTIONS = ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve', 'can_export', 'can_import', 'can_print'];

function listRoles(): array {
    return supabase_get(SB_API . 'sys_roles?select=ref,name&record_status=eq.active&order=name.asc');
}

function listModules(): array {
    $sections = supabase_get(SB_API . 'sys_tms_sections?select=ref,name,web_icon,web_color&web_enable=eq.1');
    if (!$sections) return [];
    $sectionByRef       = array_column($sections, null, 'ref');
    $enabledSectionRefs = array_column($sections, 'ref');

    $subsections    = supabase_get(SB_API . 'sys_tms_subsections?select=ref,section_ref,name');
    $subsectionMap  = array_column($subsections, null, 'ref');
    $allowedSubRefs = array_column(array_filter($subsections, fn($s) => in_array($s['section_ref'], $enabledSectionRefs)), 'ref');
    if (!$allowedSubRefs) return [];

    $refsList = implode(',', $allowedSubRefs);
    $modules  = supabase_get(SB_API . "sys_tms_modules?select=ref,name,subsection_ref&subsection_ref=in.({$refsList})&order=subsection_ref.asc,id.asc");
    foreach ($modules as &$m) {
        $sub = $subsectionMap[$m['subsection_ref']] ?? null;
        $sec = $sectionByRef[$sub['section_ref'] ?? null] ?? null;
        $m['module']        = $m['name'];
        $m['subsection']    = $sub['name'] ?? '';
        $m['section']       = $sec['name'] ?? '';
        $m['section_icon']  = $sec['web_icon'] ?? null;
        $m['section_color'] = $sec['web_color'] ?? null;
    }
    unset($m);
    return $modules;
}

// Returns [module_ref => ['can_view' => bool, ...]]
function getPermissions(string $roleRef): array {
    $cols = implode(',', RMP_ACTIONS);
    $rows = supabase_get(SB_API . "sys_role_module_permissions?select=module_ref,{$cols}&role_ref=eq." . rawurlencode($roleRef));
    return array_column($rows, null, 'module_ref');
}

function savePermissions(string $roleRef, array $permissionsByModule): bool {
    if (!supabase_delete(SB_API . 'sys_role_module_permissions?role_ref=eq.' . rawurlencode($roleRef))) {
        return false;
    }

    $rows = [];
    foreach ($permissionsByModule as $moduleRef => $perms) {
        $row = ['role_ref' => $roleRef, 'module_ref' => $moduleRef];
        foreach (RMP_ACTIONS as $action) {
            $row[$action] = !empty($perms[$action]);
        }
        $rows[] = $row;
    }

    if (!$rows) return true;

    return (bool) supabase_post(SB_API . 'sys_role_module_permissions', $rows);
}

$action = $_GET['action'] ?? '';

if ($action === 'list_roles') {
    requireModulePermission('can_view');
    echo json_encode(listRoles());

} elseif ($action === 'list_modules') {
    requireModulePermission('can_view');
    echo json_encode(listModules());

} elseif ($action === 'get_permissions') {
    requireModulePermission('can_view');
    $roleRef = $_GET['role_ref'] ?? '';
    echo json_encode($roleRef === '' ? [] : getPermissions($roleRef));

} elseif ($action === 'save') {
    requireModulePermission('can_create');
    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $roleRef     = trim($body['role_ref'] ?? '');
    $permissions = $body['permissions'] ?? [];

    if ($roleRef === '') {
        http_response_code(422);
        echo json_encode(['error' => 'role_ref is required']);
        exit;
    }

    if (!savePermissions($roleRef, $permissions)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save permissions — Supabase write rejected']);
        exit;
    }
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
