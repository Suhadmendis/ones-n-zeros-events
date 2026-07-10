<?php
// modules/settings/notifications/stg_financial_alerts/stg_financial_alerts_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

const ALERT_CATEGORY = 'financial';

function buildPayload(array $data): array {
    return [
        'category'        => ALERT_CATEGORY,
        'alert_name'      => trim($data['alert_name']      ?? ''),
        'condition_field' => trim($data['condition_field']  ?? ''),
        'operator'        => trim($data['operator']         ?? ''),
        'threshold_value' => isset($data['threshold_value']) && $data['threshold_value'] !== '' ? (float) $data['threshold_value'] : null,
        'channel'         => trim($data['channel']           ?? ''),
        'severity'        => $data['severity'] ?? 'medium',
        'is_enabled'      => !empty($data['is_enabled']),
        'description'     => trim($data['description']      ?? '') ?: null,
        'status'          => $data['status'] ?? 'active',
    ];
}

function validatePayload(array $p): void {
    if ($p['alert_name']      === '')   { http_response_code(422); echo json_encode(['error' => 'Alert name is required.']);      exit; }
    if ($p['condition_field'] === '')   { http_response_code(422); echo json_encode(['error' => 'Condition field is required.']); exit; }
    if (!in_array($p['operator'], ['>', '>=', '<', '<=', '='], true)) {
        http_response_code(422); echo json_encode(['error' => 'A valid operator is required.']); exit;
    }
    if ($p['threshold_value'] === null) { http_response_code(422); echo json_encode(['error' => 'Threshold value is required.']); exit; }
    if (!in_array($p['channel'], ['email', 'sms', 'whatsapp', 'in_app'], true)) {
        http_response_code(422); echo json_encode(['error' => 'A valid channel is required.']); exit;
    }
}

function saveRecord(array $data): array {
    $payload = buildPayload($data);
    validatePayload($payload);

    $payload['ref']        = consumeNextReference('stg_financial_alerts');
    $payload['created_by'] = current_user()['ref'] ?? null;
    $payload['updated_by'] = current_user()['ref'] ?? null;

    return supabase_post(SB_API . 'm_alert_rules', $payload);
}

function updateRecord(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']); exit; }

    $payload = buildPayload($data);
    validatePayload($payload);

    $payload['updated_by'] = current_user()['ref'] ?? null;
    $payload['updated_at'] = date('c');

    return supabase_patch(SB_API . 'm_alert_rules?ref=eq.' . urlencode($ref) . '&category=eq.' . ALERT_CATEGORY, $payload);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_alert_rules?select=ref,alert_name,condition_field,operator,threshold_value,channel,severity,is_enabled,status&category=eq.' . ALERT_CATEGORY . '&order=id.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('stg_financial_alerts'));
} elseif ($action === 'list') {
    echo json_encode(listRecords());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveRecord($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateRecord($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_alert_rules', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
