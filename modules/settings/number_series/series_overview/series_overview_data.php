<?php
// modules/settings/number_series/series_overview/series_overview_data.php
// Admin screen over the real number-generation engine table
// (sys_tms_module_number_series) — see server/general/number_series.php.
// Rows are provisioned per module via module setup, not created here.

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

function listNumberSeries(): array {
    $series = supabase_get(
        SB_API . 'sys_tms_module_number_series?select=id,ref,module_ref,prefix,suffix,current_number,padding_length,separator,reset_period,record_status&order=id.asc'
    );
    $modules = supabase_get(SB_API . 'sys_tms_modules?select=ref,name,folder');

    $modulesByRef = [];
    foreach ($modules as $m) {
        $modulesByRef[$m['ref']] = $m;
    }

    foreach ($series as &$row) {
        $module = $modulesByRef[$row['module_ref']] ?? null;
        $row['module_name']   = $module['name']   ?? '';
        $row['module_folder'] = $module['folder'] ?? '';
    }

    return $series;
}

function saveNumberSeries(array $data): array {
    $id = (int) ($data['id'] ?? 0);
    if (!$id) {
        throw new RuntimeException('A number series can only be edited, not created — select one via Search first.');
    }

    return supabase_patch(SB_API . 'sys_tms_module_number_series?id=eq.' . $id, [
        'prefix'         => trim($data['prefix'] ?? ''),
        'suffix'         => trim($data['suffix'] ?? ''),
        'padding_length' => (int) ($data['padding_length'] ?? 7),
        'separator'      => trim($data['separator'] ?? '-'),
        'reset_period'   => trim($data['reset_period'] ?? 'never'),
        'updated_at'     => date('c'),
    ]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listNumberSeries());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    try {
        echo json_encode(saveNumberSeries($body));
    } catch (RuntimeException $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'exists') {
    $id = (int) ($_GET['id'] ?? 0);
    echo json_encode(['exists' => $id > 0 && !empty(supabase_get(SB_API . 'sys_tms_module_number_series?select=id&id=eq.' . $id . '&limit=1'))]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
