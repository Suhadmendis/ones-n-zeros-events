<?php
// server/general/module_help.php — resolves a module's Help modal content by system_name

require_once __DIR__ . '/../supabase.php';

function fetchModuleHelp(string $systemName): array {
    $modules = supabase_get(SB_API . 'sys_tms_modules?select=ref&folder=eq.' . urlencode($systemName) . '&limit=1');
    $moduleRef = $modules[0]['ref'] ?? null;
    if (!$moduleRef) {
        return ['found' => false];
    }

    $rows = supabase_get(
        SB_API . 'sys_tms_module_documentation?select=title,subtitle,purpose,business_problem,business_impact,when_to_use,workflow,prerequisites,best_practices,notes&module_ref=eq.'
        . urlencode($moduleRef) . '&limit=1'
    );

    if (empty($rows)) {
        return ['found' => false];
    }

    return ['found' => true] + $rows[0];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $systemName = $_GET['system_name'] ?? '';
    echo json_encode(fetchModuleHelp($systemName));
}
