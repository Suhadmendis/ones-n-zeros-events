<?php
// modules/finance/audit_trail/audit_trail_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

function listEvents(): array {
    return supabase_get(
        SB_API . 'm_finance_audit_trail'
              . '?select=id,event_time,user_ref,user_name,action_type,module,record_ref,field_changed,old_value,new_value,description,ip_address'
              . '&order=event_time.desc'
              . '&limit=500'
    );
}

function logEvent(array $data): array {
    $u = current_user();
    return supabase_post(SB_API . 'm_finance_audit_trail', [
        'event_time'   => date('c'),
        'user_ref'     => $u['ref']  ?? null,
        'user_name'    => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: null,
        'action_type'  => trim($data['action_type']  ?? ''),
        'module'       => trim($data['module']        ?? ''),
        'record_ref'   => trim($data['record_ref']   ?? '') ?: null,
        'field_changed'=> trim($data['field_changed'] ?? '') ?: null,
        'old_value'    => trim($data['old_value']    ?? '') ?: null,
        'new_value'    => trim($data['new_value']    ?? '') ?: null,
        'description'  => trim($data['description']  ?? '') ?: null,
        'ip_address'   => $_SERVER['REMOTE_ADDR']    ?? null,
    ]);
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listEvents());
} elseif ($action === 'log') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(logEvent($body));
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
