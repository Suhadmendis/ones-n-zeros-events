<?php
// module_generator_data.php — Generate Module tool endpoint
//
// Thin HTTP routing wrapper around module_generator_lib.php. All generation
// logic lives in the lib so bin/generate_module.php (CLI) can call the exact
// same generateModule() without going through HTTP/session at all.
// require_login() here (unlike most _data.php files) because this endpoint
// writes to disk and inserts DB rows — same justification as
// create_user_data.php / role_management_data.php.

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/module_generator_lib.php';

require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list_sections') {
    echo json_encode(supabase_get(SB_API . 'sys_tms_sections?select=ref,name,folder&order=sort_order.asc'));

} elseif ($action === 'list_subsections') {
    echo json_encode(supabase_get(SB_API . 'sys_tms_subsections?select=ref,name,folder,section_ref&order=sort_order.asc'));

} elseif ($action === 'check_slug') {
    $slug = slugifyModuleName($_GET['folder'] ?? '');
    if (!isValidSlug($slug)) {
        echo json_encode(['available' => false, 'slug' => $slug]);
        exit;
    }
    $existing = supabase_get(SB_API . 'sys_tms_modules?select=id&folder=eq.' . urlencode($slug) . '&limit=1');
    echo json_encode(['available' => empty($existing), 'slug' => $slug]);

} elseif ($action === 'check_table') {
    $table = trim($_GET['table'] ?? '');
    if (!isValidTableName($table)) {
        echo json_encode(['available' => false, 'table' => $table]);
        exit;
    }
    echo json_encode(['available' => !tableExists($table), 'table' => $table]);

} elseif ($action === 'generate') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $result = generateModule($body);

    $status = $result['status'] ?? null;
    unset($result['status']);
    if (isset($result['error'])) {
        http_response_code($status ?? 500);
    }
    echo json_encode($result);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
