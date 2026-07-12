<?php
require_once __DIR__ . '/server/supabase.php';

// Nothing below is hardcoded to specific check names — the boolean check
// columns are discovered from whatever sys_module_checklist actually returns,
// so adding/renaming/removing a check column in the DB (see the "Module Audit
// Table (Supabase)" section of CLAUDE.md) changes this page with no code edit.
$rows = supabase_get(SB_API . 'sys_module_checklist?select=*&order=module_ref.asc');

$checkCols = [];
if (!empty($rows)) {
    foreach ($rows[0] as $col => $val) {
        if (is_bool($val)) {
            $checkCols[] = $col;
        }
    }
}

foreach ($rows as &$r) {
    $passed = 0;
    foreach ($checkCols as $c) {
        if (!empty($r[$c])) $passed++;
    }
    $r['_passed'] = $passed;
    $r['_total']  = count($checkCols);
}
unset($r);

function labelize(string $col): string {
    return ucwords(str_replace('_', ' ', $col));
}

// What must be true before ticking each check column — shown as a legend
// above the table so an auditor knows what "done" means for that column.
$checkCriteria = [
    'db_registered'        => 'Row exists and is active in sys_tms_modules (ref, folder, subsection_ref, sort order all set; subsection_ref resolves to a real row in sys_tms_subsections). Icon is inherited from the subsection, not stored per-module; route/component are unused legacy columns.',
    'folder_exists'        => "The module's physical folder exists under the correct category path in the ERP repo, matching its folder value.",
    'files_complete'       => "All files module_generator would scaffold exist: main .php, .js, _data.php, _search.php/_search.js, print, and (if header-detail) the report set.",
    'save_works'           => "Create/insert flow tested end-to-end; response JSON matches the persisted row; every field's DB column mapping verified, not just one column.",
    'update_works'         => "Edit/update flow tested end-to-end; partial updates don't null out untouched fields.",
    'search_works'         => "The module's _search.php returns correctly filtered/paginated results.",
    'gl_posting_support'   => 'If creates_journal_entry is true for this module, a GL-posting hook exists and produces a balanced journal entry.',
    'migration_exists'     => "A migration script creates/alters the module's table(s) on a fresh install, not just present in the dev DB.",
    'tests_fixture_exists' => '*.fixture.js exists under tests/modules/, every field declares its target DB column.',
    'tests_spec_complete'  => '*.spec.js loops over every fixture field for insert/update/search, not just one representative column.',
    'db_verified'          => 'Spec calls assertRecord(fixture.dbVerify, fixture.fields) against the actual DB row, not just the save response.',
    'ui_qa_passed'         => 'Passes the QA Checklist: assets load, sidebar/dropdowns work, icons render, layout holds on desktop/mobile, no overflow.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Module Checklist — Ones n Zeros ERP</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f4f6f9; font-size: 0.875rem; }
    h1 { font-size: 1.25rem; font-weight: 700; }
    table { font-size: 0.8rem; }
    th { background: #495057; color: #fff; white-space: nowrap; }
    td { vertical-align: middle; }
    th.check-col, td.check-col { text-align: center; width: 46px; }
    .ref-code { font-family: monospace; font-weight: 600; color: #0d6efd; }
    .path-text { color: #6c757d; font-size: 0.75rem; }
    .filter-bar { max-width: 320px; }
    .progress { width: 70px; height: 6px; }
    .notes-text { max-width: 220px; white-space: normal; font-size: 0.75rem; color: #495057; }
    .legend-table td { padding: 0.3rem 0.75rem; font-size: 0.75rem; border-bottom: 1px solid #f1f3f5; }
    .legend-col { width: 170px; font-weight: 600; color: #343a40; white-space: nowrap; }
  </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h1 class="mb-0">Module Checklist</h1>
      <small class="text-muted">Supabase · sys_module_checklist · <?= count($rows) ?> rows · loaded <?= date('d M Y H:i:s') ?></small>
    </div>
    <input type="text" id="filterInput" class="form-control form-control-sm filter-bar" placeholder="Filter modules…">
  </div>

  <?php if (!empty($checkCriteria)): ?>
  <div class="card shadow-sm mb-3">
    <div class="card-body p-0">
      <table class="table table-sm table-borderless mb-0 legend-table">
        <tbody>
          <?php foreach ($checkCols as $c): ?>
            <tr>
              <td class="legend-col"><?= htmlspecialchars(labelize($c)) ?></td>
              <td class="text-muted"><?= htmlspecialchars($checkCriteria[$c] ?? 'Mark done once verified.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
    <div class="alert alert-warning">No rows found in sys_module_checklist.</div>
  <?php else: ?>
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover mb-0">
        <thead>
          <tr>
            <th>Module</th>
            <th>Path</th>
            <th style="width:110px">Progress</th>
            <?php foreach ($checkCols as $c): ?>
              <th class="check-col" title="<?= htmlspecialchars(labelize($c)) ?>"><?= htmlspecialchars(labelize($c)) ?></th>
            <?php endforeach; ?>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
const rows = <?= json_encode($rows) ?>;
const checkCols = <?= json_encode($checkCols) ?>;
const COLS = 4 + checkCols.length;

function matches(r, filter) {
  if (!filter) return true;
  return `${r.module_ref ?? ''} ${r.module_name ?? ''} ${r.module_path ?? ''}`.toLowerCase().includes(filter);
}

function render(filter) {
  const tbody = document.getElementById('tableBody');
  if (!tbody) return;
  tbody.innerHTML = '';
  let visibleCount = 0;

  for (const r of rows) {
    if (!matches(r, filter)) continue;
    visibleCount++;

    const pct = r._total ? Math.round((r._passed / r._total) * 100) : 0;
    const barColor = pct === 100 ? 'bg-success' : pct === 0 ? 'bg-secondary' : 'bg-warning';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div class="fw-semibold">${r.module_name ?? ''}</div>
        <div class="ref-code">${r.module_ref ?? ''}</div>
      </td>
      <td class="path-text">${r.module_path ?? ''}</td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div class="progress"><div class="progress-bar ${barColor}" style="width:${pct}%"></div></div>
          <span class="text-muted" style="font-size:0.7rem">${r._passed}/${r._total}</span>
        </div>
      </td>
      ${checkCols.map(c => `
        <td class="check-col">${r[c]
          ? '<i class="bi bi-check-circle-fill text-success"></i>'
          : '<i class="bi bi-x-circle text-muted opacity-50"></i>'}
        </td>
      `).join('')}
      <td class="notes-text">${r.notes ? r.notes : '<span class="text-muted">—</span>'}</td>
    `;
    tbody.appendChild(tr);
  }

  if (visibleCount === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="${COLS}" class="text-center text-muted py-4">No modules match "<strong>${filter}</strong>"</td>`;
    tbody.appendChild(tr);
  }
}

render('');

const filterInput = document.getElementById('filterInput');
if (filterInput) {
  filterInput.addEventListener('input', function() {
    render(this.value.trim().toLowerCase());
  });
}
</script>
</body>
</html>
