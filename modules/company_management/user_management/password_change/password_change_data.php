<?php
// password_change/password_change_data.php

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/general/rbac.php';

header('Content-Type: application/json');

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
    $roleNames = userRoleNames();
    foreach ($rows as &$r) {
        $r['roles'] = implode(', ', $roleNames[$r['ref']] ?? []);
    }
    unset($r);
    return $rows;
}

function updatePassword(array $data): void {
    $ref      = trim($data['ref']          ?? '');
    $password = trim($data['new_password'] ?? '');

    if ($ref === '' || $password === '') {
        http_response_code(422);
        echo json_encode(['error' => 'User ref and new password are required.']);
        exit;
    }

    supabase_patch(
        SB_API . 'sys_users?ref=eq.' . urlencode($ref),
        ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]
    );

    echo json_encode(['success' => true]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    requireModulePermission('can_view');
    echo json_encode(listUsers());
} elseif ($action === 'update') {
    requireModulePermission('can_edit');
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    updatePassword($body);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
