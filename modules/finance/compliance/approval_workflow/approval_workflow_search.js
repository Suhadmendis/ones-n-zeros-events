// approval_workflow_search.js — Approval Workflow search modal logic

$(document).ready(function () {

  const fmt        = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
  const statusCols = { active: 'success', inactive: 'secondary' };
  const statusLbls = { active: 'Active',  inactive: 'Inactive' };

  const table = $('#apwSearchTable').DataTable({
    ajax: { url: '/modules/finance/compliance/approval_workflow/approval_workflow_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'workflow_name' },
      { data: 'module' },
      { data: 'approver_name' },
      { data: 'approval_order' },
      { data: 'is_mandatory', render: (d) => d ? '<span class="badge text-bg-primary">Yes</span>' : '<span class="badge text-bg-light text-dark">No</span>' },
      { data: 'min_amount', render: (d) => fmt(d) },
      { data: 'max_amount', render: (d) => fmt(d) },
      {
        data: 'status',
        render: (d) => '<span class="badge text-bg-' + (statusCols[d] || 'secondary') + '">' + (statusLbls[d] || d) + '</span>',
      },
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
      search:     'Global search:',
      lengthMenu: 'Show _MENU_ entries',
      info:       'Showing _START_ to _END_ of _TOTAL_ workflows',
    },
  });

  $('#apw-search-name').on('keyup',   function () { table.column(1).search(this.value).draw(); });
  $('#apw-search-module').on('keyup', function () { table.column(2).search(this.value).draw(); });
  $('#apw-search-status').on('keyup', function () { table.column(8).search(this.value).draw(); });

  $('#apwSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('apwSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('approval_workflow-selected', { detail: data }));
  });

  $('#apwSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
