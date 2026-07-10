<?php
// server/general/access_engine.php
// THE ONLY place that resolves *effective* module access — RBAC permission
// grants combined with the section-enable kill-switch — via the
// sys_effective_module_access / sys_user_effective_module_access DB views
// (docs/migrations/access_engine_view.sql). effective_access = the module's
// section is enabled AND the user's role(s) grant the action, ORed across
// every role a user holds.
//
// Stage 1: standalone, self-contained. Does NOT require or call into
// rbac.php, module_visibility.php, or module_system.php, and nothing in
// those files calls into this one yet — see scripts/verify_access_engine.php
// for the standalone correctness proof, and
// /Users/akilamendis/.claude/plans/sharded-giggling-adleman.md for the
// staged cutover plan that will eventually retire those older files.

if (defined('ACCESS_ENGINE_LOADED')) return;
define('ACCESS_ENGINE_LOADED', true);

require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/../session.php';

const ACCESS_ACTIONS = ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve', 'can_export', 'can_import', 'can_print'];

// ---------------------------------------------------------------------------
// Public API
// ---------------------------------------------------------------------------

function access_getUserRoleRefs(string $userRef): array {
    if ($userRef === '') return [];
    $rows = supabase_get(SB_API . 'sys_user_roles?select=role_ref&user_ref=eq.' . rawurlencode($userRef));
    return array_column($rows, 'role_ref');
}

// Centralizes what's currently copy-pasted across create_user_data.php,
// edit_deactivate_user_data.php, password_change_data.php — Stage 3 cleanup
// retires those copies in favor of this one.
function access_getUserRoleNames(string $userRef): array {
    $roleRefs = access_getUserRoleRefs($userRef);
    if (!$roleRefs) return [];
    $refsList = implode(',', array_map('rawurlencode', $roleRefs));
    $roles    = supabase_get(SB_API . "sys_roles?select=ref,name&ref=in.({$refsList})");
    $nameByRef = array_column($roles, 'name', 'ref');
    return array_values(array_filter(array_map(fn($ref) => $nameByRef[$ref] ?? null, $roleRefs)));
}

// Raw effective-access rows for a user, one per module, straight from
// sys_user_effective_module_access — the single query that replaces
// rbac.php's getEffectiveModulePermissions() + module_system.php's
// getEnabledSectionRefs() + the PHP-side join between them.
function access_getEffectiveModules(string $userRef): array {
    if ($userRef === '') return [];
    return supabase_get(SB_API . 'sys_user_effective_module_access?user_ref=eq.' . rawurlencode($userRef) . '&order=sort_order.asc');
}

// Module refs where the user's effective (enabled AND permitted) access
// grants $action. Direct replacement for rbac.php::getPermittedModuleRefs()
// combined with module_system.php's section-enable filtering.
function access_getVisibleModuleRefs(string $userRef, string $action = 'can_view'): array {
    if (!in_array($action, ACCESS_ACTIONS, true)) return [];
    $refs = [];
    foreach (access_getEffectiveModules($userRef) as $row) {
        if (!empty($row['effective_' . $action])) {
            $refs[] = $row['module_ref'];
        }
    }
    return $refs;
}

// Drop-in replacement for module_system.php::getModules($allowedRefs). Same
// row shape as that function returns today (id, ref, name, folder,
// subsection_ref, creates_journal_entry, section, section_ref, section_icon,
// section_color, subsection, subsection_icon, subsection_color, system_name,
// folder_path, has_file, module, reference, flag) so Stage 2 cutovers are
// pure function-swaps, not template rewrites. Only does in PHP what SQL
// genuinely can't: has_file (needs the filesystem), folder_path
// (string-building), and the number-series prefix lookup.
function access_getModules(string $userRef, string $action = 'can_view'): array {
    if (!in_array($action, ACCESS_ACTIONS, true)) return [];

    $rows = supabase_get(
        SB_API . 'sys_user_effective_module_access?user_ref=eq.' . rawurlencode($userRef)
        . '&effective_' . $action . '=eq.true&order=sort_order.asc'
    );
    if (!$rows) return [];

    $moduleRefs      = array_column($rows, 'module_ref');
    $numberSeriesMap = _access_getNumberSeriesMap($moduleRefs);
    $base            = dirname(__DIR__, 2);

    $modules = [];
    foreach ($rows as $row) {
        $sn     = $row['module_folder'];
        $fp     = 'modules/' . trim(($row['section_folder'] ?? '') . '/' . ($row['subsection_folder'] ?? '') . '/' . $sn, '/');
        $series = $numberSeriesMap[$row['module_ref']] ?? null;

        $modules[] = [
            'id'                    => $row['module_id'],
            'ref'                   => $row['module_ref'],
            'name'                  => $row['module_name'],
            'folder'                => $row['module_folder'],
            'subsection_ref'        => $row['subsection_ref'],
            'creates_journal_entry' => $row['creates_journal_entry'],
            'section'               => $row['section_name'] ?? '',
            'section_ref'           => $row['section_ref'],
            'section_icon'          => $row['web_icon'] ?? null,
            'section_color'         => $row['web_color'] ?? null,
            'subsection'            => $row['subsection_name'] ?? '',
            'subsection_icon'       => $row['subsection_icon'] ?? null,
            'subsection_color'      => $row['subsection_color'] ?? null,
            'system_name'           => $sn,
            'folder_path'           => $fp,
            'has_file'              => $sn && file_exists("{$base}/{$fp}/{$sn}.php"),
            'module'                => ucwords(strtolower($row['module_name'] ?? '')),
            'reference'             => $series['prefix'] ?? '',
            'flag'                  => $series ? 1 : 0,
        ];
    }
    return $modules;
}

// Full effective_can_* matrix for one (user, module) pair — one row, all 8
// actions. Used by home.php to inject window.__MODULE_PERMS__ for the page
// currently being loaded, so module UIs can gate New/Edit/Delete/Print
// buttons without fetching every module the user can see.
function access_getEffectiveModule(string $userRef, string $moduleFolder): ?array {
    if ($userRef === '' || $moduleFolder === '') return null;
    $rows = supabase_get(
        SB_API . 'sys_user_effective_module_access?user_ref=eq.' . rawurlencode($userRef)
        . '&module_folder=eq.' . rawurlencode($moduleFolder) . '&limit=1'
    );
    return $rows[0] ?? null;
}

// Single (user, module, action) check, resolved by folder directly against
// the view — no separate ref lookup needed, unlike rbac.php's
// requireModulePermission() which does getModuleRefByFolder() first.
function access_can(string $userRef, string $moduleFolder, string $action): bool {
    if ($userRef === '' || $moduleFolder === '' || !in_array($action, ACCESS_ACTIONS, true)) return false;
    $rows = supabase_get(
        SB_API . 'sys_user_effective_module_access?user_ref=eq.' . rawurlencode($userRef)
        . '&module_folder=eq.' . rawurlencode($moduleFolder)
        . '&select=effective_' . $action . '&limit=1'
    );
    return !empty($rows[0]['effective_' . $action]);
}

// HTTP guard — call from a module's _data.php/_print.php action branch, or
// from home.php before including a module's page shell. $moduleFolder
// defaults to the calling script's own parent folder, which always equals
// sys_tms_modules.folder for a module's own files. This is what Stage 3
// threads into every _data.php/_print.php/home.php, replacing
// rbac.php::requireModulePermission().
function access_requirePermission(string $action, ?string $moduleFolder = null): void {
    require_login();

    $moduleFolder ??= basename(dirname($_SERVER['SCRIPT_FILENAME']));
    $user           = current_user();
    $granted        = access_can($user['ref'] ?? '', $moduleFolder, $action);

    if (!$granted) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'You do not have permission to perform this action.']);
        exit;
    }
}

// Role-level check against the role x module base view directly — for
// testing/administering permissions independent of any real user, e.g. the
// verification script or a future Role Management grid UI.
function access_roleCan(string $roleRef, string $moduleRef, string $action): bool {
    if ($roleRef === '' || $moduleRef === '' || !in_array($action, ACCESS_ACTIONS, true)) return false;
    $rows = supabase_get(
        SB_API . 'sys_effective_module_access?role_ref=eq.' . rawurlencode($roleRef)
        . '&module_ref=eq.' . rawurlencode($moduleRef)
        . '&select=' . $action . ',section_web_enabled&limit=1'
    );
    $row = $rows[0] ?? null;
    return $row !== null && !empty($row['section_web_enabled']) && !empty($row[$action]);
}

// OR/union over an arbitrary caller-supplied role set, independent of any
// actual sys_user_roles row — can't be pre-baked into the user-level view,
// which is keyed to real user-role assignments, not hypothetical sets.
function access_rolesUnionCan(array $roleRefs, string $moduleRef, string $action): bool {
    if (!$roleRefs || $moduleRef === '' || !in_array($action, ACCESS_ACTIONS, true)) return false;
    $refsList = implode(',', array_map('rawurlencode', $roleRefs));
    $rows = supabase_get(
        SB_API . "sys_effective_module_access?role_ref=in.({$refsList})&module_ref=eq." . rawurlencode($moduleRef)
        . '&select=' . $action . ',section_web_enabled'
    );
    foreach ($rows as $row) {
        if (!empty($row['section_web_enabled']) && !empty($row[$action])) {
            return true;
        }
    }
    return false;
}

// Guard-fixed port of module_visibility.php::getSectionVisibility() — the
// eventual drop-in target for that file's JS consumers (Stage 3 cleanup).
function access_getSectionVisibility(): array {
    $rows = supabase_get(SB_API . 'sys_tms_sections?select=folder,web_enable');
    $map  = [];
    foreach ($rows as $row) {
        $map[$row['folder']] = (bool) $row['web_enable'];
    }
    return $map;
}

// ---------------------------------------------------------------------------
// Internal helpers — never call these from outside this file
// ---------------------------------------------------------------------------

function _access_getNumberSeriesMap(array $moduleRefs): array {
    if (!$moduleRefs) return [];
    $refsList = implode(',', array_map('rawurlencode', array_unique($moduleRefs)));
    $rows = supabase_get(SB_API . "sys_tms_module_number_series?select=module_ref,prefix&module_ref=in.({$refsList})");
    return array_column($rows, null, 'module_ref');
}

// When called directly as an endpoint, return the current user's visible
// module list as JSON — consumed by js/global-search.js and js/index.js
// (dashboard tiles), same role module_system.php's endpoint block served.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $user = current_user();
    echo json_encode(access_getModules($user['ref'] ?? '', 'can_view'));
}
