<?php
// server/auth.php — Login POST handler

require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header('Location: /login.php?error=missing');
    exit;
}

$url = SUPABASE_URL . '/rest/v1/sys_users?select=id,ref,username,full_name,email,password_hash,record_status&email=eq.' . urlencode($email) . '&limit=1';
$ch  = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: '               . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Accept: application/json',
]);
$rows = json_decode(curl_exec($ch), true) ?? [];

$user = $rows[0] ?? null;

if (!$user || !password_verify($password, $user['password_hash'])) {
    log_activity([
        'action_type' => 'login_failed',
        'description' => "Failed login attempt for: {$email}",
        'ip_address'  => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
        'metadata'    => ['email' => $email],
    ]);
    header('Location: /login.php?error=invalid');
    exit;
}

if ($user['record_status'] !== 'active') {
    log_activity([
        'action_type' => 'login_failed',
        'description' => "Login blocked — inactive account: {$email}",
        'ip_address'  => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
        'metadata'    => ['email' => $email, 'reason' => 'inactive'],
    ]);
    header('Location: /login.php?error=inactive');
    exit;
}

$userRoles = supabase_get(SB_API . 'sys_user_roles?select=role_ref&user_ref=eq.' . urlencode($user['ref']));
$roleRefs  = array_column($userRoles, 'role_ref');
$roleNames = [];
if ($roleRefs) {
    $roles     = supabase_get(SB_API . 'sys_roles?select=ref,name&ref=in.(' . implode(',', $roleRefs) . ')');
    $roleNames = array_column($roles, 'name');
}

$_SESSION['user'] = [
    'id'        => $user['id'],
    'ref'       => $user['ref'],
    'username'  => $user['username'],
    'full_name' => $user['full_name'],
    'email'     => $user['email'],
    'roles'     => $roleNames,
];

log_activity([
    'action_type' => 'login',
    'user_id'     => (int)$user['id'],
    'user_ref'    => $user['ref'],
    'user_name'   => $user['full_name'],
    'description' => 'User logged in',
    'ip_address'  => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
    'metadata'    => ['email' => $user['email']],
]);

header('Location: /index.php');
exit;
