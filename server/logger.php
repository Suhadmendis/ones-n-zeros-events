<?php
// server/logger.php — Fire-and-forget activity log INSERT via Supabase REST

function log_activity(array $data): void {
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) return;

    $u = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? []);

    $payload = array_merge([
        'user_id'       => isset($u['id'])   ? (int)$u['id'] : null,
        'user_ref'      => $u['ref']          ?? null,
        'user_name'     => $u['full_name']    ?? null,
        'ip_address'    => $_SERVER['HTTP_X_FORWARDED_FOR']
                           ?? $_SERVER['REMOTE_ADDR']
                           ?? null,
        'metadata'      => (object)[],
    ], $data);

    $ch = curl_init(SUPABASE_URL . '/rest/v1/sys_user_activity_log');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'apikey: '               . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json',
            'Prefer: return=minimal',
        ],
        CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
}
