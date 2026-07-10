<?php
// module_visibility.php — Returns a map of section_key => enabled(bool) for all ERP sections.
// Used by module JS to gate accounting components (Check GL, JE Preview) when Finance is disabled.

require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/../session.php';

function getSectionVisibility(): array {
    $rows = supabase_get(SB_API . 'sys_tms_sections?select=folder,web_enable');
    $map  = [];
    foreach ($rows as $row) {
        $map[$row['folder']] = (bool) $row['web_enable'];
    }
    return $map;
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    echo json_encode(getSectionVisibility());
}
