<?php
// edit_deactivate_user/edit_deactivate_user_data.php

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';
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

function listUsers(): array {
    $rows      = supabase_get(SB_API . 'sys_users?select=id,ref,full_name,email,record_status&order=id.asc');
    $roleRefs  = userRoleRefs();
    $roleNames = userRoleNames();
    foreach ($rows as &$r) {
        $r['role_refs'] = $roleRefs[$r['ref']]  ?? [];
        $r['roles']     = implode(', ', $roleNames[$r['ref']] ?? []);
    }
    unset($r);
    return $rows;
}

function listRoles(): array {
    return supabase_get(SB_API . 'sys_roles?select=ref,name&record_status=eq.active&order=name.asc');
}

function updateUser(array $data): void {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '') {
        http_response_code(422);
        echo json_encode(['error' => 'User ref is required.']);
        exit;
    }

    $allowed = ['full_name', 'email', 'record_status'];
    $payload = [];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $payload[$field] = trim($data[$field]);
        }
    }

    if (!empty($payload)) {
        supabase_patch(SB_API . 'sys_users?ref=eq.' . urlencode($ref), $payload);
    }

    if (isset($data['role_refs'])) {
        supabase_delete(SB_API . 'sys_user_roles?user_ref=eq.' . urlencode($ref));
        foreach (array_filter((array) $data['role_refs']) as $roleRef) {
            supabase_post(SB_API . 'sys_user_roles', ['user_ref' => $ref, 'role_ref' => $roleRef]);
        }
    }

    echo json_encode(['success' => true]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    requireModulePermission('can_view');
    echo json_encode(listUsers());
} elseif ($action === 'list_roles') {
    requireModulePermission('can_view');
    echo json_encode(listRoles());
} elseif ($action === 'update') {
    requireModulePermission('can_edit');
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    updateUser($body);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
