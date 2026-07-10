<?php
// modules/finance/accounting/journal_entries/journal_entries_data.php

require_once __DIR__ . '/../../../../server/accounting/journal_engine.php';

header('Content-Type: application/json');

function listEntries(): array {
    return supabase_get(SB_API . 'm_journal_entries?select=ref,journal_date,period,description,reference_doc,source_type,source_ref,status,total_debit,total_credit&order=journal_date.desc,id.desc');
}

function getEntryWithLines(string $ref): array {
    $headers = supabase_get(SB_API . 'm_journal_entries?select=ref,journal_date,period,description,reference_doc,source_type,source_ref,status,total_debit,total_credit,drafted_by,drafted_at,posted_by,posted_at&ref=eq.' . urlencode($ref) . '&limit=1');
    if (empty($headers)) return [];
    $entry = $headers[0];
    $lines = supabase_get(SB_API . 'm_journal_entry_lines?select=line_no,account_code,description,debit_amount,credit_amount&journal_ref=eq.' . urlencode($ref) . '&order=line_no.asc');

    $codes = array_values(array_unique(array_filter(array_column($lines, 'account_code'))));
    if ($codes) {
        $coa     = supabase_get(SB_API . 'm_chart_of_accounts?select=account_code,account_name&account_code=in.(' . implode(',', $codes) . ')');
        $nameMap = array_column($coa, 'account_name', 'account_code');
        foreach ($lines as &$line) {
            $line['account_name'] = $nameMap[$line['account_code']] ?? '';
        }
        unset($line);
    }

    $entry['lines'] = $lines;
    return $entry;
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listEntries());

} elseif ($action === 'save') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = jnl_create([
        'journal_date'  => $body['journal_date']  ?? '',
        'description'   => $body['description']   ?? '',
        'status'        => 'draft',
        'lines'         => $body['lines']          ?? [],
        'period'        => $body['period']         ?? '',
        'reference_doc' => $body['reference_doc']  ?? '',
        'source_type'   => 'manual',
        'source_ref'    => null,
    ]);
    if (!$result['success']) http_response_code(422);
    echo json_encode($result);

} elseif ($action === 'update') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = jnl_update($body['ref'] ?? '', [
        'journal_date'  => $body['journal_date']  ?? '',
        'description'   => $body['description']   ?? '',
        'lines'         => $body['lines']          ?? [],
        'period'        => $body['period']         ?? '',
        'reference_doc' => $body['reference_doc']  ?? '',
    ]);
    if (!$result['success']) http_response_code(422);
    echo json_encode($result);

} elseif ($action === 'post') {
    $ref    = $_GET['ref'] ?? '';
    $result = jnl_post($ref);
    if (!$result['success']) http_response_code(422);
    echo json_encode($result);

} elseif ($action === 'get') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(getEntryWithLines($ref));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_journal_entries', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
