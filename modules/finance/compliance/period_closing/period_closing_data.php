<?php
// modules/finance/period_closing/period_closing_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveRecord(array $data): array {
    $period       = trim($data['period']       ?? '');
    $period_name  = trim($data['period_name']  ?? '');
    $closing_date = trim($data['closing_date'] ?? '');

    if ($period       === '') { http_response_code(422); echo json_encode(['error' => 'Period is required.']);       exit; }
    if ($period_name  === '') { http_response_code(422); echo json_encode(['error' => 'Period name is required.']); exit; }
    if ($closing_date === '') { http_response_code(422); echo json_encode(['error' => 'Closing date is required.']); exit; }

    $ref = consumeNextReference('period_closing');

    $record = supabase_post(SB_API . 'm_period_closing', [
        'ref'            => $ref,
        'period'         => $period,
        'period_name'    => $period_name,
        'closing_date'   => $closing_date,
        'total_revenue'  => (float) ($data['total_revenue']  ?? 0),
        'total_expenses' => (float) ($data['total_expenses'] ?? 0),
        'notes'          => trim($data['notes'] ?? '') ?: null,
        'status'         => $data['status'] ?? 'open',
        'created_by'     => current_user()['ref'] ?? null,
        'updated_by'     => current_user()['ref'] ?? null,
    ]);

    return $record;
}

function updateRecord(array $data): array {
    $ref          = trim($data['ref']          ?? '');
    $period       = trim($data['period']       ?? '');
    $period_name  = trim($data['period_name']  ?? '');
    $closing_date = trim($data['closing_date'] ?? '');

    if ($ref          === '') { http_response_code(422); echo json_encode(['error' => 'Reference is required.']);    exit; }
    if ($period       === '') { http_response_code(422); echo json_encode(['error' => 'Period is required.']);       exit; }
    if ($period_name  === '') { http_response_code(422); echo json_encode(['error' => 'Period name is required.']); exit; }
    if ($closing_date === '') { http_response_code(422); echo json_encode(['error' => 'Closing date is required.']); exit; }

    return supabase_patch(SB_API . 'm_period_closing?ref=eq.' . urlencode($ref), [
        'period'         => $period,
        'period_name'    => $period_name,
        'closing_date'   => $closing_date,
        'total_revenue'  => (float) ($data['total_revenue']  ?? 0),
        'total_expenses' => (float) ($data['total_expenses'] ?? 0),
        'notes'          => trim($data['notes'] ?? '') ?: null,
        'status'         => $data['status'] ?? 'open',
        'updated_by'     => current_user()['ref'] ?? null,
        'updated_at'     => date('c'),
    ]);
}

function listRecords(): array {
    return supabase_get(SB_API . 'm_period_closing?select=ref,period,period_name,closing_date,total_revenue,total_expenses,net_profit,status&order=period.desc');
}

$action = $_GET['action'] ?? '';

if ($action === 'next_ref') {
    echo json_encode(previewNextReference('period_closing'));
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
    echo json_encode(['exists' => $ref !== '' && recordExists('m_period_closing', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
