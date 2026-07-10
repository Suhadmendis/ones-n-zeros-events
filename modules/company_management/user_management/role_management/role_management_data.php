<?php
// role_management_data.php — Role CRUD endpoint (create/rename/deactivate roles)
// Module-level permissions per role live in the separate user_permissions grid.

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/general/rbac.php';

header('Content-Type: application/json');

function listRoles(): array {
    return supabase_get(SB_API . 'sys_roles?select=ref,name,description,record_status&order=name.asc');
}

function saveRole(array $data): array {
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Role name is required.']);
        exit;
    }

    return supabase_post(SB_API . 'sys_roles', [
        'name'          => $name,
        'description'   => trim($data['description'] ?? ''),
        'record_status' => $data['record_status'] ?? 'active',
    ]);
}

function updateRole(array $data): void {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Role ref is required.']);
        exit;
    }

    $allowed = ['name', 'description', 'record_status'];
    $payload = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $payload[$field] = trim($data[$field]);
        }
    }

    if (empty($payload)) {
        http_response_code(422);
        echo json_encode(['error' => 'No fields to update.']);
        exit;
    }

    supabase_patch(SB_API . 'sys_roles?ref=eq.' . urlencode($ref), $payload);
    echo json_encode(['success' => true]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    requireModulePermission('can_view');
    echo json_encode(listRoles());
} elseif ($action === 'save') {
    requireModulePermission('can_create');
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveRole($body));
} elseif ($action === 'update') {
    requireModulePermission('can_edit');
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    updateRole($body);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
