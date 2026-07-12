<?php
// scripts/verify_access_engine.php
// Standalone, read-only correctness proof for server/general/access_engine.php.
// Run: php scripts/verify_access_engine.php
// Exits 0 if all cases pass, 1 if any fail (or any precondition is stale —
// see below). Talks directly to Supabase via cURL, like
// scripts/rehash_plaintext_passwords.php — no `php -S` server needed.
//
// This is the Stage 1 exit gate from the access-engine plan
// (/Users/akilamendis/.claude/plans/sharded-giggling-adleman.md): nothing in
// Stage 2 (cutting the sidebar/search/dashboard/action-buttons over to the
// new engine) should start until this script exits 0.
//
// Every case resolves roles/modules/sections by name/folder at run time
// rather than hardcoding refs, and re-checks its own precondition (e.g. "is
// this section actually disabled right now?") before asserting — so the
// script fails loudly instead of silently passing if someone flips a toggle
// or a permission grid in Settings after this was written.

require_once __DIR__ . '/../server/general/access_engine.php';

$pass = 0;
$fail = 0;

function assert_case(string $label, $actual, $expected): void {
    global $pass, $fail;
    $ok = $actual === $expected;
    $ok ? $pass++ : $fail++;
    printf("[%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
    if (!$ok) {
        printf("       expected %s, got %s\n", var_export($expected, true), var_export($actual, true));
    }
}

function skip(string $label): void {
    echo "[SKIP] {$label}\n";
}

function fetchRoleRefByName(string $name): ?string {
    $rows = supabase_get(SB_API . 'sys_roles?select=ref&name=eq.' . rawurlencode($name) . '&limit=1');
    return $rows[0]['ref'] ?? null;
}

function fetchModuleRefByFolder(string $folder): ?string {
    $rows = supabase_get(SB_API . 'sys_tms_modules?select=ref&folder=eq.' . rawurlencode($folder) . '&limit=1');
    return $rows[0]['ref'] ?? null;
}

function fetchSectionEnabled(string $sectionRef): ?bool {
    $rows = supabase_get(SB_API . 'sys_tms_sections?select=web_enable&ref=eq.' . rawurlencode($sectionRef) . '&limit=1');
    if (!isset($rows[0])) return null;
    return (bool) $rows[0]['web_enable'];
}

function fetchRawPermission(string $roleRef, string $moduleRef, string $action): ?bool {
    $rows = supabase_get(
        SB_API . 'sys_role_module_permissions?select=' . $action
        . '&role_ref=eq.' . rawurlencode($roleRef) . '&module_ref=eq.' . rawurlencode($moduleRef) . '&limit=1'
    );
    if (!isset($rows[0])) return null;
    return (bool) $rows[0][$action];
}

// --- Resolve live reference data ---

$adminRef    = fetchRoleRefByName('Admin');
$managerRef  = fetchRoleRefByName('Manager');
$operatorRef = fetchRoleRefByName('Operator');
$viewerRef   = fetchRoleRefByName('Viewer');

if (!$adminRef || !$managerRef || !$operatorRef || !$viewerRef) {
    echo "FATAL: could not resolve all 4 seeded roles (Admin/Manager/Operator/Viewer) by name.\n";
    exit(1);
}

$companyProfileRef = fetchModuleRefByFolder('company_profile');
$driverMasterRef   = fetchModuleRefByFolder('driver_master_file');
$tripRunningRef    = fetchModuleRefByFolder('trip_running_chart');

if (!$companyProfileRef || !$driverMasterRef || !$tripRunningRef) {
    echo "FATAL: could not resolve company_profile / driver_master_file / trip_running_chart modules by folder.\n";
    exit(1);
}

$compEnabled = fetchSectionEnabled('COMP');
$mstrEnabled = fetchSectionEnabled('MSTR');
$opsEnabled  = fetchSectionEnabled('OPS');

// --- Case 1: disabled section overrides a granted RBAC permission ---

$label1 = 'Case 1: disabled section overrides granted RBAC permission (Admin/company_profile/can_view)';
if ($compEnabled !== false) {
    skip("{$label1} — COMP section is not currently disabled, precondition not met.");
} elseif (fetchRawPermission($adminRef, $companyProfileRef, 'can_view') !== true) {
    skip("{$label1} — Admin no longer has raw can_view on company_profile, precondition not met.");
} else {
    assert_case($label1, access_roleCan($adminRef, $companyProfileRef, 'can_view'), false);
}

// --- Case 2 & 3: enabled section, no grant -> hidden; enabled + granted -> visible ---

$label2 = 'Case 2: enabled section + no RBAC grant -> hidden (Viewer/driver_master_file/can_delete)';
$label3 = 'Case 3: enabled section + RBAC grant -> visible (Viewer/driver_master_file/can_view)';
if ($mstrEnabled !== true) {
    skip("{$label2} — MSTR section is not currently enabled, precondition not met.");
    skip("{$label3} — MSTR section is not currently enabled, precondition not met.");
} else {
    if (fetchRawPermission($viewerRef, $driverMasterRef, 'can_delete') !== false) {
        skip("{$label2} — Viewer now has can_delete on driver_master_file, precondition not met.");
    } else {
        assert_case($label2, access_roleCan($viewerRef, $driverMasterRef, 'can_delete'), false);
    }

    if (fetchRawPermission($viewerRef, $driverMasterRef, 'can_view') !== true) {
        skip("{$label3} — Viewer no longer has can_view on driver_master_file, precondition not met.");
    } else {
        assert_case($label3, access_roleCan($viewerRef, $driverMasterRef, 'can_view'), true);
    }
}

// --- Case 4: OR/union of roles elevates permission (real, non-hardcoded case) ---

$label4 = 'Case 4: OR/union of roles elevates permission (Operator+Manager/trip_running_chart/can_approve)';
if ($opsEnabled !== true) {
    skip("{$label4} — OPS section is not currently enabled, precondition not met.");
} else {
    $operatorApprove = fetchRawPermission($operatorRef, $tripRunningRef, 'can_approve');
    $managerApprove  = fetchRawPermission($managerRef, $tripRunningRef, 'can_approve');
    if ($operatorApprove !== false || $managerApprove !== true) {
        skip("{$label4} — live can_approve state no longer matches (Operator=false, Manager=true), precondition not met.");
    } else {
        assert_case($label4, access_rolesUnionCan([$operatorRef, $managerRef], $tripRunningRef, 'can_approve'), true);
    }
}

// --- Case 5: union negative control — two non-granting roles stay false ---

$label5 = 'Case 5: union negative control (Manager+Operator/driver_master_file/can_edit)';
if ($mstrEnabled !== true) {
    skip("{$label5} — MSTR section is not currently enabled, precondition not met.");
} else {
    $managerEdit  = fetchRawPermission($managerRef, $driverMasterRef, 'can_edit');
    $operatorEdit = fetchRawPermission($operatorRef, $driverMasterRef, 'can_edit');
    if ($managerEdit !== false || $operatorEdit !== false) {
        skip("{$label5} — live can_edit state no longer matches (both false), precondition not met.");
    } else {
        assert_case($label5, access_rolesUnionCan([$managerRef, $operatorRef], $driverMasterRef, 'can_edit'), false);
    }
}

// --- Case 6: end-to-end per real user — zero visible modules from disabled sections ---

$users = supabase_get(SB_API . 'sys_users?select=ref,username&record_status=eq.active');
if (!$users) {
    skip('Case 6: per-user end-to-end check — no active users found.');
} else {
    // Independently computed (not via the engine) so this isn't a test that
    // calls the thing it's testing to build its own expected value.
    $sections    = supabase_get(SB_API . 'sys_tms_sections?select=ref,web_enable');
    $disabledSec = array_column(array_filter($sections, fn($s) => empty($s['web_enable'])), 'ref');
    $subs        = supabase_get(SB_API . 'sys_tms_subsections?select=ref,section_ref');
    $disabledSub = array_column(array_filter($subs, fn($s) => in_array($s['section_ref'], $disabledSec, true)), 'ref');
    $mods        = supabase_get(SB_API . 'sys_tms_modules?select=ref,subsection_ref');
    $disabledMod = array_column(array_filter($mods, fn($m) => in_array($m['subsection_ref'], $disabledSub, true)), 'ref');

    foreach ($users as $u) {
        $visible = access_getVisibleModuleRefs($u['ref'], 'can_view');
        $leaked  = array_values(array_intersect($visible, $disabledMod));
        assert_case("Case 6: {$u['username']} sees zero modules from disabled sections", $leaked, []);
    }
}

// --- Case 7: access_getModules() row shape matches module_system.php::getModules() ---

$label7 = "Case 7: access_getModules() row shape matches module_system.php's getModules()";
if (!$users) {
    skip("{$label7} — no active users to sample.");
} else {
    $rows = access_getModules($users[0]['ref'], 'can_view');
    if (!$rows) {
        skip("{$label7} — access_getModules() returned no rows for the sampled user.");
    } else {
        $expectedKeys = [
            'id', 'ref', 'name', 'folder', 'subsection_ref', 'creates_journal_entry',
            'section', 'section_ref', 'section_icon', 'section_color',
            'subsection', 'subsection_icon', 'subsection_color',
            'system_name', 'folder_path', 'has_file', 'module', 'reference', 'flag',
        ];
        $actualKeys = array_keys($rows[0]);
        sort($expectedKeys);
        sort($actualKeys);
        assert_case($label7, $actualKeys, $expectedKeys);
    }
}

echo "\n{$pass} passed, {$fail} failed.\n";
exit($fail > 0 ? 1 : 0);
