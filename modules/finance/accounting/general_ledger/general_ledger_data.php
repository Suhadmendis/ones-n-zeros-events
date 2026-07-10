<?php
// modules/finance/general_ledger/general_ledger_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

function buildGLFilters(array $p): string {
    $f = '';
    $acct = trim($p['account_code'] ?? '');
    if ($acct !== '')              $f .= '&account_code=eq.'      . rawurlencode($acct);
    if (!empty($p['journal_ref'])) $f .= '&journal_ref=eq.'       . rawurlencode(trim($p['journal_ref']));
    if (!empty($p['status']))      $f .= '&status=eq.'            . rawurlencode($p['status']);
    if (!empty($p['period']))      $f .= '&period=eq.'            . rawurlencode($p['period']);
    if (!empty($p['date_from']))   $f .= '&transaction_date=gte.' . rawurlencode($p['date_from']);
    if (!empty($p['date_to']))     $f .= '&transaction_date=lte.' . rawurlencode($p['date_to']);
    if (!empty($p['description'])) $f .= '&description=ilike.*'   . rawurlencode(trim($p['description'])) . '*';
    return $f;
}

function filterEntries(array $p): array {
    $filters  = buildGLFilters($p);
    $page     = max(1, (int)($p['page']     ?? 1));
    $per_page = max(10, min(500, (int)($p['per_page'] ?? 50)));
    $offset   = ($page - 1) * $per_page;

    // Paginated data
    $data = supabase_get(
        SB_API . 'm_general_ledger?select=ref,journal_ref,line_no,account_code,account_name,transaction_date,period,description,debit_amount,credit_amount,status'
        . $filters
        . '&order=transaction_date.asc,id.asc'
        . '&limit=' . $per_page
        . '&offset=' . $offset
    );

    // Total count via Prefer: count=exact (reliable across PostgREST versions)
    $total_count = supabase_get_count(
        SB_API . 'm_general_ledger?select=id' . $filters
    );

    // Sums — two separate calls so keys don't collide; guard against error objects
    $agg_dr = supabase_get(SB_API . 'm_general_ledger?select=debit_amount.sum()'  . $filters);
    $agg_cr = supabase_get(SB_API . 'm_general_ledger?select=credit_amount.sum()' . $filters);

    $total_debit  = (isset($agg_dr[0]) && is_array($agg_dr[0])) ? (float)($agg_dr[0]['sum'] ?? 0) : 0.0;
    $total_credit = (isset($agg_cr[0]) && is_array($agg_cr[0])) ? (float)($agg_cr[0]['sum'] ?? 0) : 0.0;

    $result = [
        'data'         => $data,
        'page'         => $page,
        'per_page'     => $per_page,
        'total_count'  => $total_count,
        'total_debit'  => $total_debit,
        'total_credit' => $total_credit,
    ];

    // Opening balance for single-account running balance
    $acct = trim($p['account_code'] ?? '');
    if ($acct !== '') {
        if ($offset > 0) {
            $ob_rows = supabase_get(
                SB_API . 'm_general_ledger?select=debit_amount,credit_amount&account_code=eq.' . rawurlencode($acct)
                . '&order=transaction_date.asc,id.asc'
                . '&limit=' . $offset
            );
            $ob_dr = array_sum(array_column($ob_rows, 'debit_amount'));
            $ob_cr = array_sum(array_column($ob_rows, 'credit_amount'));
            $result['opening_balance'] = (float)$ob_dr - (float)$ob_cr;
        } else {
            $result['opening_balance'] = 0.0;
        }
    }

    return $result;
}

$action = $_GET['action'] ?? '';

if ($action === 'filter') {
    echo json_encode(filterEntries($_GET));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_general_ledger', $ref)]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
