<?php
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/rbac.php';
requireModulePermission('can_view');

header('Content-Type: application/json');

$table     = ($_GET['table'] ?? 'activity') === 'export' ? 'export_records' : 'user_activity_log';
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to']   ?? '');
$user_ref  = trim($_GET['user_ref']  ?? '');
$action    = trim($_GET['action_type'] ?? '');
$module    = trim($_GET['module']    ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 50;
$offset    = ($page - 1) * $limit;

$filters = [];

if ($date_from) $filters[] = 'created_at=gte.' . urlencode($date_from . 'T00:00:00+00:00');
if ($date_to)   $filters[] = 'created_at=lte.' . urlencode($date_to   . 'T23:59:59+00:00');
if ($user_ref)  $filters[] = 'user_ref=ilike.*' . urlencode($user_ref) . '*';

if ($table === 'user_activity_log') {
    if ($action) $filters[] = 'action_type=eq.' . urlencode($action);
    if ($module) $filters[] = 'module=eq.'       . urlencode($module);
}

$qs  = implode('&', $filters);
$url = SUPABASE_URL . SB_API . '' . $table
     . '?select=*&order=created_at.desc&limit=' . $limit . '&offset=' . $offset
     . ($qs ? '&' . $qs : '');

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_HTTPHEADER     => [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json',
        'Prefer: count=exact',
    ],
]);

$response    = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header_text = substr($response, 0, $header_size);
$body        = substr($response, $header_size);
$rows        = json_decode($body, true) ?? [];

// Parse total from Content-Range: 0-49/1234
$total = null;
if (preg_match('/Content-Range:\s*\d+-\d+\/(\d+)/i', $header_text, $m)) {
    $total = (int)$m[1];
}

echo json_encode([
    'rows'  => $rows,
    'total' => $total,
    'page'  => $page,
    'limit' => $limit,
]);
