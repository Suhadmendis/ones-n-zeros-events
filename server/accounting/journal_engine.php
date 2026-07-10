<?php
// server/accounting/journal_engine.php
// THE ONLY place in the system that writes journal entries and general ledger rows.
// Every module — manual form or automated — calls jnl_create() or jnl_post() here.

if (defined('JNL_ENGINE_LOADED')) return;
define('JNL_ENGINE_LOADED', true);

require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../general/number_series.php';

// ---------------------------------------------------------------------------
// Public API
// ---------------------------------------------------------------------------

/**
 * Create a journal entry. The only valid source of journal entries in the system.
 *
 * $params:
 *   journal_date  string   required  'YYYY-MM-DD'
 *   description   string   required
 *   status        string   required  'draft'  — write JE tables only
 *                                    'posted' — write JE tables AND GL immediately
 *   lines         array    required  min 2, debits must equal credits
 *     each: [ account_code, debit_amount, credit_amount, description? ]
 *   source_type   string   optional  'manual'|'trip'|'trip_expense'|'fuel_entry'|
 *                                    'general_expense'|'vehicle_expense'|'cash_flow'|
 *                                    'employee_advance'|'loan'|'driver_settlement'|
 *                                    'cleaner_settlement'|'payment'|'deduction'|
 *                                    'accounts_payable'|'accounts_receivable'|'cash_bank'
 *   source_ref    string   optional  ref of originating record e.g. 'TRP-0000001'
 *   period        string   optional  'YYYY-MM' — derived from journal_date if omitted
 *   reference_doc string   optional  free-text external reference number
 *
 * Returns on success: [ success, journal_ref, status, total_debit, total_credit ]
 * Returns on failure: [ success, error ]
 */
function jnl_create(array $params): array {
    $err = _jnl_validate($params);
    if ($err !== null) return ['success' => false, 'error' => $err];

    $journal_date  = trim($params['journal_date']);
    $description   = trim($params['description']);
    $status        = trim($params['status']);
    $lines         = $params['lines'];
    $period        = trim($params['period'] ?? '') ?: substr($journal_date, 0, 7);
    $source_type   = trim($params['source_type']   ?? 'manual') ?: 'manual';
    $source_ref    = trim($params['source_ref']    ?? '') ?: null;
    $reference_doc = trim($params['reference_doc'] ?? '') ?: null;

    [$total_debit, $total_credit] = _jnl_line_totals($lines);

    try {
        $ref = consumeNextReference('journal_entries');
    } catch (RuntimeException $e) {
        return ['success' => false, 'error' => 'Failed to generate journal reference: ' . $e->getMessage()];
    }
    $actor = _jnl_actor();
    $now   = gmdate('Y-m-d\TH:i:s\Z');

    $header = supabase_post(SB_API . 'm_journal_entries', [
        'ref'           => $ref,
        'journal_date'  => $journal_date,
        'period'        => $period,
        'description'   => $description,
        'reference_doc' => $reference_doc,
        'source_type'   => $source_type,
        'source_ref'    => $source_ref,
        'status'        => 'draft',
        'total_debit'   => $total_debit,
        'total_credit'  => $total_credit,
        'drafted_by'    => $actor['name'],
        'drafted_at'    => $now,
        'created_by'    => $actor['ref'],
        'updated_by'    => $actor['ref'],
    ]);

    if (empty($header)) {
        return ['success' => false, 'error' => 'Failed to create journal entry header.'];
    }

    _jnl_insert_lines($ref, $lines);

    if ($status === 'posted') {
        $gl = _jnl_write_gl($ref, $journal_date, $period, $actor, $now);
        if (!$gl['success']) {
            return ['success' => false, 'error' => $gl['error'], 'journal_ref' => $ref];
        }
    }

    return [
        'success'      => true,
        'journal_ref'  => $ref,
        'status'       => $status,
        'total_debit'  => $total_debit,
        'total_credit' => $total_credit,
    ];
}

/**
 * Update an existing DRAFT journal entry's header and lines in place.
 * Only 'manual' entries reached via journal_entries_data.php's action=update
 * call this — automated source modules (accounts_payable, cash_bank, ...)
 * never update journal entries, they only ever create new ones.
 *
 * Once an entry is 'posted' it is immutable for audit/accounting integrity —
 * this refuses to touch anything but a 'draft' entry.
 *
 * $params: same shape as jnl_create()'s journal_date/description/lines/period/
 *          reference_doc — no 'status' or 'source_*', those never change here.
 *
 * Returns on success: [ success, journal_ref, status, total_debit, total_credit ]
 * Returns on failure: [ success, error ]
 */
function jnl_update(string $ref, array $params): array {
    if ($ref === '') return ['success' => false, 'error' => 'ref is required.'];

    $entries = supabase_get(
        SB_API . 'm_journal_entries?select=id,ref,status&ref=eq.' . urlencode($ref) . '&limit=1'
    );
    if (empty($entries) || ($entries[0]['status'] ?? '') !== 'draft') {
        return ['success' => false, 'error' => 'Only draft entries can be edited.'];
    }

    // _jnl_validate() only looks at journal_date/description/status/lines, so
    // feed it a synthetic 'draft' status — this function never changes status.
    $err = _jnl_validate($params + ['status' => 'draft']);
    if ($err !== null) return ['success' => false, 'error' => $err];

    $journal_date  = trim($params['journal_date']);
    $description   = trim($params['description']);
    $lines         = $params['lines'];
    $period        = trim($params['period'] ?? '') ?: substr($journal_date, 0, 7);
    $reference_doc = trim($params['reference_doc'] ?? '') ?: null;

    [$total_debit, $total_credit] = _jnl_line_totals($lines);

    $actor = _jnl_actor();

    supabase_patch(SB_API . 'm_journal_entries?ref=eq.' . urlencode($ref), [
        'journal_date'  => $journal_date,
        'period'        => $period,
        'description'   => $description,
        'reference_doc' => $reference_doc,
        'total_debit'   => $total_debit,
        'total_credit'  => $total_credit,
        'updated_by'    => $actor['ref'],
    ]);

    supabase_delete(SB_API . 'm_journal_entry_lines?journal_ref=eq.' . urlencode($ref));
    _jnl_insert_lines($ref, $lines);

    return [
        'success'      => true,
        'journal_ref'  => $ref,
        'status'       => 'draft',
        'total_debit'  => $total_debit,
        'total_credit' => $total_credit,
    ];
}

/**
 * Post an existing draft journal entry to the general ledger.
 * Called only from journal_entries_data.php when the user clicks "Post Entry".
 * Automated modules use jnl_create(['status'=>'posted']) instead.
 *
 * Returns on success: [ success, journal_ref, status ]
 * Returns on failure: [ success, error ]
 */
function jnl_post(string $ref): array {
    if ($ref === '') return ['success' => false, 'error' => 'ref is required.'];

    $entries = supabase_get(
        SB_API . 'm_journal_entries?select=id,ref,journal_date,period&ref=eq.' . urlencode($ref) . '&status=eq.draft&limit=1'
    );
    if (empty($entries)) {
        return ['success' => false, 'error' => 'Journal entry not found or already posted.'];
    }

    $entry  = $entries[0];
    $actor  = _jnl_actor();
    $now    = gmdate('Y-m-d\TH:i:s\Z');

    $gl = _jnl_write_gl($ref, $entry['journal_date'], $entry['period'], $actor, $now);
    if (!$gl['success']) return $gl;

    return ['success' => true, 'journal_ref' => $ref, 'status' => 'posted'];
}

/**
 * Posts a journal entry using the DR/CR account mapping configured in
 * m_posting_rules for ($moduleSystemName, $variant), instead of a hardcoded
 * pair of account codes. Every posting-rule line gets the full $amount on
 * its configured side (debit or credit) — the generalization of the
 * fixed-two-line pattern every auto-posting module (fuel_entry,
 * general_expense_entry, ...) currently hardcodes inline. Used by modules
 * generated with the "Post to GL" toggle in the Generate Module tool
 * (modules/settings/modules_mgmt/module_generator/).
 *
 * No-ops (returns success without posting) when $amount <= 0.
 *
 * Returns on success: same shape as jnl_create()
 * Returns on failure: [ success, error ]
 */
function jnl_create_from_posting_rules(
    string $moduleSystemName, ?string $variant, string $journalDate,
    string $description, string $sourceRef, float $amount
): array {
    if ($amount <= 0) return ['success' => true, 'skipped' => true];

    $query = SB_API . 'm_posting_rules?select=entry_type,account_code&module_system_name=eq.' . urlencode($moduleSystemName)
        . '&variant=' . ($variant !== null ? 'eq.' . urlencode($variant) : 'is.null')
        . '&order=line_no.asc';
    $rules = supabase_get($query);

    if (empty($rules)) {
        return ['success' => false, 'error' => "No posting rules configured for '{$moduleSystemName}'. Configure them from Settings → Posting Rules."];
    }

    $lines = array_map(fn($r) => [
        'account_code'  => $r['account_code'],
        'debit_amount'  => $r['entry_type'] === 'debit'  ? $amount : 0,
        'credit_amount' => $r['entry_type'] === 'credit' ? $amount : 0,
    ], $rules);

    return jnl_create([
        'journal_date' => $journalDate,
        'description'  => $description,
        'status'       => 'posted',
        'source_type'  => $moduleSystemName,
        'source_ref'   => $sourceRef,
        'lines'        => $lines,
    ]);
}

// ---------------------------------------------------------------------------
// Internal helpers — never call these from outside this file
// ---------------------------------------------------------------------------

function _jnl_validate(array $p): ?string {
    if (empty(trim($p['journal_date'] ?? '')))  return 'Journal date is required.';
    if (empty(trim($p['description']  ?? '')))  return 'Description is required.';
    if (!in_array($p['status'] ?? '', ['draft', 'posted'], true)) return 'Status must be draft or posted.';

    $lines = $p['lines'] ?? [];
    if (count($lines) < 2) return 'At least two lines are required.';

    $debit = 0; $credit = 0;
    foreach ($lines as $l) {
        if (empty(trim($l['account_code'] ?? ''))) return 'Each line must have an account_code.';
        $debit  += (float) ($l['debit_amount']  ?? 0);
        $credit += (float) ($l['credit_amount'] ?? 0);
    }
    if (abs($debit - $credit) > 0.001) return 'Journal entry must balance (debits must equal credits).';
    return null;
}

// Sums debit_amount/credit_amount across a line array. Shared by jnl_create()
// and jnl_update() so the balance math never drifts between the two paths.
function _jnl_line_totals(array $lines): array {
    $total_debit  = 0;
    $total_credit = 0;
    foreach ($lines as $l) {
        $total_debit  += (float) ($l['debit_amount']  ?? 0);
        $total_credit += (float) ($l['credit_amount'] ?? 0);
    }
    return [$total_debit, $total_credit];
}

// Inserts m_journal_entry_lines rows for $ref, numbered from 1. Shared by
// jnl_create() (fresh insert) and jnl_update() (re-insert after a delete of
// the old lines) so line-shape handling never drifts between the two paths.
function _jnl_insert_lines(string $ref, array $lines): void {
    foreach ($lines as $i => $line) {
        supabase_post(SB_API . 'm_journal_entry_lines', [
            'journal_ref'   => $ref,
            'line_no'       => $i + 1,
            'account_code'  => trim($line['account_code']),
            'description'   => trim($line['description'] ?? '') ?: null,
            'debit_amount'  => (float) ($line['debit_amount']  ?? 0),
            'credit_amount' => (float) ($line['credit_amount'] ?? 0),
        ]);
    }
}

function _jnl_actor(): array {
    $u    = current_user();
    $ref  = $u['ref'] ?? null;
    $name = ($u['full_name'] ?? '') ?: ($ref ?? 'System');
    return ['ref' => $ref, 'name' => $name];
}

function _jnl_resolve_account_names(array $codes): array {
    if (empty($codes)) return [];
    $codes = array_values(array_unique(array_filter($codes)));
    $coa   = supabase_get(SB_API . 'm_chart_of_accounts?select=account_code,account_name&account_code=in.(' . implode(',', $codes) . ')');
    return array_column($coa, 'account_name', 'account_code');
}

function _jnl_write_gl(string $jnl_ref, string $journal_date, string $period, array $actor, string $now): array {
    $lines = supabase_get(
        SB_API . 'm_journal_entry_lines?select=line_no,account_code,description,debit_amount,credit_amount&journal_ref=eq.' . urlencode($jnl_ref) . '&order=line_no.asc'
    );
    if (empty($lines)) return ['success' => false, 'error' => 'No lines found for journal entry.'];

    $nameMap = _jnl_resolve_account_names(array_column($lines, 'account_code'));

    $headers  = supabase_get(SB_API . 'm_journal_entries?select=description&ref=eq.' . urlencode($jnl_ref) . '&limit=1');
    $jnl_desc = $headers[0]['description'] ?? '';

    foreach ($lines as $line) {
        try {
            $glRef = consumeNextReference('general_ledger');
        } catch (RuntimeException $e) {
            return ['success' => false, 'error' => 'Failed to generate general ledger reference: ' . $e->getMessage()];
        }
        supabase_post(SB_API . 'm_general_ledger', [
            'ref'              => $glRef,
            'journal_ref'      => $jnl_ref,
            'line_no'          => (int) $line['line_no'],
            'account_code'     => $line['account_code'],
            'account_name'     => $nameMap[$line['account_code']] ?? null,
            'transaction_date' => $journal_date,
            'period'           => $period,
            'description'      => $line['description'] ?: $jnl_desc,
            'debit_amount'     => (float) ($line['debit_amount']  ?? 0),
            'credit_amount'    => (float) ($line['credit_amount'] ?? 0),
            'status'           => 'posted',
            'created_by'       => $actor['ref'],
            'updated_by'       => $actor['ref'],
        ]);
    }

    supabase_patch(
        SB_API . 'm_journal_entries?ref=eq.' . urlencode($jnl_ref),
        ['status' => 'posted', 'posted_by' => $actor['name'], 'posted_at' => $now]
    );

    return ['success' => true];
}
