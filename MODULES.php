<?php
require_once __DIR__ . '/server/supabase.php';

$modules      = supabase_get(SB_API . 'sys_tms_modules?select=*&order=sort_order.asc');
$sections     = supabase_get(SB_API . 'sys_tms_sections?select=ref,name,web_color');
$subsections  = supabase_get(SB_API . 'sys_tms_subsections?select=ref,section_ref,name,color');
$numberSeries = supabase_get(SB_API . 'sys_tms_module_number_series?select=module_ref,prefix');

$sectionMap      = array_column($sections, null, 'ref');
$subsectionMap   = array_column($subsections, null, 'ref');
$numberSeriesMap = array_column($numberSeries, null, 'module_ref');

foreach ($modules as &$m) {
    $sub    = $subsectionMap[$m['subsection_ref']] ?? null;
    $sec    = $sectionMap[$sub['section_ref'] ?? null] ?? null;
    $series = $numberSeriesMap[$m['ref']] ?? null;

    $m['section_name']    = $sec['name'] ?? '';
    $m['section_color']   = $sec['web_color'] ?? 'dark';
    $m['subsection_name'] = $sub['name'] ?? '';
    $m['subsection_color'] = $sub['color'] ?? ($sec['web_color'] ?? 'secondary');
    $m['reference']       = $series['prefix'] ?? '';
    $m['flag']            = $series ? 1 : 0;
}
unset($m);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>sys_tms_modules — Ones n Zeros ERP</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f4f6f9; font-size: 0.875rem; }
    h1 { font-size: 1.25rem; font-weight: 700; }
    .section-header { background: #343a40; color: #fff; font-weight: 600; font-size: 0.8rem; letter-spacing: 0.06em; text-transform: uppercase; }
    .section-header td { padding: 0.55rem 0.75rem; }
    .subsection-header { background: #f1f3f5; color: #343a40; font-weight: 600; font-size: 0.78rem; letter-spacing: 0.03em; }
    .subsection-header td { padding: 0.4rem 0.75rem 0.4rem 2rem; border-left: 3px solid transparent; }
    table { font-size: 0.8rem; }
    th { background: #495057; color: #fff; white-space: nowrap; }
    td { vertical-align: middle; }
    .badge-flag-1 { background: #198754; }
    .badge-flag-0 { background: #6c757d; }
    .ref-code { font-family: monospace; font-weight: 600; color: #0d6efd; }
    .folder-path { font-family: monospace; color: #6c757d; font-size: 0.75rem; }
    .filter-bar { max-width: 320px; }
  </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h1 class="mb-0">sys_tms_modules</h1>
      <small class="text-muted">Supabase · oncenzeros-transport · <?= count($modules) ?> rows · loaded <?= date('d M Y H:i:s') ?></small>
    </div>
    <input type="text" id="filterInput" class="form-control form-control-sm filter-bar" placeholder="Filter modules…">
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover mb-0">
        <thead>
          <tr>
            <th style="width:45px">ID</th>
            <th>Section</th>
            <th>Subsection</th>
            <th style="width:70px">Sub Ref</th>
            <th>Module</th>
            <th style="width:90px">Ref</th>
            <th>Folder</th>
            <th style="width:55px">Prefix</th>
            <th style="width:55px">Flag</th>
            <th style="width:60px">Web</th>
            <th style="width:60px">App</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>

  <p class="text-muted mt-2" style="font-size:0.75rem">
    <i class="bi bi-info-circle"></i>
    <strong>flag</strong>: 1 = generates a reference number &nbsp;|&nbsp;
    <strong>web_status</strong> / <strong>app_status</strong>: 1 = active
  </p>
</div>

<script>
const rows = <?= json_encode(array_map(fn($r) => [
    (int)  $r['id'],                // 0
           $r['section_name'],      // 1
           $r['subsection_name'],   // 2
           $r['subsection_ref'],    // 3
           $r['name'],              // 4
           $r['ref'],               // 5
           $r['folder'],            // 6
           $r['reference'],         // 7
    (int)  $r['flag'],              // 8
           $r['web_status'],        // 9
           $r['app_status'],        // 10
           $r['section_color'],     // 11
           $r['subsection_color'],  // 12
], $modules)); ?>;

const COLS = 11;

function matches(r, filter) {
  if (!filter) return true;
  return `${r[0]} ${r[1]} ${r[2]} ${r[3]} ${r[4]} ${r[5]} ${r[6]} ${r[7]}`.toLowerCase().includes(filter);
}

function render(filter) {
  const tbody = document.getElementById('tableBody');
  tbody.innerHTML = '';
  let lastSection = null;
  let lastSubsection = null;
  let visibleCount = 0;

  for (const row of rows) {
    const [id, section, subsection, subRef, name, ref, folder, prefix, flag, webStatus, appStatus, sectionColor, subsectionColor] = row;

    if (!matches(row, filter)) continue;
    visibleCount++;

    if (section !== lastSection) {
      const sectionCount = rows.filter(r => r[1] === section && matches(r, filter)).length;
      const tr = document.createElement('tr');
      tr.className = 'section-header';
      tr.style.borderLeft = `4px solid var(--bs-${sectionColor})`;
      tr.innerHTML = `<td colspan="${COLS}"><i class="bi bi-collection me-2"></i>${section} <span class="fw-normal opacity-75">(${sectionCount})</span></td>`;
      tbody.appendChild(tr);
      lastSection = section;
      lastSubsection = null;
    }

    if (subsection && subsection !== lastSubsection) {
      const subCount = rows.filter(r => r[1] === section && r[2] === subsection && matches(r, filter)).length;
      const tr = document.createElement('tr');
      tr.className = 'subsection-header';
      tr.innerHTML = `<td colspan="${COLS}" style="border-left:3px solid var(--bs-${subsectionColor})"><i class="bi bi-folder me-2" style="color:var(--bs-${subsectionColor})"></i>${subsection} <span class="fw-normal text-muted">(${subCount})</span></td>`;
      tbody.appendChild(tr);
      lastSubsection = subsection;
    }

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-muted">${id}</td>
      <td>${section}</td>
      <td>${subsection ? `<span class="text-muted">${subsection}</span>` : '<span class="text-muted">—</span>'}</td>
      <td class="text-center"><span class="badge bg-secondary font-monospace" style="font-size:.72rem">${subRef || '—'}</span></td>
      <td>${name}</td>
      <td class="folder-path">${ref}</td>
      <td><code>${folder}</code></td>
      <td><span class="ref-code">${prefix}</span></td>
      <td><span class="badge badge-flag-${flag}">${flag === 1 ? 'Seq' : '—'}</span></td>
      <td class="text-center">${webStatus ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<span class="text-muted">—</span>'}</td>
      <td class="text-center">${appStatus ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<span class="text-muted">—</span>'}</td>
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

document.getElementById('filterInput').addEventListener('input', function() {
  render(this.value.trim().toLowerCase());
});
</script>
</body>
</html>
