<?php /* modules/finance/audit_trail/audit_trail.php — Finance Audit Trail viewer */ ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" crossorigin="anonymous" />

<div class="row g-4">

  <div class="col-12">
    <div class="card card-outline card-secondary mb-4">
      <div class="card-header d-flex align-items-center">
        <div class="card-title">Finance Audit Trail</div>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
          <i class="bi bi-question-circle me-1"></i>Help
        </button>
      </div>
      <div class="card-body">

        <!-- Filters -->
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-grid-3x3-gap"></i></span>
              <input type="text" id="aud-filter-module" class="form-control" placeholder="Filter by module…" />
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-lightning"></i></span>
              <input type="text" id="aud-filter-action" class="form-control" placeholder="Filter by action…" />
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" id="aud-filter-user" class="form-control" placeholder="Filter by user…" />
            </div>
          </div>
          <div class="col-md-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-hash"></i></span>
              <input type="text" id="aud-filter-ref" class="form-control" placeholder="Filter by record ref…" />
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table id="audTable" class="table table-bordered table-striped table-hover align-middle table-sm" style="width:100%">
            <thead class="table-dark">
              <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Record Ref</th>
                <th>Field</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Description</th>
              </tr>
            </thead>
          </table>
        </div>

      </div>
    </div>
  </div>

</div>

<!-- Event detail modal -->
<div class="modal fade" id="audDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-journal-text me-2"></i>Audit Event Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="aud-detail-body"></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
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

<script>
$(document).ready(function () {
  const actionColours = { create: 'success', update: 'primary', delete: 'danger', view: 'secondary', close: 'warning', reconcile: 'info' };

  const table = $('#audTable').DataTable({
    ajax: { url: '/modules/finance/compliance/audit_trail/audit_trail_data.php?action=list', dataSrc: '' },
    order: [[0, 'desc']],
    columns: [
      { data: 'event_time', render: (d) => d ? new Date(d).toLocaleString() : '—' },
      { data: 'user_name',   render: (d) => d ?? '—' },
      {
        data: 'action_type',
        render: (d) => {
          const col = actionColours[d?.toLowerCase()] || 'secondary';
          return '<span class="badge text-bg-' + col + '">' + (d ?? '—') + '</span>';
        },
      },
      { data: 'module',       render: (d) => d ?? '—' },
      { data: 'record_ref',   render: (d) => d ? '<span class="font-monospace small">' + d + '</span>' : '—' },
      { data: 'field_changed', render: (d) => d ?? '—' },
      { data: 'old_value',    render: (d) => d ? '<span class="text-muted small">' + d + '</span>' : '—' },
      { data: 'new_value',    render: (d) => d ? '<span class="small">' + d + '</span>' : '—' },
      { data: 'description',  render: (d) => d ? '<span class="small text-truncate d-inline-block" style="max-width:200px" title="' + d + '">' + d + '</span>' : '—' },
    ],
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    dom: '<"d-flex justify-content-between align-items-center mb-2"Bf>rtip',
    buttons: [
      { extend: 'copy',  className: 'btn btn-sm btn-secondary' },
      { extend: 'excel', className: 'btn btn-sm btn-success',  text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel' },
      { extend: 'csv',   className: 'btn btn-sm btn-info',     text: '<i class="bi bi-filetype-csv me-1"></i>CSV' },
      { extend: 'print', className: 'btn btn-sm btn-secondary' },
    ],
    language: {
      search:     'Global search:',
      lengthMenu: 'Show _MENU_ entries',
      info:       'Showing _START_ to _END_ of _TOTAL_ events',
    },
  });

  $('#aud-filter-module').on('keyup', function () { table.column(3).search(this.value).draw(); });
  $('#aud-filter-action').on('keyup', function () { table.column(2).search(this.value).draw(); });
  $('#aud-filter-user').on('keyup',   function () { table.column(1).search(this.value).draw(); });
  $('#aud-filter-ref').on('keyup',    function () { table.column(4).search(this.value).draw(); });

  $('#audTable tbody').on('click', 'tr', function () {
    const d = table.row(this).data();
    if (!d) return;
    const row = (label, value) => value
      ? `<tr><th class="text-nowrap" style="width:35%">${label}</th><td>${value}</td></tr>` : '';
    $('#aud-detail-body').html(`
      <table class="table table-sm table-bordered mb-0">
        ${row('Time',        d.event_time ? new Date(d.event_time).toLocaleString() : '')}
        ${row('User',        d.user_name)}
        ${row('Action',      d.action_type)}
        ${row('Module',      d.module)}
        ${row('Record Ref',  d.record_ref)}
        ${row('Field',       d.field_changed)}
        ${row('Old Value',   d.old_value)}
        ${row('New Value',   d.new_value)}
        ${row('Description', d.description)}
        ${row('IP Address',  d.ip_address)}
      </table>
    `);
    new bootstrap.Modal(document.getElementById('audDetailModal')).show();
  });
});
</script>
