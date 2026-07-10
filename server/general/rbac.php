<?php
// server/general/rbac.php — Role-based module permission helpers
//
// A user can hold multiple roles (sys_user_roles); effective permission for
// any action is the union (OR) of all their roles' sys_role_module_permissions
// rows. Nothing here is cached across requests — a role change must take
// effect on the user's very next request, not just after re-login.

require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/../session.php';

const RBAC_ACTIONS = ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve', 'can_export', 'can_import', 'can_print'];

function getUserRoleRefs(string $userRef): array {
    if ($userRef === '') return [];
    $rows = supabase_get(SB_API . 'sys_user_roles?select=role_ref&user_ref=eq.' . rawurlencode($userRef));
    return array_column($rows, 'role_ref');
}

// Returns [module_ref => ['can_view' => bool, 'can_create' => bool, ...]]
function getEffectiveModulePermissions(string $userRef): array {
    $roleRefs = getUserRoleRefs($userRef);
    if (!$roleRefs) return [];

    $refsList = implode(',', $roleRefs);
    $cols     = implode(',', RBAC_ACTIONS);
    $rows     = supabase_get(SB_API . "sys_role_module_permissions?select=module_ref,{$cols}&role_ref=in.({$refsList})");

    $matrix = [];
    foreach ($rows as $row) {
        $ref = $row['module_ref'];
        if (!isset($matrix[$ref])) {
            $matrix[$ref] = array_fill_keys(RBAC_ACTIONS, false);
        }
        foreach (RBAC_ACTIONS as $action) {
            if (!empty($row[$action])) {
                $matrix[$ref][$action] = true;
            }
        }
    }
    return $matrix;
}

function getPermittedModuleRefs(string $userRef, string $action = 'can_view'): array {
    $matrix = getEffectiveModulePermissions($userRef);
    $refs   = [];
    foreach ($matrix as $moduleRef => $perms) {
        if (!empty($perms[$action])) {
            $refs[] = $moduleRef;
        }
    }
    return $refs;
}

function getModuleRefByFolder(string $folder): ?string {
    $rows = supabase_get(SB_API . 'sys_tms_modules?select=ref&folder=eq.' . rawurlencode($folder) . '&limit=1');
    return $rows[0]['ref'] ?? null;
}

// Call from a module's _data.php/_print.php action branch. $moduleFolder
// defaults to the calling script's own parent folder, which always equals
// sys_tms_modules.folder for a module's own files.
function requireModulePermission(string $action, ?string $moduleFolder = null): void {
    require_login();

    $moduleFolder ??= basename(dirname($_SERVER['SCRIPT_FILENAME']));
    $moduleRef      = getModuleRefByFolder($moduleFolder);
    $user           = current_user();
    $matrix         = $moduleRef ? getEffectiveModulePermissions($user['ref'] ?? '') : [];
    $granted        = $moduleRef && !empty($matrix[$moduleRef][$action]);

    if (!$granted) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'You do not have permission to perform this action.']);
        exit;
    }
}
