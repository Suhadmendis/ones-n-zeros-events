<?php
// scripts/regenerate_modules_md.php
// Regenerates MODULES.md from the live sys_tms_modules hierarchy.
// Mirrors the same queries/join as MODULES.php (root dev page).
// Re-run any time the module registry changes: php scripts/regenerate_modules_md.php

require_once __DIR__ . '/../server/supabase.php';

$modules      = supabase_get(SB_API . 'sys_tms_modules?select=*&order=sort_order.asc');
$sections     = supabase_get(SB_API . 'sys_tms_sections?select=ref,name,web_color');
$subsections  = supabase_get(SB_API . 'sys_tms_subsections?select=ref,section_ref,name,color');
$numberSeries = supabase_get(SB_API . 'sys_tms_module_number_series?select=module_ref,prefix');

$sectionMap      = array_column($sections, null, 'ref');
$subsectionMap   = array_column($subsections, null, 'ref');
$numberSeriesMap = array_column($numberSeries, null, 'module_ref');

foreach ($modules as &$m) {
    $sub    = $subsectionMap[$m['subsection_ref']] ?? null;
    $sec    = $sectionMap[$sub['section_ref'] ?? null] ?? null;
    $series = $numberSeriesMap[$m['ref']] ?? null;

    $m['section_name']    = $sec['name'] ?? '';
    $m['subsection_name'] = $sub['name'] ?? '';
    $m['prefix']          = $series['prefix'] ?? '';
    $m['flag']            = $series ? 1 : 0;
}
unset($m);

$lines   = [];
$lines[] = '# sys_tms_modules';
$lines[] = '';
$lines[] = 'Source: Supabase project `oncenzeros-transport` — tables `sys_tms_modules`, `sys_tms_sections`, `sys_tms_subsections`, `sys_tms_module_number_series`';
$lines[] = 'Regenerated: ' . date('Y-m-d H:i:s') . ' via `php scripts/regenerate_modules_md.php`';
$lines[] = '';
$lines[] = '| id | section | subsection | module | ref | folder | prefix | flag | web_status | app_status |';
$lines[] = '|----|---------|------------|--------|-----|--------|--------|------|------------|------------|';

foreach ($modules as $m) {
    $lines[] = sprintf(
        '| %d | %s | %s | %s | %s | %s | %s | %d | %s | %s |',
        (int) $m['id'],
        $m['section_name'] ?: '—',
        $m['subsection_name'] ?: '—',
        $m['name'],
        $m['ref'],
        $m['folder'],
        $m['prefix'] ?: '—',
        (int) $m['flag'],
        $m['web_status'] ? 1 : '—',
        $m['app_status'] ? 1 : '—'
    );
}

$outPath = __DIR__ . '/../MODULES.md';
file_put_contents($outPath, implode("\n", $lines) . "\n");

echo "Wrote " . count($modules) . " module(s) to " . realpath($outPath) . "\n";
