<?php
// modules/settings/financial_settings/stg_posting_rules/stg_posting_rules_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ── list_modules ──────────────────────────────────────────────────────────────
if ($action === 'list_modules') {
    $rows = supabase_get(
        SB_API . 'sys_tms_modules?select=id,name,folder,subsection_ref&creates_journal_entry=eq.true&order=subsection_ref.asc,name.asc'
    );
    $subsectionMap = array_column(supabase_get(SB_API . 'sys_tms_subsections?select=ref,section_ref,name'), null, 'ref');
    $sectionMap    = array_column(supabase_get(SB_API . 'sys_tms_sections?select=ref,name'), 'name', 'ref');
    foreach ($rows as &$r) {
        $sub = $subsectionMap[$r['subsection_ref']] ?? null;
        $r['module']      = $r['name'];
        $r['system_name'] = $r['folder'];
        $r['subsection']  = $sub['name'] ?? '';
        $r['section']     = $sectionMap[$sub['section_ref'] ?? null] ?? '';
    }
    unset($r);
    echo json_encode($rows);
    exit;
}

// ── get_rules ─────────────────────────────────────────────────────────────────
if ($action === 'get_rules') {
    $module = trim($_GET['module'] ?? '');
    if ($module === '') { http_response_code(422); echo json_encode(['error' => 'module required']); exit; }
    $rows = supabase_get(
        SB_API . 'm_posting_rules?select=id,module_system_name,variant,line_no,entry_type,account_code,account_name,description&module_system_name=eq.' . urlencode($module) . '&order=variant.asc.nullsfirst,line_no.asc'
    );
    echo json_encode($rows);
    exit;
}

// ── save ──────────────────────────────────────────────────────────────────────
if ($action === 'save') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $module = trim($body['module_system_name'] ?? '');
    $lines  = $body['lines'] ?? [];

    if ($module === '') { http_response_code(422); echo json_encode(['error' => 'module_system_name required']); exit; }
    if (empty($lines))  { http_response_code(422); echo json_encode(['error' => 'No lines provided']); exit; }

    // Group lines by variant for validation
    $byVariant = [];
    foreach ($lines as $line) {
        $v = $line['variant'] ?? null;
        $k = $v ?? '__null__';
        $byVariant[$k][] = $line;
    }

    foreach ($byVariant as $vKey => $vLines) {
        $hasDebit  = false;
        $hasCredit = false;
        foreach ($vLines as $l) {
            if (($l['entry_type'] ?? '') === 'debit')  $hasDebit  = true;
            if (($l['entry_type'] ?? '') === 'credit') $hasCredit = true;
            if (empty(trim($l['account_code'] ?? ''))) {
                $variantLabel = ($vKey === '__null__') ? '' : ' (' . $vKey . ')';
                http_response_code(422);
                echo json_encode(['error' => 'All lines must have an account code' . $variantLabel . '.']);
                exit;
            }
        }
        if (!$hasDebit || !$hasCredit) {
            $variantLabel = ($vKey === '__null__') ? '' : ' (' . $vKey . ')';
            http_response_code(422);
            echo json_encode(['error' => 'Each rule set' . $variantLabel . ' must have at least one debit and one credit line.']);
            exit;
        }
    }

    $u        = current_user();
    $user_ref = $u['ref'] ?? null;

    // Delete all existing rules for this module (all variants at once)
    supabase_delete(SB_API . 'm_posting_rules?module_system_name=eq.' . urlencode($module));

    // Insert new lines
    $inserted = 0;
    foreach ($lines as $i => $line) {
        $row = supabase_post(SB_API . 'm_posting_rules', [
            'module_system_name' => $module,
            'variant'            => $line['variant'] ?: null,
            'line_no'            => $i + 1,
            'entry_type'         => $line['entry_type'],
            'account_code'       => trim($line['account_code']),
            'account_name'       => trim($line['account_name'] ?? '') ?: null,
            'description'        => trim($line['description']  ?? '') ?: null,
            'created_by'         => $user_ref,
            'updated_by'         => $user_ref,
        ]);
        if (!empty($row['id'])) $inserted++;
    }

    echo json_encode(['success' => true, 'inserted' => $inserted]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
