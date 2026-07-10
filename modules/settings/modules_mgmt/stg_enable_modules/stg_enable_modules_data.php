<?php
// stg_enable_modules_data.php — Sections settings endpoint

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';

require_login();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $rows = supabase_get(SB_API . 'sys_tms_sections?select=*&order=sort_order.asc');
    echo json_encode($rows);

} elseif ($action === 'toggle') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $ref   = $body['ref']   ?? '';
    $field = $body['field'] ?? '';
    $value = isset($body['value']) ? (int) $body['value'] : null;

    $allowed_fields = ['web_enable', 'app_enable'];
    if ($ref === '' || !in_array($field, $allowed_fields, true) || $value === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }

    $result = supabase_patch(
        SB_API . 'sys_tms_sections?ref=eq.' . urlencode($ref),
        [$field => $value]
    );
    echo json_encode(['success' => true, 'result' => $result]);

} elseif ($action === 'set_style') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $ref   = $body['ref']   ?? '';
    $field = $body['field'] ?? '';
    $value = $body['value'] ?? '';

    $allowed_fields = ['web_icon', 'web_color'];
    $allowed_colors = [
        'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark',
        'indigo', 'purple', 'pink', 'orange', 'teal',
    ];

    if ($ref === '' || !in_array($field, $allowed_fields, true) || $value === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    if ($field === 'web_color' && !in_array($value, $allowed_colors, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid color']);
        exit;
    }
    if ($field === 'web_icon' && !preg_match('/^bi-[a-z0-9-]+$/', $value)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid icon']);
        exit;
    }

    $result = supabase_patch(
        SB_API . 'sys_tms_sections?ref=eq.' . urlencode($ref),
        [$field => $value]
    );
    echo json_encode(['success' => true, 'result' => $result]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
