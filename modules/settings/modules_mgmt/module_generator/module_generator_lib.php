<?php
// module_generator_lib.php — Generate Module tool, pure generation logic.
//
// No require_login(), no direct output — importable by both the HTTP
// endpoint (module_generator_data.php) and the CLI script
// (bin/generate_module.php) so both drive the exact same code path.
//
// Supports three module_type shapes:
//   entry          — flat table, header fields only (the original shape)
//   report         — no business table; date-range + Run + Excel/Print
//                     scaffold with a stub aggregation query
//   header_detail  — a header table plus an optional child line-items table
//                     (empty line_fields == identical to 'entry')
// Plus an optional GL-posting toggle (entry/header_detail only) that seeds
// m_posting_rules and wires a Journal Entry Preview + save-time post via
// jnl_create_from_posting_rules() (server/accounting/journal_engine.php).

require_once __DIR__ . '/../../../../server/supabase.php';

const MODULES_ROOT = __DIR__ . '/../../..'; // .../modules

const FIELD_TYPE_CODES = ['text', 'textarea', 'number', 'decimal', 'date', 'dropdown', 'checkbox'];
const RESERVED_COLUMNS = ['id', 'ref', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by'];
const MODULE_TYPES      = ['entry', 'report', 'header_detail'];
const GL_ENTRY_TYPES    = ['debit', 'credit'];

function slugifyModuleName(string $name): string {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
    $slug = trim($slug, '_');
    $slug = preg_replace('/_{2,}/', '_', $slug);
    return substr($slug, 0, 50);
}

function isValidSlug(string $slug): bool {
    return (bool) preg_match('/^[a-z][a-z0-9_]{2,49}$/', $slug);
}

function isValidTableName(string $t): bool {
    return (bool) preg_match('/^m_[a-z][a-z0-9_]{2,58}$/', $t);
}

function isValidLineTableName(string $t): bool {
    return (bool) preg_match('/^t_[a-z][a-z0-9_]{2,58}$/', $t);
}

function isValidColumnName(string $c): bool {
    return (bool) preg_match('/^[a-z][a-z0-9_]{1,49}$/', $c);
}

function isValidRefPrefix(string $p): bool {
    return (bool) preg_match('/^[A-Z0-9]{2,6}$/', $p);
}

function toPascalCase(string $slug): string {
    return str_replace('_', '', ucwords($slug, '_'));
}

function toKebabCase(string $slug): string {
    return str_replace('_', '-', $slug);
}

function toTitleCase(string $slug): string {
    return ucwords(str_replace('_', ' ', $slug));
}

// Re-validates and normalizes a client-sent field list — never trust column
// names / types straight from the request body. Shared by header `fields`
// and header_detail's `line_fields` (same shape, different target table).
function validateFields($rawFields): array {
    if (!is_array($rawFields)) return [];

    $seen = [];
    $out  = [];
    foreach ($rawFields as $f) {
        $label    = trim((string) ($f['label'] ?? ''));
        $column   = trim((string) ($f['column'] ?? ''));
        $type     = trim((string) ($f['type'] ?? ''));
        $required = !empty($f['required']);
        $options  = [];

        if ($label === '') {
            throw new RuntimeException('Every field needs a label.');
        }
        if (!isValidColumnName($column)) {
            throw new RuntimeException("Invalid column name for field '{$label}': must start with a letter (lowercase letters, numbers, underscores only).");
        }
        if (in_array($column, RESERVED_COLUMNS, true)) {
            throw new RuntimeException("'{$column}' is a reserved column name.");
        }
        if (isset($seen[$column])) {
            throw new RuntimeException("Duplicate column name: '{$column}'.");
        }
        $seen[$column] = true;

        if (!in_array($type, FIELD_TYPE_CODES, true)) {
            throw new RuntimeException("Unknown field type for '{$label}'.");
        }

        if ($type === 'dropdown') {
            $options = array_values(array_filter(array_map('trim', (array) ($f['options'] ?? []))));
            if (count($options) < 2) {
                throw new RuntimeException("Dropdown field '{$label}' needs at least 2 options.");
            }
        }

        $out[] = ['label' => $label, 'column' => $column, 'type' => $type, 'required' => $required, 'options' => $options];
    }
    return $out;
}

// Same validation stg_posting_rules_data.php's `save` action already applies
// to posting-rule lines — reused here so the rule (>=1 debit, >=1 credit,
// every line has an account_code) never drifts between the two callers.
function validateGlLines($rawLines): array {
    if (!is_array($rawLines)) {
        throw new RuntimeException('GL posting lines are required.');
    }

    $out = [];
    $hasDebit  = false;
    $hasCredit = false;
    foreach ($rawLines as $l) {
        $entryType   = trim((string) ($l['entry_type']   ?? ''));
        $accountCode = trim((string) ($l['account_code'] ?? ''));
        $accountName = trim((string) ($l['account_name'] ?? ''));
        $description = trim((string) ($l['description']  ?? ''));

        if (!in_array($entryType, GL_ENTRY_TYPES, true)) {
            throw new RuntimeException('Each GL posting line needs a debit/credit type.');
        }
        if ($accountCode === '') {
            throw new RuntimeException('Each GL posting line needs an account code.');
        }
        if ($entryType === 'debit')  $hasDebit  = true;
        if ($entryType === 'credit') $hasCredit = true;

        $out[] = [
            'entry_type'   => $entryType,
            'account_code' => $accountCode,
            'account_name' => $accountName ?: null,
            'description'  => $description ?: null,
        ];
    }

    if (count($out) < 2) {
        throw new RuntimeException('At least two GL posting lines are required (one debit, one credit).');
    }
    if (!$hasDebit || !$hasCredit) {
        throw new RuntimeException('GL posting lines must include at least one debit and one credit line.');
    }
    return $out;
}

function fieldJsDefault(array $field): string {
    return match ($field['type']) {
        'number', 'decimal' => '0',
        'checkbox'           => 'false',
        default              => "''",
    };
}

function fieldPhpDefault(array $field): string {
    return match ($field['type']) {
        'number', 'decimal' => '0',
        'checkbox'           => 'false',
        default              => "''",
    };
}

// Recursively deletes a directory — used to roll back a partially-written scaffold.
function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

// supabase_post()/supabase_patch() (server/supabase.php) return `$rows[0] ?? []`,
// which silently collapses a PostgREST error body (a JSON *object*, no index 0)
// down to an empty array — indistinguishable from a genuine empty success. That's
// fine for existing callers, which sanity-check the returned row's own fields, but
// create_module_table()/drop_module_table() return void (empty body / 204 on
// success), so there's nothing to sanity-check — the HTTP status is the only
// signal. This talks to PostgREST directly to surface RPC errors as exceptions.
function callVoidRpc(string $function, array $args): void {
    $ch = curl_init(SUPABASE_URL . SB_API . 'rpc/' . $function);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($code < 200 || $code >= 300) {
        $decoded = json_decode((string) $body, true);
        $msg = is_array($decoded) ? ($decoded['message'] ?? $body) : $body;
        throw new RuntimeException($msg !== '' ? $msg : "{$function} failed (HTTP {$code})");
    }
}

// Same direct-curl reasoning as callVoidRpc(), for a read-only jsonb-returning
// RPC — reused from TABLES.php's get_schema_overview() usage.
function tableExists(string $table): bool {
    $ch = curl_init(SUPABASE_URL . SB_API . 'rpc/get_schema_overview');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    $overview = json_decode((string) curl_exec($ch), true);

    if (!is_array($overview)) return false;
    foreach ($overview as $t) {
        if (($t['table_name'] ?? null) === $table) return true;
    }
    return false;
}

// Seeds m_posting_rules for a generated module — same insert shape
// stg_posting_rules_data.php's `save` action uses (module_system_name,
// variant=null, line_no, entry_type, account_code, account_name, description).
function seedPostingRules(string $moduleSystemName, array $glLines): void {
    $actorRef = current_user()['ref'] ?? null;
    foreach ($glLines as $i => $line) {
        supabase_post(SB_API . 'm_posting_rules', [
            'module_system_name' => $moduleSystemName,
            'variant'            => null,
            'line_no'            => $i + 1,
            'entry_type'         => $line['entry_type'],
            'account_code'       => $line['account_code'],
            'account_name'       => $line['account_name'],
            'description'        => $line['description'],
            'created_by'         => $actorRef,
            'updated_by'         => $actorRef,
        ]);
    }
}

// Undoes whichever generation steps already succeeded, in reverse order.
// Every argument is optional — callers pass only what actually got created
// before the failure.
function rollbackGeneration(
    ?int $moduleId, ?string $tableName, ?int $seriesId, ?int $permId,
    ?string $lineTableName = null, ?string $postingRulesModule = null
): void {
    if ($postingRulesModule) {
        supabase_delete(SB_API . 'm_posting_rules?module_system_name=eq.' . urlencode($postingRulesModule));
    }
    if ($permId) {
        supabase_delete(SB_API . 'sys_role_module_permissions?id=eq.' . $permId);
    }
    if ($seriesId) {
        supabase_delete(SB_API . 'sys_tms_module_number_series?id=eq.' . $seriesId);
    }
    if ($lineTableName) {
        try { callVoidRpc('drop_module_line_table', ['p_table' => $lineTableName]); } catch (\Throwable $e) { /* best effort */ }
    }
    if ($tableName) {
        try { callVoidRpc('drop_module_table', ['p_table' => $tableName]); } catch (\Throwable $e) { /* best effort */ }
    }
    if ($moduleId) {
        supabase_post(SB_API . 'rpc/unregister_tms_module', ['p_id' => $moduleId]);
    }
}

// ---------------------------------------------------------------------------
// Field-driven fragment renderers — shared by every template builder below.
// ---------------------------------------------------------------------------

function renderFieldFormRow(array $field, string $kebab): string {
    $id    = $kebab . '-' . str_replace('_', '-', $field['column']);
    $label = htmlspecialchars($field['label'], ENT_QUOTES);
    $req   = $field['required'] ? ' required' : '';
    $col   = $field['column'];

    if ($field['type'] === 'checkbox') {
        return <<<HTML
            <div class="row mb-3">
              <div class="col-sm-8 offset-sm-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="{$id}" v-model="form.{$col}" />
                  <label class="form-check-label" for="{$id}">{$label}</label>
                </div>
              </div>
            </div>
        HTML;
    }

    $input = match ($field['type']) {
        'textarea' => "<textarea class=\"form-control\" id=\"{$id}\" v-model=\"form.{$col}\"{$req}></textarea>",
        'number'   => "<input type=\"number\" step=\"1\" class=\"form-control\" id=\"{$id}\" v-model.number=\"form.{$col}\"{$req} />",
        'decimal'  => "<input type=\"number\" step=\"0.01\" class=\"form-control\" id=\"{$id}\" v-model.number=\"form.{$col}\"{$req} />",
        'date'     => "<input type=\"date\" class=\"form-control\" id=\"{$id}\" v-model=\"form.{$col}\"{$req} />",
        'dropdown' => renderDropdownInput($field, $id, $req),
        default    => "<input type=\"text\" class=\"form-control\" id=\"{$id}\" v-model=\"form.{$col}\"{$req} />",
    };

    return <<<HTML
            <div class="row mb-3">
              <label for="{$id}" class="col-sm-4 col-form-label">{$label}</label>
              <div class="col-sm-8">
                {$input}
              </div>
            </div>
        HTML;
}

function renderDropdownInput(array $field, string $id, string $req): string {
    $opts = '';
    foreach ($field['options'] as $opt) {
        $optSafe = htmlspecialchars($opt, ENT_QUOTES);
        $opts .= "\n                  <option value=\"{$optSafe}\">{$optSafe}</option>";
    }
    return "<select class=\"form-select\" id=\"{$id}\" v-model=\"form.{$field['column']}\"{$req}>\n"
         . "                  <option value=\"\" disabled>Select…</option>{$opts}\n"
         . "                </select>";
}

function renderFormFieldsBlock(array $fields, string $kebab): string {
    return implode("\n", array_map(fn($f) => renderFieldFormRow($f, $kebab), $fields));
}

function renderJsDefaults(array $fields): string {
    $lines = array_map(fn($f) => "      {$f['column']}: " . fieldJsDefault($f) . ',', $fields);
    return implode("\n", $lines);
}

function renderIsDirtyExpr(array $fields): string {
    if (!$fields) return 'false';
    $parts = array_map(function ($f) {
        return match ($f['type']) {
            'checkbox'           => "this.form.{$f['column']} !== false",
            'number', 'decimal'  => "this.form.{$f['column']} !== 0",
            default              => "this.form.{$f['column']} !== ''",
        };
    }, $fields);
    return implode(" ||\n        ", $parts);
}

function renderResetLines(array $fields): string {
    $lines = array_map(fn($f) => "      this.form.{$f['column']} = " . fieldJsDefault($f) . ';', $fields);
    return implode("\n", $lines);
}

function renderLoadLines(array $fields): string {
    $lines = array_map(fn($f) => "      this.form.{$f['column']} = data.{$f['column']};", $fields);
    return implode("\n", $lines);
}

function renderPrintParamLines(array $fields): string {
    $lines = array_map(fn($f) => "        {$f['column']}: String(this.form.{$f['column']} ?? ''),", $fields);
    return implode("\n", $lines);
}

function renderDataPhpMapLines(array $fields): string {
    // 'date' columns reject '' at the DB layer (invalid input syntax for type date) — send
    // null instead when the field is left blank, unlike the '' default used for text fields.
    $lines = array_map(function ($f) {
        if ($f['type'] === 'date') {
            return "        '{$f['column']}' => !empty(\$data['{$f['column']}']) ? \$data['{$f['column']}'] : null,";
        }
        return "        '{$f['column']}' => \$data['{$f['column']}'] ?? " . fieldPhpDefault($f) . ',';
    }, $fields);
    return implode("\n", $lines);
}

function renderListSelectCols(array $fields): string {
    if (!$fields) return '';
    return ',' . implode(',', array_map(fn($f) => $f['column'], $fields));
}

function renderSearchHeaderCols(array $fields): string {
    $lines = array_map(fn($f) => '              <th>' . htmlspecialchars($f['label'], ENT_QUOTES) . '</th>', $fields);
    return implode("\n", $lines);
}

function renderSearchColumnDefs(array $fields): string {
    $lines = array_map(fn($f) => "      { data: '{$f['column']}' },", $fields);
    return implode("\n", $lines);
}

function renderPrintRows(array $fields): string {
    $lines = array_map(function ($f) {
        $label = htmlspecialchars($f['label'], ENT_QUOTES);
        return "      <tr><th>{$label}</th><td><?= htmlspecialchars(\$_GET['{$f['column']}'] ?? '') ?></td></tr>";
    }, $fields);
    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// Line-table (header_detail) fragment renderers — table-cell style inputs,
// no FK/lookup pickers (out of scope for v1 — same limitation MODULE_GUIDE.md
// already documents for header fields).
// ---------------------------------------------------------------------------

function renderLineFieldHeaderCell(array $field): string {
    return '                <th>' . htmlspecialchars($field['label'], ENT_QUOTES) . '</th>';
}

function renderLineHeaderCells(array $lineFields): string {
    return implode("\n", array_map('renderLineFieldHeaderCell', $lineFields));
}

function renderLineDropdownInput(array $field): string {
    $opts = '';
    foreach ($field['options'] as $opt) {
        $optSafe = htmlspecialchars($opt, ENT_QUOTES);
        $opts .= "\n                    <option value=\"{$optSafe}\">{$optSafe}</option>";
    }
    return "<select class=\"form-select form-select-sm\" v-model=\"line.{$field['column']}\">\n"
         . "                    <option value=\"\">—</option>{$opts}\n"
         . "                  </select>";
}

function renderLineFieldCell(array $field): string {
    $col = $field['column'];
    $input = match ($field['type']) {
        'textarea' => "<textarea class=\"form-control form-control-sm\" v-model=\"line.{$col}\" rows=\"1\"></textarea>",
        'number'   => "<input type=\"number\" step=\"1\" class=\"form-control form-control-sm\" v-model.number=\"line.{$col}\" />",
        'decimal'  => "<input type=\"number\" step=\"0.01\" class=\"form-control form-control-sm\" v-model.number=\"line.{$col}\" />",
        'date'     => "<input type=\"date\" class=\"form-control form-control-sm\" v-model=\"line.{$col}\" />",
        'dropdown' => renderLineDropdownInput($field),
        'checkbox' => "<div class=\"form-check d-flex justify-content-center\"><input class=\"form-check-input\" type=\"checkbox\" v-model=\"line.{$col}\" /></div>",
        default    => "<input type=\"text\" class=\"form-control form-control-sm\" v-model=\"line.{$col}\" />",
    };
    return "                <td>{$input}</td>";
}

function renderLineCells(array $lineFields): string {
    return implode("\n", array_map('renderLineFieldCell', $lineFields));
}

function renderLineJsDefaults(array $lineFields): string {
    $lines = array_map(fn($f) => "  {$f['column']}: " . fieldJsDefault($f) . ',', $lineFields);
    return implode("\n", $lines);
}

function renderLineHasDataExpr(array $lineFields): string {
    if (!$lineFields) return 'false';
    return implode(' || ', array_map(fn($f) => "l.{$f['column']}", $lineFields));
}

function renderLineLoadMap(array $lineFields): string {
    $lines = array_map(fn($f) => "            {$f['column']}: l.{$f['column']} ?? " . fieldJsDefault($f) . ',', $lineFields);
    return implode("\n", $lines);
}

function renderLineDataPhpMapLines(array $lineFields): string {
    $lines = array_map(function ($f) {
        if ($f['type'] === 'date') {
            return "            '{$f['column']}' => !empty(\$line['{$f['column']}']) ? \$line['{$f['column']}'] : null,";
        }
        return "            '{$f['column']}' => \$line['{$f['column']}'] ?? " . fieldPhpDefault($f) . ',';
    }, $lineFields);
    return implode("\n", $lines);
}

function renderLineHasDataPhpExpr(array $lineFields): string {
    if (!$lineFields) return 'false';
    return implode(' || ', array_map(fn($f) => "!empty(\$line['{$f['column']}'])", $lineFields));
}

// ---------------------------------------------------------------------------
// GL preview fragment renderers — reused verbatim (markup/CSS) from the
// hand-built Check-GL pattern in modules/operations/expenses/fuel_entry/,
// generalized from a hardcoded 2-line DR/CR to the N lines configured on
// the generator (still constrained to >=1 debit + >=1 credit by
// validateGlLines()).
// ---------------------------------------------------------------------------

function renderGlToggleSwitch(): string {
    return <<<HTML
          <div class="form-check form-switch d-flex align-items-center gap-2 ms-2 mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="checkGLToggle" v-model="checkGL" style="width:2.4em;height:1.25em;cursor:pointer">
            <label class="form-check-label fw-semibold text-nowrap" for="checkGLToggle" style="cursor:pointer;font-size:.85rem">Check GL</label>
          </div>
    HTML;
}

function renderJePreviewBlock(array $glLines): string {
    $rows = '';
    foreach ($glLines as $line) {
        $isDebit  = $line['entry_type'] === 'debit';
        $rowClass = $isDebit ? 'je-preview__row--dr' : 'je-preview__row--cr';
        $typeCls  = $isDebit ? 'je-preview__type--dr' : 'je-preview__type--cr';
        $typeText = $isDebit ? 'DR' : 'CR';
        $code     = htmlspecialchars($line['account_code'], ENT_QUOTES);
        $name     = htmlspecialchars($line['account_name'] ?: ($line['description'] ?: $line['account_code']), ENT_QUOTES);
        $debitCell  = $isDebit
            ? '<td class="je-preview__amount je-preview__amount--dr">{{ fmtAmount(jeTotalAmount) }}</td>'
            : '<td class="je-preview__amount je-preview__amount--blank">—</td>';
        $creditCell = !$isDebit
            ? '<td class="je-preview__amount je-preview__amount--cr">{{ fmtAmount(jeTotalAmount) }}</td>'
            : '<td class="je-preview__amount je-preview__amount--blank">—</td>';

        $rows .= <<<HTML
                <tr class="je-preview__row {$rowClass}">
                  <td><span class="je-preview__type {$typeCls}">{$typeText}</span></td>
                  <td class="je-preview__code">{$code}</td>
                  <td class="je-preview__name">{$name}</td>
                  {$debitCell}
                  {$creditCell}
                </tr>

        HTML;
    }

    return <<<HTML
        <!-- Journal Entry Preview -->
        <div v-if="checkGL" class="je-preview mb-4" :class="jeTotalAmount > 0 ? 'je-preview--active' : 'je-preview--empty'">
          <div class="je-preview__header">
            <span class="je-preview__icon"><i class="bi bi-journal-bookmark-fill"></i></span>
            <span class="je-preview__title">Journal Entry Preview</span>
            <span class="je-preview__badge">Auto-Post on Save</span>
          </div>
          <div v-if="jeTotalAmount > 0" class="je-preview__body">
            <table class="je-preview__table">
              <thead>
                <tr>
                  <th class="je-preview__th--type">Type</th>
                  <th class="je-preview__th--code">Code</th>
                  <th class="je-preview__th--name">Account</th>
                  <th class="je-preview__th--amount">Debit</th>
                  <th class="je-preview__th--amount">Credit</th>
                </tr>
              </thead>
              <tbody>
        {$rows}      </tbody>
              <tfoot>
                <tr class="je-preview__total">
                  <td colspan="3">Total</td>
                  <td class="je-preview__amount">{{ fmtAmount(jeTotalAmount) }}</td>
                  <td class="je-preview__amount">{{ fmtAmount(jeTotalAmount) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div v-else class="je-preview__placeholder">
            <i class="bi bi-pencil-square me-2 opacity-50"></i>Enter an amount above to preview the journal entry.
          </div>
        </div>
    HTML;
}

function jePreviewCss(): string {
    return <<<'CSS'
<style>
.je-preview {
  border-radius: 8px;
  overflow: hidden;
  border: 1.5px solid #dee2e6;
  background: #fff;
  transition: border-color .2s;
}
.je-preview--active  { border-color: #0d6efd; }
.je-preview--empty   { border-color: #dee2e6; }
.je-preview__header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
.je-preview--active .je-preview__header {
  background: #eef3fd;
  border-bottom-color: #c5d5f7;
}
.je-preview__icon   { color: #0d6efd; font-size: .95rem; }
.je-preview__title  { font-weight: 600; font-size: .82rem; letter-spacing: .02em; color: #1a2340; flex: 1; }
.je-preview__badge  {
  font-size: .7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
  color: #fff; background: #0d6efd; padding: 2px 9px; border-radius: 20px;
}
.je-preview__body    { padding: 12px 16px 14px; }
.je-preview__placeholder {
  padding: 14px 16px; font-size: .8rem; color: #adb5bd; font-style: italic;
}
.je-preview__table {
  width: 100%; border-collapse: separate; border-spacing: 0; font-size: .81rem;
}
.je-preview__table thead tr { background: #f1f3f5; }
.je-preview__table th {
  padding: 6px 10px; font-weight: 600; font-size: .72rem; letter-spacing: .04em;
  text-transform: uppercase; color: #6c757d; border-bottom: 1.5px solid #dee2e6;
}
.je-preview__th--type   { width: 52px; }
.je-preview__th--code   { width: 64px; }
.je-preview__th--name   { }
.je-preview__th--amount { width: 130px; text-align: right; }
.je-preview__row td      { padding: 7px 10px; border-bottom: 1px solid #f1f3f5; }
.je-preview__row--dr td  { background: #fff9f9; }
.je-preview__row--cr td  { background: #f6fff8; }
.je-preview__type {
  display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .06em;
  padding: 2px 7px; border-radius: 4px;
}
.je-preview__type--dr { background: #ffe0e0; color: #c0392b; }
.je-preview__type--cr { background: #d6f5e0; color: #1a7f45; }
.je-preview__code   { font-family: monospace; font-weight: 600; color: #495057; }
.je-preview__name   { color: #212529; }
.je-preview__amount        { text-align: right; font-family: monospace; font-weight: 500; }
.je-preview__amount--dr    { color: #c0392b; }
.je-preview__amount--cr    { color: #1a7f45; }
.je-preview__amount--blank { color: #ced4da; }
.je-preview__total td {
  padding: 7px 10px; font-weight: 700; font-size: .8rem; background: #f8f9fa;
  border-top: 1.5px solid #dee2e6; font-family: monospace; text-align: right;
}
.je-preview__total td:first-child {
  text-align: left; font-family: inherit; color: #495057;
}
</style>
CSS;
}

// PHP side of the save-time GL post, appended into a generated save{Pascal}()
// function's body right before its `return`. Picks the module's first `date`
// header field (if any) to journal against; falls back to today.
function renderGlPostCallPhp(string $slug, array $fields, array $gl): string {
    $amountField = $gl['amount_field'];
    $dateField   = null;
    foreach ($fields as $f) {
        if ($f['type'] === 'date') { $dateField = $f['column']; break; }
    }
    $dateExpr = $dateField
        ? "!empty(\$data['{$dateField}']) ? \$data['{$dateField}'] : date('Y-m-d')"
        : "date('Y-m-d')";
    $title = toTitleCase($slug);

    return <<<PHP


    if ((float) (\$data['{$amountField}'] ?? 0) > 0 && !empty(\$ref)) {
        jnl_create_from_posting_rules('{$slug}', null, {$dateExpr}, '{$title} ' . \$ref, \$ref, (float) \$data['{$amountField}']);
    }
PHP;
}

// ---------------------------------------------------------------------------
// Scaffold file templates. Placeholders are substituted via str_replace, not
// PHP string interpolation, so the templates can freely contain literal '$'
// (Vue bindings, PHP variables) without needing escaping.
// ---------------------------------------------------------------------------

function renderTemplate(string $tpl, array $vars): string {
    return strtr($tpl, $vars);
}

// ============================================================================
// module_type = 'entry' (and header_detail with no line_fields, which is
// generated identically to 'entry')
// ============================================================================

function buildModulePhp(string $slug, string $sectionFolder, string $subsectionFolder, array $fields, ?array $gl = null): string {
    $kebab = toKebabCase($slug);
    $camel = lcfirst(toPascalCase($slug));
    $title = toTitleCase($slug);

    $fieldsColumn = $fields
        ? "\n          <div class=\"col-md-6\">\n    <form ref=\"form\">\n" . renderFormFieldsBlock($fields, $kebab) . "\n    </form>\n          </div>"
        : '';
    $emptyNote = $fields
        ? ''
        : "\n        <!-- No business fields were added when this module was generated — edit this file, {$slug}.js, and {$slug}_data.php to add more. -->";

    $glToggle  = $gl ? "\n" . renderGlToggleSwitch() : '';
    $jePreview = $gl ? "\n" . renderJePreviewBlock($gl['lines']) : '';
    $glCss     = $gl ? "\n" . jePreviewCss() : '';

    $tpl = <<<'EOT'
<?php /* modules/{{SECTION_FOLDER}}/{{SUBSECTION_FOLDER}}/{{SLUG}}/{{SLUG}}.php — generated by Generate Module */ ?>

<div id="{{KEBAB}}-app" class="row g-4">
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#{{CAMEL}}SearchModal">Search</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>{{GL_TOGGLE}}
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>
{{EMPTY_NOTE}}{{JE_PREVIEW}}
        <div class="row mt-2 g-4">
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="{{KEBAB}}-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="{{KEBAB}}-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
          </div>{{FIELDS_COLUMN}}
        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">{{TITLE}} saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/{{SLUG}}_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/{{SECTION_FOLDER}}/{{SUBSECTION_FOLDER}}/{{SLUG}}/{{SLUG}}.js"></script>{{GL_CSS}}
EOT;

    return renderTemplate($tpl, [
        '{{SECTION_FOLDER}}'    => $sectionFolder,
        '{{SUBSECTION_FOLDER}}' => $subsectionFolder,
        '{{SLUG}}'              => $slug,
        '{{KEBAB}}'             => $kebab,
        '{{CAMEL}}'             => $camel,
        '{{TITLE}}'             => $title,
        '{{FIELDS_COLUMN}}'     => $fieldsColumn,
        '{{EMPTY_NOTE}}'        => $emptyNote,
        '{{GL_TOGGLE}}'         => $glToggle,
        '{{JE_PREVIEW}}'        => $jePreview,
        '{{GL_CSS}}'            => $glCss,
    ]);
}

function buildModuleJs(string $slug, string $sectionFolder, string $subsectionFolder, array $fields, ?array $gl = null): string {
    $kebab   = toKebabCase($slug);
    $dataDir = "/modules/{$sectionFolder}/{$subsectionFolder}/{$slug}";

    $jsDefaults = $fields ? "\n" . renderJsDefaults($fields) : '';
    $isDirty    = renderIsDirtyExpr($fields);
    $resetLines = $fields ? "\n" . renderResetLines($fields) : '';
    $loadLines  = $fields ? "\n" . renderLoadLines($fields) : '';
    $printLines = $fields ? "\n" . renderPrintParamLines($fields) : '';

    $glData     = $gl ? "\n      checkGL: true," : '';
    $glComputed = $gl ? "\n    jeTotalAmount() {\n      const v = parseFloat(this.form.{$gl['amount_field']});\n      return isNaN(v) || v <= 0 ? 0 : v;\n    }," : '';
    $glMethods  = $gl ? "\n    fmtAmount(v) {\n      return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });\n    },\n" : '';

    $tpl = <<<'EOT'
// {{SLUG}}.js — generated by Generate Module

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading: false,
      saving:  false,
      saved:   false,
      error:   '',
      isExisting: false,{{GL_DATA}}
      form: {
        ref: '',{{JS_DEFAULTS}}
      },
    };
  },

  computed: {
    isDirty() {
      return (
        {{IS_DIRTY_EXPR}}
      );
    },{{GL_COMPUTED}}
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('{{SLUG}}-selected', (e) => this.loadRecord(e.detail));
  },

  methods: {{{GL_METHODS}}
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('{{DATA_DIR}}/{{SLUG}}_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref: this.form.ref,{{PRINT_PARAMS}}
          });
          window.open('{{DATA_DIR}}/{{SLUG}}_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.saved = false;
      this.error = '';
      this.isExisting = false;{{RESET_LINES}}
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.$refs.form && !this.$refs.form.reportValidity()) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('{{DATA_DIR}}/{{SLUG}}_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('{{DATA_DIR}}/{{SLUG}}_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
            proceed('update');
          })
          .catch(err => { this.error = 'Failed to verify record.'; console.error(err); this.saving = false; });
      } else {
        proceed('save');
      }
    },

    loadRecord(data) {
      this.form.ref = data.ref;{{LOAD_LINES}}
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },
  },
}).mount('#{{KEBAB}}-app');
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'         => $slug,
        '{{KEBAB}}'        => $kebab,
        '{{DATA_DIR}}'     => $dataDir,
        '{{JS_DEFAULTS}}'  => $jsDefaults,
        '{{IS_DIRTY_EXPR}}'=> $isDirty,
        '{{RESET_LINES}}'  => $resetLines,
        '{{LOAD_LINES}}'   => $loadLines,
        '{{PRINT_PARAMS}}' => $printLines,
        '{{GL_DATA}}'      => $glData,
        '{{GL_COMPUTED}}'  => $glComputed,
        '{{GL_METHODS}}'   => $glMethods,
    ]);
}

function buildModuleDataPhp(string $slug, string $tableName, array $fields, ?array $gl = null): string {
    $pascal = toPascalCase($slug);
    $fieldMap   = $fields ? "\n" . renderDataPhpMapLines($fields) : '';
    $selectCols = renderListSelectCols($fields);

    $glRequire  = $gl ? "\nrequire_once __DIR__ . '/../../../../server/accounting/journal_engine.php';" : '';
    $glPostCall = $gl ? renderGlPostCallPhp($slug, $fields, $gl) : '';

    $tpl = <<<'EOT'
<?php
// {{SLUG}}_data.php — generated by Generate Module

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';{{GL_REQUIRE}}

header('Content-Type: application/json');

function save{{PASCAL}}(array $data): array {
    $ref = consumeNextReference('{{SLUG}}');

    $record = supabase_post(SB_API . '{{TABLE}}', [
        'ref' => $ref,{{FIELD_MAP}}
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);
{{GL_POST_CALL}}
    return $record;
}

function update{{PASCAL}}(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('{{TABLE}}', $ref)) {
        return ['error' => 'Record not found.'];
    }

    return supabase_patch(SB_API . '{{TABLE}}?ref=eq.' . urlencode($ref), [{{FIELD_MAP}}
        'updated_by' => current_user()['ref'] ?? null,
    ]);
}

function list{{PASCAL}}(): array {
    return supabase_get(SB_API . '{{TABLE}}?select=id,ref{{SELECT_COLS}}&order=id.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(list{{PASCAL}}());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(save{{PASCAL}}($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(update{{PASCAL}}($body));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('{{TABLE}}', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'        => $slug,
        '{{PASCAL}}'      => $pascal,
        '{{TABLE}}'       => $tableName,
        '{{FIELD_MAP}}'   => $fieldMap,
        '{{SELECT_COLS}}' => $selectCols,
        '{{GL_REQUIRE}}'  => $glRequire,
        '{{GL_POST_CALL}}'=> $glPostCall,
    ]);
}

function buildSearchPhp(string $slug, array $fields): string {
    $camel = lcfirst(toPascalCase($slug));
    $title = toTitleCase($slug);
    $headerCols = $fields ? "\n" . renderSearchHeaderCols($fields) : '';

    $tpl = <<<'EOT'
<?php /* modules/.../{{SLUG}}/{{SLUG}}_search.php — search modal; generated by Generate Module */ ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" crossorigin="anonymous" />

<div class="modal fade" id="{{CAMEL}}SearchModal" tabindex="-1" aria-labelledby="{{CAMEL}}SearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{CAMEL}}SearchModalLabel">{{TITLE}} Search</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
        <table id="{{CAMEL}}SearchTable" class="table table-bordered table-striped table-hover align-middle" style="width:100%;cursor:pointer">
          <thead class="table-light">
            <tr>
              <th>Ref</th>{{HEADER_COLS}}
            </tr>
          </thead>
        </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js" crossorigin="anonymous"></script>
<script src="/modules/.../{{SLUG}}/{{SLUG}}_search.js"></script>
EOT;

    // The literal ".../" placeholders above get resolved to the real section/subsection
    // path when this template is combined with the module's real folder; see the
    // str_replace('modules/.../', ...) call at the generate-action call site.
    return renderTemplate($tpl, [
        '{{SLUG}}'        => $slug,
        '{{CAMEL}}'       => $camel,
        '{{TITLE}}'       => $title,
        '{{HEADER_COLS}}' => $headerCols,
    ]);
}

function buildSearchJs(string $slug, array $fields, bool $needsFullFetch = false): string {
    $camel = lcfirst(toPascalCase($slug));
    $title = toTitleCase($slug);
    $colDefs = $fields ? "\n" . renderSearchColumnDefs($fields) : '';

    // header_detail modules with lines: the list endpoint doesn't embed lines
    // (they're only attached by action=get), so selecting a search row fetches
    // the full record first — same as work_order_entries_search.js.
    $clickHandler = $needsFullFetch
        ? <<<'JS'
  $('#{{CAMEL}}SearchTable tbody').on('click', 'tr', function () {
    const row = table.row(this).data();
    if (!row) return;
    axios.get('{{DATA_URL}}?action=get&ref=' + encodeURIComponent(row.ref))
      .then(res => {
        bootstrap.Modal.getInstance(document.getElementById('{{CAMEL}}SearchModal')).hide();
        document.dispatchEvent(new CustomEvent('{{SLUG}}-selected', { detail: res.data }));
      });
  });
JS
        : <<<'JS'
  $('#{{CAMEL}}SearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('{{CAMEL}}SearchModal')).hide();
    document.dispatchEvent(new CustomEvent('{{SLUG}}-selected', { detail: data }));
  });
JS;

    $tpl = <<<'EOT'
// {{SLUG}}_search.js — generated by Generate Module

$(document).ready(function () {

  const table = $('#{{CAMEL}}SearchTable').DataTable({
    ajax: { url: '{{DATA_URL}}?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },{{COL_DEFS}}
    ],
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    dom: '<"d-flex justify-content-between align-items-center mb-2"Bf>rtip',
    buttons: [
      { extend: 'copy',  className: 'btn btn-sm btn-secondary' },
      { extend: 'excel', className: 'btn btn-sm btn-success',  text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel' },
      { extend: 'csv',   className: 'btn btn-sm btn-info',     text: '<i class="bi bi-filetype-csv me-1"></i>CSV' },
      { extend: 'pdf',   className: 'btn btn-sm btn-danger',   text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF' },
      { extend: 'print', className: 'btn btn-sm btn-secondary' },
    ],
    language: {
      search: 'Global search:',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ {{TITLE}} records',
    },
  });

{{CLICK_HANDLER}}
  $('#{{CAMEL}}SearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'          => $slug,
        '{{CAMEL}}'         => $camel,
        '{{TITLE}}'         => $title,
        '{{COL_DEFS}}'      => $colDefs,
        '{{CLICK_HANDLER}}' => renderTemplate($clickHandler, ['{{CAMEL}}' => $camel, '{{SLUG}}' => $slug, '{{DATA_URL}}' => "{$slug}_data.php"]),
        // filled in by caller once the real folder path is known
        '{{DATA_URL}}' => "{$slug}_data.php",
    ]);
}

function buildPrintPhp(string $slug, string $moduleName, array $fields): string {
    $title = toTitleCase($slug);
    $safeName = htmlspecialchars($moduleName, ENT_QUOTES);
    $printRows = $fields ? "\n" . renderPrintRows($fields) : '';

    $tpl = <<<'EOT'
<?php
// {{SLUG}}_print.php — generated by Generate Module

require_once __DIR__ . '/../../../../server/supabase.php';

$url = SUPABASE_URL . SB_API . 'sys_company_info?select=name,address,phone,email&limit=1';
$ch  = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Accept: application/json',
]);
$rows    = json_decode(curl_exec($ch), true) ?? [];
$company = $rows[0] ?? [];

$ref = htmlspecialchars($_GET['ref'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>{{MODULE_NAME}} — <?= $ref ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #000; background: #f4f4f4; }
    .page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 16mm 18mm; display: flex; flex-direction: column; box-shadow: 0 0 12px rgba(0,0,0,0.15); }
    .letterhead { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; border-bottom: 3px solid #1a1a2e; margin-bottom: 24px; }
    .letterhead .company-name { font-size: 22px; font-weight: 700; color: #1a1a2e; }
    .letterhead .company-contact { text-align: right; font-size: 11px; color: #444; line-height: 1.7; }
    .doc-title { text-align: center; margin-bottom: 24px; }
    .doc-title h2 { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #1a1a2e; border-bottom: 1px solid #ccc; display: inline-block; padding-bottom: 4px; }
    .doc-title .ref { font-size: 12px; color: #555; margin-top: 4px; }
    .details { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
    .details tr:nth-child(even) td { background: #f9f9f9; }
    .details th { width: 38%; padding: 9px 14px; text-align: left; font-weight: 600; font-size: 12px; color: #333; border: 1px solid #ddd; background: #f0f0f0; }
    .details td { padding: 9px 14px; border: 1px solid #ddd; font-size: 12px; color: #111; }
    .signatures { display: flex; justify-content: space-between; margin-top: auto; padding-top: 40px; }
    .sig-block { text-align: center; width: 40%; }
    .sig-block .sig-line { border-top: 1px solid #555; margin-bottom: 6px; }
    .sig-block .sig-label { font-size: 11px; color: #555; }
    .footer { margin-top: 32px; padding-top: 10px; border-top: 1px solid #ccc; text-align: center; font-size: 10px; color: #777; line-height: 1.8; }
    .actions { text-align: center; padding: 12px; }
    .actions button { padding: 7px 20px; font-size: 13px; cursor: pointer; margin: 0 4px; border: 1px solid #999; border-radius: 4px; background: #f5f5f5; }
    .actions button.btn-print { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
    @media print { body { background: #fff; } .actions { display: none; } .page { margin: 0; box-shadow: none; width: 100%; padding: 12mm 14mm; } }
  </style>
</head>
<body>
  <div class="actions">
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    <button onclick="window.close()">Close</button>
  </div>
  <div class="page">
    <div class="letterhead">
      <div><div class="company-name"><?= htmlspecialchars($company['name'] ?? '') ?></div></div>
      <div class="company-contact">
        <?php if (!empty($company['address'])): ?><?= nl2br(htmlspecialchars($company['address'])) ?><br><?php endif; ?>
        <?php if (!empty($company['phone'])): ?>Tel: <?= htmlspecialchars($company['phone']) ?><br><?php endif; ?>
        <?php if (!empty($company['email'])): ?><?= htmlspecialchars($company['email']) ?><?php endif; ?>
      </div>
    </div>
    <div class="doc-title">
      <h2>{{MODULE_NAME}}</h2>
      <div class="ref">Ref: <?= $ref ?></div>
    </div>
    <table class="details">
      <tr><th>Reference No.</th><td><?= $ref ?></td></tr>{{PRINT_ROWS}}
    </table>
    <div class="signatures">
      <div class="sig-block"><div class="sig-line"></div><div class="sig-label">Prepared By</div></div>
      <div class="sig-block"><div class="sig-line"></div><div class="sig-label">Authorized By</div></div>
    </div>
    <div class="footer">
      <?= htmlspecialchars($company['name'] ?? '') ?>
      <?php if (!empty($company['address'])): ?> &mdash; <?= htmlspecialchars($company['address']) ?><?php endif; ?>
      <?php if (!empty($company['phone'])): ?> &mdash; <?= htmlspecialchars($company['phone']) ?><?php endif; ?>
      <?php if (!empty($company['email'])): ?> &mdash; <?= htmlspecialchars($company['email']) ?><?php endif; ?>
    </div>
  </div>
</body>
</html>
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'        => $slug,
        '{{MODULE_NAME}}' => $safeName,
        '{{PRINT_ROWS}}'  => $printRows,
    ]);
}

// ============================================================================
// module_type = 'report' — scaffold-only (date range + Run + Excel/Print),
// modeled on modules/reports/general_reports/fuel_usage_report/. 3 files,
// no business table, no number series.
// ============================================================================

function buildReportPhp(string $slug, string $sectionFolder, string $subsectionFolder): string {
    $tpl = <<<'EOT'
<?php /* modules/{{SECTION_FOLDER}}/{{SUBSECTION_FOLDER}}/{{SLUG}}/{{SLUG}}.php — report scaffold, generated by Generate Module */ ?>

<div id="{{KEBAB}}-app" v-cloak>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto">
          <label class="form-label mb-1">Date From</label>
          <input type="date" class="form-control form-control-sm" v-model="dateFrom" />
        </div>
        <div class="col-auto">
          <label class="form-label mb-1">Date To</label>
          <input type="date" class="form-control form-control-sm" v-model="dateTo" />
        </div>
        <div class="col-auto">
          <button class="btn btn-primary btn-sm" @click="load" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>Run Report
          </button>
        </div>
        <div class="col-auto ms-auto">
          <div class="report-export-btns d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-success" onclick="ReportUtils.exportExcel()">
              <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="ReportUtils.printReport()">
              <i class="bi bi-printer me-1"></i>Print / PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="card" v-if="rows.length || ran">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered table-hover mb-0">
        <thead class="table-dark">
          <!-- TODO: add real result columns here -->
          <tr><th>Ref</th></tr>
        </thead>
        <tbody>
          <!-- TODO: bind real result-row columns here -->
          <tr v-for="r in rows"><td>{{ r.ref }}</td></tr>
          <tr v-if="!rows.length"><td class="text-center text-muted py-3">No data for the selected period.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="alert alert-danger mt-2" v-if="error">{{ error }}</div>
</div>
<script src="/modules/{{SECTION_FOLDER}}/{{SUBSECTION_FOLDER}}/{{SLUG}}/{{SLUG}}.js"></script>
EOT;

    return renderTemplate($tpl, [
        '{{SECTION_FOLDER}}'    => $sectionFolder,
        '{{SUBSECTION_FOLDER}}' => $subsectionFolder,
        '{{SLUG}}'              => $slug,
        '{{KEBAB}}'             => toKebabCase($slug),
    ]);
}

function buildReportJs(string $slug, string $sectionFolder, string $subsectionFolder): string {
    $tpl = <<<'EOT'
// {{SLUG}}.js — report scaffold, generated by Generate Module

const { createApp } = Vue;

createApp({
  data() {
    return {
      dateFrom: '',
      dateTo:   '',
      rows:     [],
      ran:      false,
      loading:  false,
      error:    '',
    };
  },

  mounted() {
    const today = new Date();
    this.dateTo   = today.toISOString().slice(0, 10);
    this.dateFrom = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10);
  },

  methods: {
    load() {
      this.loading = true;
      this.error   = '';
      axios.get('{{DATA_DIR}}/{{SLUG}}_data.php', { params: { action: 'run', date_from: this.dateFrom, date_to: this.dateTo } })
        .then(res => { this.rows = res.data; this.ran = true; })
        .catch(err => { this.error = 'Failed to run report.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#{{KEBAB}}-app');
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'     => $slug,
        '{{KEBAB}}'    => toKebabCase($slug),
        '{{DATA_DIR}}' => "/modules/{$sectionFolder}/{$subsectionFolder}/{$slug}",
    ]);
}

function buildReportDataPhp(string $slug): string {
    $pascal = toPascalCase($slug);

    $tpl = <<<'EOT'
<?php
// {{SLUG}}_data.php — report scaffold, generated by Generate Module
//
// TODO: replace run{{PASCAL}}() below with the report's real aggregation
// query. See modules/reports/general_reports/fuel_usage_report/
// fuel_usage_report_data.php for a worked example, and docs/report_concepts.md
// for the report catalog/spec this ERP follows.

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

function run{{PASCAL}}(string $dateFrom, string $dateTo): array {
    // TODO: replace this stub with the report's real aggregation query.
    return [];
}

$action = $_GET['action'] ?? '';

if ($action === 'run') {
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo   = $_GET['date_to']   ?? '';
    echo json_encode(run{{PASCAL}}($dateFrom, $dateTo));

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'   => $slug,
        '{{PASCAL}}' => $pascal,
    ]);
}

// ============================================================================
// module_type = 'header_detail' with non-empty line_fields — header table +
// child line-items table, wholesale-replace-on-update, modeled directly on
// modules/operations/work_orders/work_order_entries/ (basic field types only,
// no FK/lookup pickers, no per-line GL splitting).
// ============================================================================

function buildHeaderDetailPhp(string $slug, string $sectionFolder, string $subsectionFolder, array $fields, array $lineFields, ?array $gl = null): string {
    $kebab = toKebabCase($slug);
    $camel = lcfirst(toPascalCase($slug));
    $title = toTitleCase($slug);

    $fieldsColumn = $fields
        ? "\n          <div class=\"col-md-6\">\n    <form ref=\"form\">\n" . renderFormFieldsBlock($fields, $kebab) . "\n    </form>\n          </div>"
        : '';

    $glToggle  = $gl ? "\n" . renderGlToggleSwitch() : '';
    $jePreview = $gl ? "\n" . renderJePreviewBlock($gl['lines']) : '';
    $glCss     = $gl ? "\n" . jePreviewCss() : '';

    $tpl = <<<'EOT'
<?php /* modules/{{SECTION_FOLDER}}/{{SUBSECTION_FOLDER}}/{{SLUG}}/{{SLUG}}.php — generated by Generate Module */ ?>

<div id="{{KEBAB}}-app" class="row g-4">
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#{{CAMEL}}SearchModal">Search</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>{{GL_TOGGLE}}
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>
{{JE_PREVIEW}}
        <div class="row mt-2 g-4">
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="{{KEBAB}}-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="{{KEBAB}}-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
          </div>{{FIELDS_COLUMN}}
        </div>

        <hr class="my-4" />
        <h6 class="mb-3">Lines</h6>
        <div class="table-responsive mb-2">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:40px">#</th>
{{LINE_HEADER_CELLS}}
                <th style="width:50px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, i) in form.lines" :key="i">
                <td class="text-center text-muted">{{ i + 1 }}</td>
{{LINE_CELLS}}
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(i)" :disabled="form.lines.length <= 1" title="Remove line">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mb-2" @click="addLine">
          <i class="bi bi-plus-lg me-1"></i>Add Line
        </button>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">{{TITLE}} saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/{{SLUG}}_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/{{SECTION_FOLDER}}/{{SUBSECTION_FOLDER}}/{{SLUG}}/{{SLUG}}.js"></script>{{GL_CSS}}
EOT;

    return renderTemplate($tpl, [
        '{{SECTION_FOLDER}}'    => $sectionFolder,
        '{{SUBSECTION_FOLDER}}' => $subsectionFolder,
        '{{SLUG}}'              => $slug,
        '{{KEBAB}}'             => $kebab,
        '{{CAMEL}}'             => $camel,
        '{{TITLE}}'             => $title,
        '{{FIELDS_COLUMN}}'     => $fieldsColumn,
        '{{GL_TOGGLE}}'         => $glToggle,
        '{{JE_PREVIEW}}'        => $jePreview,
        '{{GL_CSS}}'            => $glCss,
        '{{LINE_HEADER_CELLS}}' => renderLineHeaderCells($lineFields),
        '{{LINE_CELLS}}'        => renderLineCells($lineFields),
    ]);
}

function buildHeaderDetailJs(string $slug, string $sectionFolder, string $subsectionFolder, array $fields, array $lineFields, ?array $gl = null): string {
    $kebab   = toKebabCase($slug);
    $dataDir = "/modules/{$sectionFolder}/{$subsectionFolder}/{$slug}";

    $jsDefaults = $fields ? "\n" . renderJsDefaults($fields) : '';
    $isDirty    = renderIsDirtyExpr($fields);
    $resetLines = $fields ? "\n" . renderResetLines($fields) : '';
    $loadLines  = $fields ? "\n" . renderLoadLines($fields) : '';
    $printLines = $fields ? "\n" . renderPrintParamLines($fields) : '';

    $glData     = $gl ? "\n      checkGL: true," : '';
    $glComputed = $gl ? "\n    jeTotalAmount() {\n      const v = parseFloat(this.form.{$gl['amount_field']});\n      return isNaN(v) || v <= 0 ? 0 : v;\n    }," : '';
    $glMethods  = $gl ? "\n    fmtAmount(v) {\n      return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });\n    },\n" : '';

    $tpl = <<<'EOT'
// {{SLUG}}.js — generated by Generate Module

const { createApp } = Vue;

const emptyLine = () => ({
{{LINE_JS_DEFAULTS}}
});

const lineHasData = (l) => ({{LINE_HAS_DATA_EXPR}});

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading: false,
      saving:  false,
      saved:   false,
      error:   '',
      isExisting: false,{{GL_DATA}}
      form: {
        ref: '',{{JS_DEFAULTS}}
        lines: [emptyLine()],
      },
    };
  },

  computed: {
    isDirty() {
      return (
        {{IS_DIRTY_EXPR}} ||
        this.form.lines.some(lineHasData)
      );
    },{{GL_COMPUTED}}
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('{{SLUG}}-selected', (e) => this.loadRecord(e.detail));
  },

  methods: {{{GL_METHODS}}
    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    addLine()     { this.form.lines.push(emptyLine()); },
    removeLine(i) { if (this.form.lines.length > 1) this.form.lines.splice(i, 1); },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('{{DATA_DIR}}/{{SLUG}}_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref: this.form.ref,{{PRINT_PARAMS}}
          });
          window.open('{{DATA_DIR}}/{{SLUG}}_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.saved = false;
      this.error = '';
      this.isExisting = false;{{RESET_LINES}}
      this.form.lines = [emptyLine()];
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.$refs.form && !this.$refs.form.reportValidity()) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';
      const payload = { ...this.form, lines: this.form.lines.filter(lineHasData) };

      const proceed = (action) => {
        axios.post('{{DATA_DIR}}/{{SLUG}}_data.php?action=' + action, payload)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('{{DATA_DIR}}/{{SLUG}}_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
            proceed('update');
          })
          .catch(err => { this.error = 'Failed to verify record.'; console.error(err); this.saving = false; });
      } else {
        proceed('save');
      }
    },

    loadRecord(data) {
      this.form.ref = data.ref;{{LOAD_LINES}}
      this.form.lines = (data.lines && data.lines.length)
        ? data.lines.map(l => ({
{{LINE_LOAD_MAP}}
          }))
        : [emptyLine()];
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },
  },
}).mount('#{{KEBAB}}-app');
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'             => $slug,
        '{{KEBAB}}'            => $kebab,
        '{{DATA_DIR}}'         => $dataDir,
        '{{JS_DEFAULTS}}'      => $jsDefaults,
        '{{IS_DIRTY_EXPR}}'    => $isDirty,
        '{{RESET_LINES}}'      => $resetLines,
        '{{LOAD_LINES}}'       => $loadLines,
        '{{PRINT_PARAMS}}'     => $printLines,
        '{{GL_DATA}}'          => $glData,
        '{{GL_COMPUTED}}'      => $glComputed,
        '{{GL_METHODS}}'       => $glMethods,
        '{{LINE_JS_DEFAULTS}}' => renderLineJsDefaults($lineFields),
        '{{LINE_HAS_DATA_EXPR}}' => renderLineHasDataExpr($lineFields),
        '{{LINE_LOAD_MAP}}'    => renderLineLoadMap($lineFields),
    ]);
}

function buildHeaderDetailDataPhp(
    string $slug, string $tableName, string $lineTableName, string $parentFkColumn,
    array $fields, array $lineFields, ?array $gl = null
): string {
    $pascal = toPascalCase($slug);
    $fieldMap   = $fields ? "\n" . renderDataPhpMapLines($fields) : '';
    $selectCols = renderListSelectCols($fields);
    $lineSelectCols = renderListSelectCols($lineFields);

    $glRequire  = $gl ? "\nrequire_once __DIR__ . '/../../../../server/accounting/journal_engine.php';" : '';
    $glPostCall = $gl ? renderGlPostCallPhp($slug, $fields, $gl) : '';

    $tpl = <<<'EOT'
<?php
// {{SLUG}}_data.php — generated by Generate Module

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';{{GL_REQUIRE}}

header('Content-Type: application/json');

// {{LINE_TABLE}} has no module or number series of its own (by design — it's
// wired into this screen, not a standalone module); its refs are derived from
// the parent ref rather than consumed from sys_tms_module_number_series.
function save{{PASCAL}}Lines(string $parentRef, array $lines): void {
    $lineNo = 0;
    foreach ($lines as $line) {
        $hasLineData = {{LINE_HAS_DATA_PHP_EXPR}};
        if (!$hasLineData) continue;

        $lineNo++;

        supabase_post(SB_API . '{{LINE_TABLE}}', [
            'ref' => $parentRef . '-L' . $lineNo,
            '{{PARENT_FK}}' => $parentRef,
            'line_no' => $lineNo,{{LINE_FIELD_MAP}}
            'created_by' => current_user()['ref'] ?? null,
            'updated_by' => current_user()['ref'] ?? null,
        ]);
    }
}

function save{{PASCAL}}(array $data): array {
    $ref = consumeNextReference('{{SLUG}}');

    $header = supabase_post(SB_API . '{{TABLE}}', [
        'ref' => $ref,{{FIELD_MAP}}
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    if (!empty($header['ref'])) {
        save{{PASCAL}}Lines($header['ref'], $data['lines'] ?? []);
    }
{{GL_POST_CALL}}
    return $header;
}

function update{{PASCAL}}(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('{{TABLE}}', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $header = supabase_patch(SB_API . '{{TABLE}}?ref=eq.' . urlencode($ref), [{{FIELD_MAP}}
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    // Replace the line set wholesale: delete every existing child row for this
    // record, then reinsert the current set fresh — avoids diffing individual
    // lines, same approach work_order_entries_data.php uses.
    supabase_delete(SB_API . '{{LINE_TABLE}}?{{PARENT_FK}}=eq.' . urlencode($ref));
    save{{PASCAL}}Lines($ref, $data['lines'] ?? []);

    return $header;
}

function list{{PASCAL}}(): array {
    return supabase_get(SB_API . '{{TABLE}}?select=id,ref{{SELECT_COLS}}&order=id.asc');
}

function get{{PASCAL}}WithLines(string $ref): array {
    $headers = supabase_get(SB_API . '{{TABLE}}?select=ref{{SELECT_COLS}}&ref=eq.' . urlencode($ref) . '&limit=1');
    if (empty($headers)) return [];
    $record = $headers[0];

    $record['lines'] = supabase_get(SB_API . '{{LINE_TABLE}}?select=ref,line_no{{LINE_SELECT_COLS}}&{{PARENT_FK}}=eq.' . urlencode($ref) . '&order=line_no.asc');

    return $record;
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(list{{PASCAL}}());

} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(save{{PASCAL}}($body));

} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(update{{PASCAL}}($body));

} elseif ($action === 'get') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(get{{PASCAL}}WithLines($ref));

} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('{{TABLE}}', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
EOT;

    return renderTemplate($tpl, [
        '{{SLUG}}'           => $slug,
        '{{PASCAL}}'         => $pascal,
        '{{TABLE}}'          => $tableName,
        '{{LINE_TABLE}}'     => $lineTableName,
        '{{PARENT_FK}}'      => $parentFkColumn,
        '{{FIELD_MAP}}'      => $fieldMap,
        '{{SELECT_COLS}}'    => $selectCols,
        '{{LINE_SELECT_COLS}}' => $lineSelectCols,
        '{{LINE_FIELD_MAP}}' => renderLineDataPhpMapLines($lineFields) ? "\n" . renderLineDataPhpMapLines($lineFields) : '',
        '{{LINE_HAS_DATA_PHP_EXPR}}' => renderLineHasDataPhpExpr($lineFields),
        '{{GL_REQUIRE}}'     => $glRequire,
        '{{GL_POST_CALL}}'   => $glPostCall,
    ]);
}

// ============================================================================
// Orchestration — the single entry point both the HTTP endpoint
// (module_generator_data.php) and the CLI script (bin/generate_module.php)
// call. Returns ['success' => true, ...] or ['error' => string, 'status' => int]
// — never calls http_response_code()/exit itself, so it's safe from a CLI
// context with no HTTP response in play.
// ============================================================================

function generateModule(array $body): array {
    $moduleType = trim($body['module_type'] ?? 'entry') ?: 'entry';
    if (!in_array($moduleType, MODULE_TYPES, true)) {
        return ['error' => 'Invalid module type.', 'status' => 422];
    }
    $isReport = $moduleType === 'report';

    $sectionRef    = trim($body['section_ref'] ?? '');
    $subsectionRef = trim($body['subsection_ref'] ?? '');
    $moduleName    = trim($body['module_name'] ?? '');

    if ($sectionRef === '' || $subsectionRef === '') {
        return ['error' => 'Section and subsection are required.', 'status' => 422];
    }
    if (mb_strlen($moduleName) < 3 || mb_strlen($moduleName) > 100) {
        return ['error' => 'Module name must be 3–100 characters.', 'status' => 422];
    }

    $tableName = '';
    $refPrefix = '';
    if (!$isReport) {
        $tableName = trim($body['table_name'] ?? '');
        $refPrefix = strtoupper(trim($body['ref_prefix'] ?? ''));
        if (!isValidTableName($tableName)) {
            return ['error' => "Table name must look like 'm_your_table' (lowercase letters, numbers, underscores).", 'status' => 422];
        }
        if (!isValidRefPrefix($refPrefix)) {
            return ['error' => 'Reference prefix must be 2–6 letters/numbers.', 'status' => 422];
        }
    }

    try {
        $fields = $isReport ? [] : validateFields($body['fields'] ?? []);
    } catch (RuntimeException $e) {
        return ['error' => $e->getMessage(), 'status' => 422];
    }

    $lineFields = [];
    if ($moduleType === 'header_detail') {
        try {
            $lineFields = validateFields($body['line_fields'] ?? []);
        } catch (RuntimeException $e) {
            return ['error' => $e->getMessage(), 'status' => 422];
        }
    }

    $createsJournalEntry = !empty($body['creates_journal_entry']);
    if ($createsJournalEntry && $isReport) {
        return ['error' => 'Report modules cannot post to the general ledger.', 'status' => 422];
    }

    $gl = null;
    if ($createsJournalEntry) {
        $amountField = trim($body['gl_amount_field'] ?? '');
        $amountFieldDef = null;
        foreach ($fields as $f) {
            if ($f['column'] === $amountField) { $amountFieldDef = $f; break; }
        }
        if (!$amountFieldDef || !in_array($amountFieldDef['type'], ['number', 'decimal'], true)) {
            return ['error' => 'GL posting requires selecting one of the header number/decimal fields as the amount field.', 'status' => 422];
        }
        try {
            $glLines = validateGlLines($body['gl_lines'] ?? []);
        } catch (RuntimeException $e) {
            return ['error' => $e->getMessage(), 'status' => 422];
        }
        $gl = ['amount_field' => $amountField, 'lines' => $glLines];
    }

    // Resolve section/subsection server-side — never trust client-sent folder names.
    $section = supabase_get(SB_API . 'sys_tms_sections?select=ref,name,folder&ref=eq.' . urlencode($sectionRef) . '&limit=1')[0] ?? null;
    if (!$section) return ['error' => 'Unknown section.', 'status' => 422];

    $subsection = supabase_get(SB_API . 'sys_tms_subsections?select=ref,name,folder,section_ref&ref=eq.' . urlencode($subsectionRef) . '&limit=1')[0] ?? null;
    if (!$subsection) return ['error' => 'Unknown subsection.', 'status' => 422];
    if ($subsection['section_ref'] !== $sectionRef) return ['error' => 'Subsection does not belong to the selected section.', 'status' => 422];

    $slug = slugifyModuleName($moduleName);
    if (!isValidSlug($slug)) {
        return ['error' => 'Module name must produce a valid folder name (letters, numbers, underscores; at least 3 characters).', 'status' => 422];
    }

    // Global folder-name uniqueness (folder is treated as globally unique everywhere
    // else in this codebase — guardModuleWrite(), rbac.php, number_series.php — not
    // scoped per subsection).
    $existing = supabase_get(SB_API . 'sys_tms_modules?select=id&folder=eq.' . urlencode($slug) . '&limit=1');
    if (!empty($existing)) {
        return ['error' => "A module with folder '{$slug}' already exists.", 'status' => 409];
    }
    if (!$isReport && tableExists($tableName)) {
        return ['error' => "Table '{$tableName}' already exists.", 'status' => 409];
    }

    // Header_detail child table — only when line_fields is non-empty. Empty
    // line_fields generates identically to 'entry' (no child table).
    $lineTableName  = null;
    $parentFkColumn = null;
    if ($moduleType === 'header_detail' && $lineFields) {
        $baseTable = preg_replace('/^m_/', '', $tableName);
        $lineTableName  = "t_{$baseTable}_lines";
        $parentFkColumn = "{$slug}_ref";

        if (!isValidLineTableName($lineTableName)) {
            return ['error' => 'Generated line table name is invalid; choose a shorter table name.', 'status' => 422];
        }
        if (!isValidColumnName($parentFkColumn)) {
            return ['error' => 'Generated parent reference column name is invalid; choose a shorter module name.', 'status' => 422];
        }
        if (tableExists($lineTableName)) {
            return ['error' => "Table '{$lineTableName}' already exists.", 'status' => 409];
        }
        foreach ($lineFields as $lf) {
            if ($lf['column'] === $parentFkColumn || $lf['column'] === 'line_no') {
                return ['error' => "Line field column '{$lf['column']}' collides with a reserved line-table column.", 'status' => 422];
            }
        }
    }

    // Filesystem containment + existence check.
    $modulesRoot = realpath(MODULES_ROOT);
    $parentDir   = realpath($modulesRoot . '/' . $section['folder'] . '/' . $subsection['folder']);
    if (!$parentDir || !str_starts_with($parentDir, $modulesRoot)) {
        return ['error' => 'Could not resolve the target subsection folder on disk.', 'status' => 500];
    }
    $targetDir = $parentDir . '/' . $slug;
    if (is_dir($targetDir)) {
        return ['error' => "Folder already exists on disk: modules/{$section['folder']}/{$subsection['folder']}/{$slug}", 'status' => 409];
    }

    // sys_tms_modules has Row-Level Security enabled with only a SELECT policy for
    // anon (confirmed live — no INSERT/UPDATE policy exists, direct supabase_post()
    // is rejected by Postgres itself). Registration goes through register_tms_module(),
    // a SECURITY DEFINER RPC (docs/migrations/module_generator_rpc.sql, extended by
    // module_generator_advanced_rpc.sql for module_type/creates_journal_entry) that
    // does the insert + ref derivation atomically.
    $maxSortRow = supabase_get(SB_API . 'sys_tms_modules?select=sort_order&order=sort_order.desc&limit=1');
    $nextSort   = (int) ($maxSortRow[0]['sort_order'] ?? 0) + 1;

    $registered = supabase_post(SB_API . 'rpc/register_tms_module', [
        'p_subsection_ref'        => $subsectionRef,
        'p_folder'                => $slug,
        'p_name'                  => $moduleName,
        'p_sort_order'            => $nextSort,
        'p_module_type'           => $moduleType,
        'p_creates_journal_entry' => $createsJournalEntry,
    ]);
    $moduleId  = $registered['id']  ?? null;
    $moduleRef = $registered['ref'] ?? null;
    if (!$moduleId || !$moduleRef) {
        return ['error' => 'Failed to register module.', 'status' => 500];
    }

    // Business table(s). m_*/t_* tables have RLS disabled with open anon/authenticated
    // grants (confirmed live), but PostgREST can't run DDL — table creation still
    // needs its own SECURITY DEFINER RPCs.
    if (!$isReport) {
        try {
            callVoidRpc('create_module_table', [
                'p_table'   => $tableName,
                'p_columns' => array_map(fn($f) => ['name' => $f['column'], 'type' => $f['type']], $fields),
            ]);
        } catch (\Throwable $e) {
            rollbackGeneration((int) $moduleId, null, null, null);
            return ['error' => 'Failed to create the business table: ' . $e->getMessage(), 'status' => 500];
        }

        if ($lineTableName) {
            try {
                callVoidRpc('create_module_line_table', [
                    'p_table'            => $lineTableName,
                    'p_parent_fk_column' => $parentFkColumn,
                    'p_columns'          => array_map(fn($f) => ['name' => $f['column'], 'type' => $f['type']], $lineFields),
                ]);
            } catch (\Throwable $e) {
                rollbackGeneration((int) $moduleId, $tableName, null, null);
                return ['error' => 'Failed to create the line-items table: ' . $e->getMessage(), 'status' => 500];
            }
        }
    }

    // sys_tms_module_number_series has open grants (confirmed live) — plain insert,
    // no RPC needed. Reports have no ref sequence (no Reference No. field).
    $seriesId = null;
    if (!$isReport) {
        try {
            $placeholderRef = 'PENDING-' . bin2hex(random_bytes(8));
            $seriesRow = supabase_post(SB_API . 'sys_tms_module_number_series', [
                'ref'        => $placeholderRef,
                'module_ref' => $moduleRef,
                'prefix'     => $refPrefix,
            ]);
            $seriesId = $seriesRow['id'] ?? null;
            if (!$seriesId) throw new RuntimeException('insert did not return an id');

            $canonicalRef = 'NS-' . str_pad((string) $seriesId, 6, '0', STR_PAD_LEFT);
            supabase_patch(SB_API . 'sys_tms_module_number_series?id=eq.' . $seriesId, ['ref' => $canonicalRef]);
        } catch (\Throwable $e) {
            rollbackGeneration((int) $moduleId, $tableName, $seriesId ? (int) $seriesId : null, null, $lineTableName);
            return ['error' => 'Failed to create the number series: ' . $e->getMessage(), 'status' => 500];
        }
    }

    // Admin permission grant — reports can view/print but not create/edit (there's
    // nothing to save on a report screen).
    $permId = null;
    try {
        $adminRole = supabase_get(SB_API . 'sys_roles?select=ref&name=eq.Admin&limit=1')[0] ?? null;
        if (!$adminRole) throw new RuntimeException("no 'Admin' role found");

        $permRow = supabase_post(SB_API . 'sys_role_module_permissions', [
            'role_ref'   => $adminRole['ref'],
            'module_ref' => $moduleRef,
            'can_view'   => true,
            'can_create' => !$isReport,
            'can_edit'   => !$isReport,
            'can_print'  => true,
        ]);
        $permId = $permRow['id'] ?? null;
        if (!$permId) throw new RuntimeException('insert did not return an id');
    } catch (\Throwable $e) {
        rollbackGeneration((int) $moduleId, $tableName, (int) $seriesId, $permId ? (int) $permId : null, $lineTableName);
        return ['error' => 'Failed to grant Admin permissions: ' . $e->getMessage(), 'status' => 500];
    }

    // Seed m_posting_rules before writing files, so a failure here still rolls
    // back cleanly (the file-write try/catch below also cleans up posting rules).
    if ($gl) {
        try {
            seedPostingRules($slug, $gl['lines']);
        } catch (\Throwable $e) {
            rollbackGeneration((int) $moduleId, $tableName, (int) $seriesId, (int) $permId, $lineTableName);
            return ['error' => 'Failed to seed posting rules: ' . $e->getMessage(), 'status' => 500];
        }
    }

    // Write files. Any failure rolls back the directory, table(s), number series,
    // permission row, posting rules, and the module registration.
    try {
        // @-suppressed: mkdir()/file_put_contents() emit raw PHP warnings on failure,
        // which would otherwise leak into this JSON API response body. The return-value
        // check still catches failures.
        if (!@mkdir($targetDir, 0755, true)) {
            throw new RuntimeException('mkdir failed');
        }

        if ($isReport) {
            $files = [
                "{$slug}.php"      => buildReportPhp($slug, $section['folder'], $subsection['folder']),
                "{$slug}.js"       => buildReportJs($slug, $section['folder'], $subsection['folder']),
                "{$slug}_data.php" => buildReportDataPhp($slug),
            ];
        } elseif ($moduleType === 'header_detail' && $lineTableName) {
            $searchJs = buildSearchJs($slug, $fields, true);
            $searchJs = str_replace("{$slug}_data.php", "/modules/{$section['folder']}/{$subsection['folder']}/{$slug}/{$slug}_data.php", $searchJs);

            $searchPhp = buildSearchPhp($slug, $fields);
            $searchPhp = str_replace('modules/.../', "modules/{$section['folder']}/{$subsection['folder']}/", $searchPhp);

            $files = [
                "{$slug}.php"        => buildHeaderDetailPhp($slug, $section['folder'], $subsection['folder'], $fields, $lineFields, $gl),
                "{$slug}.js"         => buildHeaderDetailJs($slug, $section['folder'], $subsection['folder'], $fields, $lineFields, $gl),
                "{$slug}_data.php"   => buildHeaderDetailDataPhp($slug, $tableName, $lineTableName, $parentFkColumn, $fields, $lineFields, $gl),
                "{$slug}_search.php" => $searchPhp,
                "{$slug}_search.js"  => $searchJs,
                "{$slug}_print.php"  => buildPrintPhp($slug, $moduleName, $fields),
            ];
        } else {
            // 'entry', or 'header_detail' with no line_fields (identical output to 'entry').
            $searchJs = buildSearchJs($slug, $fields, false);
            $searchJs = str_replace("{$slug}_data.php", "/modules/{$section['folder']}/{$subsection['folder']}/{$slug}/{$slug}_data.php", $searchJs);

            $searchPhp = buildSearchPhp($slug, $fields);
            $searchPhp = str_replace('modules/.../', "modules/{$section['folder']}/{$subsection['folder']}/", $searchPhp);

            $files = [
                "{$slug}.php"        => buildModulePhp($slug, $section['folder'], $subsection['folder'], $fields, $gl),
                "{$slug}.js"         => buildModuleJs($slug, $section['folder'], $subsection['folder'], $fields, $gl),
                "{$slug}_data.php"   => buildModuleDataPhp($slug, $tableName, $fields, $gl),
                "{$slug}_search.php" => $searchPhp,
                "{$slug}_search.js"  => $searchJs,
                "{$slug}_print.php"  => buildPrintPhp($slug, $moduleName, $fields),
            ];
        }

        foreach ($files as $filename => $content) {
            if (@file_put_contents("{$targetDir}/{$filename}", $content) === false) {
                throw new RuntimeException("Failed to write {$filename}");
            }
        }
    } catch (\Throwable $e) {
        rrmdir($targetDir);
        rollbackGeneration((int) $moduleId, $isReport ? null : $tableName, $seriesId ? (int) $seriesId : null, (int) $permId, $lineTableName, $gl ? $slug : null);
        return ['error' => 'Failed to write module files; changes rolled back. (' . $e->getMessage() . ')', 'status' => 500];
    }

    $warnings = [];
    if (!$isReport && !$fields) {
        $warnings[] = "No business fields were added — this module only has the Reference No. field. Edit {$slug}.php / {$slug}.js / {$slug}_data.php to add more.";
    }

    return [
        'success'     => true,
        'ref'         => $moduleRef,
        'folder'      => $slug,
        'name'        => $moduleName,
        'module_type' => $moduleType,
        'table'       => $isReport ? null : $tableName,
        'line_table'  => $lineTableName,
        'ref_prefix'  => $isReport ? null : $refPrefix,
        'section'     => $section['name'],
        'subsection'  => $subsection['name'],
        'url'         => '/home.php?page=' . $slug,
        'warnings'    => $warnings,
    ];
}
