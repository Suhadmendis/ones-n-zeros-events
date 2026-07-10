<?php
// server/general/module_system.php — TMS module system functions + API endpoint

require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/rbac.php';

function getEnabledSectionRefs(): array {
    $rows = supabase_get(SB_API . 'sys_tms_sections?select=ref&web_enable=eq.1');
    return array_column($rows, 'ref');
}

function getSectionMap(): array {
    $rows = supabase_get(SB_API . 'sys_tms_sections?select=ref,name,folder,web_icon,app_icon,web_color,app_color');
    return array_column($rows, null, 'ref');
}

function getSubsectionMap(): array {
    $rows = supabase_get(SB_API . 'sys_tms_subsections?select=ref,section_ref,name,folder,icon,color');
    return array_column($rows, null, 'ref');
}

function getNumberSeriesMap(): array {
    $rows = supabase_get(SB_API . 'sys_tms_module_number_series?select=module_ref,prefix');
    return array_column($rows, null, 'module_ref');
}

function getModules(?array $allowedRefs = null, bool $ignoreSection = false): array {
    $enabledRefs     = $ignoreSection ? [] : getEnabledSectionRefs();
    $sectionMap      = getSectionMap();
    $subsectionMap   = getSubsectionMap();
    $numberSeriesMap = getNumberSeriesMap();

    $url = SUPABASE_URL . '/rest/v1/sys_tms_modules?select=id,ref,name,folder,subsection_ref,creates_journal_entry&order=sort_order.asc';
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json',
    ]);
    $modules = json_decode(curl_exec($ch), true) ?? [];
    $base    = dirname(__DIR__, 2);

    foreach ($modules as &$m) {
        $sub        = $subsectionMap[$m['subsection_ref']] ?? null;
        $sectionRef = $sub['section_ref'] ?? null;
        $sec        = $sectionMap[$sectionRef] ?? null;

        $m['section']         = $sec['name'] ?? '';
        $m['section_ref']     = $sectionRef;
        $m['section_icon']    = $sec['web_icon'] ?? null;
        $m['section_color']   = $sec['web_color'] ?? null;
        $m['subsection']      = $sub['name'] ?? '';
        $m['subsection_icon'] = $sub['icon'] ?? null;
        $m['subsection_color'] = $sub['color'] ?? null;

        $sn     = $m['folder'];
        $fp     = 'modules/' . trim(($sec['folder'] ?? '') . '/' . ($sub['folder'] ?? '') . '/' . $sn, '/');
        $series = $numberSeriesMap[$m['ref']] ?? null;

        $m['system_name'] = $sn;
        $m['folder_path'] = $fp;
        $m['has_file']    = $sn && file_exists("{$base}/{$fp}/{$sn}.php");
        $m['module']      = ucwords(strtolower($m['name']));
        $m['reference']   = $series['prefix'] ?? '';
        $m['flag']        = $series ? 1 : 0;
    }
    unset($m);

    // Filter to only enabled sections (unless bypassed)
    if (!$ignoreSection) {
        $modules = array_values(array_filter($modules, fn($m) => in_array($m['section_ref'], $enabledRefs)));
    }
    if ($allowedRefs !== null) {
        $modules = array_values(array_filter($modules, fn($m) => in_array($m['ref'], $allowedRefs)));
    }
    return $modules;
}

// When called directly as an endpoint, return JSON
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $user = current_user();
    echo json_encode(getModules(getPermittedModuleRefs($user['ref'] ?? '')));
}
