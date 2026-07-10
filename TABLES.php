<?php
require_once __DIR__ . '/server/supabase.php';

// Read-only schema introspection — see docs/migrations/schema_introspection.sql.
// Returns every public-schema table with its live column definitions; nothing here
// is hardcoded, and the RPC function itself can only read information_schema, never
// row data from any application table.
function supabase_rpc(string $function): array {
    $ch = curl_init(SUPABASE_URL . '/rest/v1/rpc/' . $function);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: '               . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    $result = json_decode(curl_exec($ch), true);
    return is_array($result) ? $result : [];
}

$tables = supabase_rpc('get_schema_overview');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tables — Ones n Zeros ERP</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f4f6f9; font-size: 0.875rem; }
    h1 { font-size: 1.25rem; font-weight: 700; }
    .table-row { cursor: pointer; user-select: none; }
    .table-row:hover { background: #f1f3f5; }
    .table-row .bi-chevron-right { transition: transform 0.15s ease; }
    .table-row.expanded .bi-chevron-right { transform: rotate(90deg); }
    .col-def { font-size: 0.8rem; }
    .type-badge { font-family: monospace; font-size: 0.72rem; }
    .filter-bar { max-width: 320px; }
    .empty-note { font-size: 0.75rem; }
  </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h1 class="mb-0">Tables</h1>
      <small class="text-muted"><?= count($tables) ?> tables · loaded <?= date('d M Y H:i:s') ?></small>
    </div>
    <input type="text" id="filterInput" class="form-control form-control-sm filter-bar" placeholder="Filter tables…">
  </div>

  <?php if (empty($tables)): ?>
    <div class="alert alert-warning empty-note">
      No tables found — or <code>get_schema_overview()</code> hasn't been created yet.
      Run <code>docs/migrations/schema_introspection.sql</code> in the Supabase SQL editor first.
    </div>
  <?php else: ?>
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div id="tableList"></div>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
const tables = <?= json_encode($tables) ?>;

const listEl = document.getElementById('tableList');

function render(filter) {
  if (!listEl) return;
  listEl.innerHTML = '';
  const f = filter.toLowerCase();
  const visible = tables.filter(t => !f || t.table_name.toLowerCase().includes(f));

  if (visible.length === 0) {
    listEl.innerHTML = `<div class="text-center text-muted py-4">No tables match "<strong>${filter}</strong>"</div>`;
    return;
  }

  visible.forEach(t => {
    const row = document.createElement('div');
    row.className = 'table-row d-flex align-items-center px-3 py-2 border-bottom';
    row.innerHTML = `
      <i class="bi bi-chevron-right me-2 text-muted"></i>
      <i class="bi bi-table me-2 text-primary"></i>
      <span class="fw-semibold">${t.table_name}</span>
      <span class="text-muted ms-2">(${t.columns.length} columns)</span>
    `;

    const body = document.createElement('div');
    body.className = 'col-def px-3 pb-3';
    body.style.display = 'none';
    body.innerHTML = `
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>Column</th>
            <th>Type</th>
            <th>Nullable</th>
            <th>Default</th>
          </tr>
        </thead>
        <tbody>
          ${t.columns.map(c => `
            <tr>
              <td><code>${c.column_name}</code></td>
              <td><span class="badge text-bg-secondary type-badge">${c.data_type}</span></td>
              <td>${c.is_nullable === 'YES' ? '<span class="text-muted">yes</span>' : '<strong>no</strong>'}</td>
              <td class="text-muted">${c.column_default ?? '—'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    row.addEventListener('click', () => {
      const collapsed = body.style.display === 'none';
      body.style.display = collapsed ? '' : 'none';
      row.classList.toggle('expanded', collapsed);
    });

    listEl.appendChild(row);
    listEl.appendChild(body);
  });
}

render('');

const filterInput = document.getElementById('filterInput');
if (filterInput) {
  filterInput.addEventListener('input', function() {
    render(this.value.trim());
  });
}
</script>
</body>
</html>
