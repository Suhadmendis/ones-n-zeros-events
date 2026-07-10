<?php
// module_documentation_data.php — Module Documentation editor endpoint

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';

require_login();

header('Content-Type: application/json');

const DOC_FIELDS = [
    'title', 'subtitle', 'purpose', 'business_problem', 'business_impact',
    'when_to_use', 'workflow', 'prerequisites', 'best_practices', 'notes',
];

function nextDocumentationRef(): string {
    $rows = supabase_get(SB_API . 'sys_tms_module_documentation?select=ref&order=ref.desc&limit=1');
    $last = (int) str_replace('DOC-', '', $rows[0]['ref'] ?? 'DOC-000000');
    return 'DOC-' . str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
}

$action = $_GET['action'] ?? '';

if ($action === 'list_sections') {
    echo json_encode(supabase_get(SB_API . 'sys_tms_sections?select=ref,name&order=sort_order.asc'));

} elseif ($action === 'list_subsections') {
    echo json_encode(supabase_get(SB_API . 'sys_tms_subsections?select=ref,name,section_ref&order=sort_order.asc'));

} elseif ($action === 'list_modules') {
    echo json_encode(supabase_get(SB_API . 'sys_tms_modules?select=ref,name,subsection_ref&record_status=eq.active&order=name.asc'));

} elseif ($action === 'get') {
    $moduleRef = $_GET['module_ref'] ?? '';
    if ($moduleRef === '') {
        http_response_code(400);
        echo json_encode(['error' => 'module_ref is required']);
        exit;
    }
    $rows = supabase_get(
        SB_API . 'sys_tms_module_documentation?select=' . implode(',', DOC_FIELDS)
        . '&module_ref=eq.' . urlencode($moduleRef) . '&limit=1'
    );
    echo json_encode($rows[0] ?? new stdClass());

} elseif ($action === 'save') {
    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $moduleRef = $body['module_ref'] ?? '';
    if ($moduleRef === '') {
        http_response_code(400);
        echo json_encode(['error' => 'module_ref is required']);
        exit;
    }

    $payload = ['updated_at' => date('c')];
    foreach (DOC_FIELDS as $field) {
        $payload[$field] = $body[$field] ?? '';
    }

    $existing = supabase_get(
        SB_API . 'sys_tms_module_documentation?select=id&module_ref=eq.' . urlencode($moduleRef) . '&limit=1'
    );

    if (!empty($existing)) {
        $result = supabase_patch(SB_API . 'sys_tms_module_documentation?id=eq.' . $existing[0]['id'], $payload);
    } else {
        $payload['ref']        = nextDocumentationRef();
        $payload['module_ref'] = $moduleRef;
        $result = supabase_post(SB_API . 'sys_tms_module_documentation', $payload);
    }

    echo json_encode(['success' => true, 'result' => $result]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
