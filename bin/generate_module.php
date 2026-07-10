#!/usr/bin/env php
<?php
// bin/generate_module.php — Generate Module tool, CLI entry point.
//
// Calls the exact same generateModule() the browser UI and HTTP endpoint use
// (modules/settings/modules_mgmt/module_generator/module_generator_lib.php),
// but in-process — no dev server, no session cookie, no curl. Supersedes the
// curl+PHPSESSID workflow MODULE_GUIDE.md used to document for scripted use.
//
// Usage:
//   php bin/generate_module.php module.json
//   php bin/generate_module.php module.json --actor=USR-0000001
//   cat module.json | php bin/generate_module.php -
//
// This script sets $_SESSION['user'] directly to attribute created_by/
// updated_by/posting-rule rows to a real user — safe only because this is a
// trusted local/CI shell tool, never web-exposed (module_generator_data.php
// is the web-facing endpoint and still goes through require_login()).

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../server/supabase.php';
require_once __DIR__ . '/../server/session.php';
require_once __DIR__ . '/../modules/settings/modules_mgmt/module_generator/module_generator_lib.php';

function cli_fail(string $message): never {
    fwrite(STDERR, "Error: {$message}\n");
    exit(1);
}

$args   = array_slice($argv, 1);
$source = null;
$actorRef = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--actor=')) {
        $actorRef = substr($arg, strlen('--actor='));
    } elseif ($source === null) {
        $source = $arg;
    }
}

if ($source === null) {
    cli_fail("Usage: php bin/generate_module.php <module.json|-> [--actor=USR-0000001]");
}

$json = $source === '-' ? stream_get_contents(STDIN) : file_get_contents($source);
if ($json === false) {
    cli_fail("Could not read input from '{$source}'.");
}

$body = json_decode($json, true);
if (!is_array($body)) {
    cli_fail('Input is not valid JSON.');
}

// Resolve the acting user, attributed on created_by/updated_by and seeded
// posting-rule rows. --actor takes an explicit ref; otherwise fall back to
// the first active user holding the Admin role (same lookup MODULE_GUIDE.md
// previously documented doing by hand via psql for the curl workflow).
if ($actorRef !== null) {
    $user = supabase_get(SB_API . 'sys_users?select=ref,username,full_name&ref=eq.' . urlencode($actorRef) . '&limit=1')[0] ?? null;
    if (!$user) {
        cli_fail("No user found with ref '{$actorRef}'.");
    }
} else {
    $adminRole = supabase_get(SB_API . 'sys_roles?select=ref&name=eq.Admin&limit=1')[0] ?? null;
    if (!$adminRole) {
        cli_fail("No 'Admin' role found — pass --actor=<user_ref> explicitly.");
    }
    $adminUserRoles = supabase_get(SB_API . 'sys_user_roles?select=user_ref&role_ref=eq.' . urlencode($adminRole['ref']));
    $user = null;
    foreach ($adminUserRoles as $ur) {
        $candidate = supabase_get(SB_API . 'sys_users?select=ref,username,full_name&ref=eq.' . urlencode($ur['user_ref']) . '&record_status=eq.active&limit=1')[0] ?? null;
        if ($candidate) { $user = $candidate; break; }
    }
    if (!$user) {
        cli_fail("No active Admin user found — pass --actor=<user_ref> explicitly.");
    }
}

$_SESSION['user'] = [
    'ref'       => $user['ref'],
    'username'  => $user['username'],
    'full_name' => $user['full_name'],
    'roles'     => ['Admin'],
];

$result = generateModule($body);

if (isset($result['error'])) {
    fwrite(STDERR, "Error: {$result['error']}\n");
    exit(1);
}

fwrite(STDOUT, "Module generated: {$result['ref']} — {$result['name']}\n");
fwrite(STDOUT, "  Type:       {$result['module_type']}\n");
fwrite(STDOUT, "  Folder:     {$result['folder']}\n");
fwrite(STDOUT, "  Section:    {$result['section']} / {$result['subsection']}\n");
if ($result['table'])      fwrite(STDOUT, "  Table:      {$result['table']}\n");
if ($result['line_table']) fwrite(STDOUT, "  Line table: {$result['line_table']}\n");
if ($result['ref_prefix']) fwrite(STDOUT, "  Ref prefix: {$result['ref_prefix']}\n");
fwrite(STDOUT, "  URL:        {$result['url']}\n");
foreach ($result['warnings'] as $w) {
    fwrite(STDOUT, "  Warning: {$w}\n");
}

exit(0);
