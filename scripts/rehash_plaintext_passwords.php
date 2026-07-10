<?php
// scripts/rehash_plaintext_passwords.php
// One-off: bcrypt-hash any sys_users.password_hash value that isn't already
// a bcrypt hash. Run once after docs/migrations/rbac_migration.sql, before
// cutting server/auth.php over to sys_users. Delete after running.

require_once __DIR__ . '/../server/supabase.php';

$users = supabase_get(SB_API . 'sys_users?select=ref,username,password_hash');

$rehashed = 0;
foreach ($users as $u) {
    if (preg_match('/^\$2[aby]\$/', $u['password_hash'])) {
        continue; // already bcrypt
    }
    $newHash = password_hash($u['password_hash'], PASSWORD_DEFAULT);
    supabase_patch(SB_API . 'sys_users?ref=eq.' . urlencode($u['ref']), ['password_hash' => $newHash]);
    echo "Rehashed {$u['ref']} ({$u['username']})\n";
    $rehashed++;
}

echo "Done. {$rehashed} password(s) rehashed out of " . count($users) . " user(s).\n";
