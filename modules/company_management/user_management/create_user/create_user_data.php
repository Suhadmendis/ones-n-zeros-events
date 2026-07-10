<?php
// create_user_data.php — User module endpoint

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';
require_once __DIR__ . '/../../../../server/general/rbac.php';

header('Content-Type: application/json');

function userRoleRefs(): array {
    $userRoles = supabase_get(SB_API . 'sys_user_roles?select=user_ref,role_ref');
    $map = [];
    foreach ($userRoles as $ur) {
        $map[$ur['user_ref']][] = $ur['role_ref'];
    }
    return $map;
}

function userRoleNames(): array {
    $userRoles = supabase_get(SB_API . 'sys_user_roles?select=user_ref,role_ref');
    if (!$userRoles) return [];
    $roles    = supabase_get(SB_API . 'sys_roles?select=ref,name');
    $roleName = array_column($roles, 'name', 'ref');
    $map = [];
    foreach ($userRoles as $ur) {
        $map[$ur['user_ref']][] = $roleName[$ur['role_ref']] ?? $ur['role_ref'];
    }
    return $map;
}

function saveUser(array $data): array {
    $full_name = trim($data['full_name'] ?? '');
    $email     = trim($data['email']     ?? '');
    $password  = trim($data['password']  ?? '');
    $roleRefs  = array_values(array_filter((array) ($data['role_refs'] ?? [])));

    if ($full_name === '' || $email === '' || $password === '' || !$roleRefs) {
        http_response_code(422);
        echo json_encode(['error' => 'Full name, email, password, and at least one role are required.']);
        exit;
    }

    if (recordExists('sys_users', $email, 'email')) {
        http_response_code(409);
        echo json_encode(['error' => 'A user with this email already exists.']);
        exit;
    }

    $ref = consumeNextReference('create_user');

    $user = supabase_post(SB_API . 'sys_users', [
        'ref'           => $ref,
        'username'      => $email,
        'full_name'     => $full_name,
        'email'         => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'record_status' => $data['record_status'] ?? 'active',
    ]);

    if (empty($user['id'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user record.']);
        exit;
    }

    foreach ($roleRefs as $roleRef) {
        supabase_post(SB_API . 'sys_user_roles', ['user_ref' => $ref, 'role_ref' => $roleRef]);
    }

    return $user;
}

function listUsers(): array {
    $rows      = supabase_get(SB_API . 'sys_users?select=ref,full_name,email,record_status&order=id.asc');
    $roleRefs  = userRoleRefs();
    $roleNames = userRoleNames();
    foreach ($rows as &$r) {
        $r['role_refs'] = $roleRefs[$r['ref']]  ?? [];
        $r['roles']     = implode(', ', $roleNames[$r['ref']] ?? []);
    }
    unset($r);
    // Never return password hashes in list
    return $rows;
}

function listRoles(): array {
    return supabase_get(SB_API . 'sys_roles?select=ref,name&record_status=eq.active&order=name.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    requireModulePermission('can_view');
    echo json_encode(listUsers());
} elseif ($action === 'list_roles') {
    requireModulePermission('can_view');
    echo json_encode(listRoles());
} elseif ($action === 'save') {
    requireModulePermission('can_create');
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveUser($body));
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
