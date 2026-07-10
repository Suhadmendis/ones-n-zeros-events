<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

define('SB_API', '/rest/v1/');

function supabase_get(string $path): array {
    $ch = curl_init(SUPABASE_URL . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json',
    ]);
    return json_decode(curl_exec($ch), true) ?? [];
}

// Tables written as a side effect of any request (logging, etc.) — never
// gated by module write-permission, since they aren't business-module data.
const SB_WRITE_GUARD_EXEMPT_TABLES = ['sys_user_activity_log'];

// Tier-1 coarse write guard (see docs/migrations/rbac_migration.sql /
// server/general/rbac.php for the full RBAC picture). Blocks a write if the
// current user has zero write-type permission on the module whose _data.php
// initiated the request. This is deliberately coarse — it can't distinguish
// *which* action (create/edit/delete/...) is being attempted, only that the
// user has none of them. Precise per-action checks are rolled out separately
// via requireModulePermission() in server/general/rbac.php.
//
// Fails open (allows the write) when the module can't be resolved at all —
// e.g. CLI scripts, or requests not routed through a registered module's own
// _data.php — since this guard exists to catch business-data writes from
// unauthorized users, not to gate every possible write path in the app.
function guardModuleWrite(string $path): void {
    if (empty($_SERVER['SCRIPT_FILENAME'])) return;

    if (preg_match('#^/rest/v1/([a-zA-Z0-9_]+)#', $path, $m)) {
        if (in_array($m[1], SB_WRITE_GUARD_EXEMPT_TABLES, true)) return;
    }

    $moduleFolder = basename(dirname($_SERVER['SCRIPT_FILENAME']));
    $modules      = supabase_get(SB_API . 'sys_tms_modules?select=ref&folder=eq.' . urlencode($moduleFolder) . '&limit=1');
    $moduleRef    = $modules[0]['ref'] ?? null;
    if (!$moduleRef) return;

    $user    = current_user();
    $userRef = $user['ref'] ?? '';
    if ($userRef === '') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated.']);
        exit;
    }

    $roleRows = supabase_get(SB_API . 'sys_user_roles?select=role_ref&user_ref=eq.' . urlencode($userRef));
    $roleRefs = array_column($roleRows, 'role_ref');

    $hasWriteAccess = false;
    if ($roleRefs) {
        $refsList = implode(',', $roleRefs);
        $perms    = supabase_get(SB_API . "sys_role_module_permissions?select=can_create,can_edit,can_delete,can_approve,can_import&role_ref=in.({$refsList})&module_ref=eq." . urlencode($moduleRef));
        foreach ($perms as $p) {
            if (!empty($p['can_create']) || !empty($p['can_edit']) || !empty($p['can_delete']) || !empty($p['can_approve']) || !empty($p['can_import'])) {
                $hasWriteAccess = true;
                break;
            }
        }
    }

    if (!$hasWriteAccess) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'You do not have permission to modify this module.']);
        exit;
    }
}

function supabase_post(string $path, array $body): array {
    guardModuleWrite($path);
    $ch = curl_init(SUPABASE_URL . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
        'Prefer: return=representation',
    ]);
    $rows = json_decode(curl_exec($ch), true);
    return $rows[0] ?? [];
}

function supabase_patch(string $path, array $body): array {
    guardModuleWrite($path);
    $ch = curl_init(SUPABASE_URL . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
        'Prefer: return=representation',
    ]);
    $rows = json_decode(curl_exec($ch), true);
    return $rows[0] ?? [];
}

function supabase_delete(string $path): bool {
    guardModuleWrite($path);
    $ch = curl_init(SUPABASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_HTTPHEADER     => [
            'apikey: '               . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json',
        ],
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return $code >= 200 && $code < 300;
}

function supabase_get_count(string $path): int {
    $sep = strpos($path, '?') !== false ? '&' : '?';
    $ch  = curl_init(SUPABASE_URL . $path . $sep . 'limit=1&offset=0');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: '               . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Accept: application/json',
            'Prefer: count=exact',
        ],
    ]);
    $response    = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers     = substr($response, 0, $header_size);
    if (preg_match('/content-range:\s*\d+-?\d*\/(\d+)/i', $headers, $m)) {
        return (int) $m[1];
    }
    return 0;
}

function recordExists(string $table, string $value, string $column = 'ref'): bool {
    $rows = supabase_get(SB_API . $table . '?select=id&' . $column . '=eq.' . urlencode($value) . '&limit=1');
    return !empty($rows);
}

function get_module_prefix(string $system_name): string {
    $modules   = supabase_get(SB_API . 'sys_tms_modules?folder=eq.' . urlencode($system_name) . '&select=ref&limit=1');
    $moduleRef = $modules[0]['ref'] ?? null;
    if ($moduleRef) {
        $series = supabase_get(SB_API . 'sys_tms_module_number_series?module_ref=eq.' . urlencode($moduleRef) . '&select=prefix&limit=1');
        if (!empty($series[0]['prefix'])) {
            return $series[0]['prefix'];
        }
    }
    return strtoupper(substr($system_name, 0, 3));
}
