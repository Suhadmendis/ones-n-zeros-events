// cost_centers_search.js — Cost Centers search modal logic

$(document).ready(function () {

  const fmt        = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
  const statusCols = { active: 'success', inactive: 'secondary' };
  const statusLbls = { active: 'Active',  inactive: 'Inactive' };

  const table = $('#cstSearchTable').DataTable({
    ajax: { url: '/modules/finance/assets_budgeting/cost_centers/cost_centers_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'cost_center_code' },
      { data: 'cost_center_name' },
      { data: 'department', render: (d) => d ?? '—' },
      { data: 'manager',    render: (d) => d ?? '—' },
      { data: 'budget_amount', render: (d) => fmt(d) },
      { data: 'actual_amount', render: (d) => fmt(d) },
      {
        data: 'variance',
        render: (d) => {
          const v   = parseFloat(d) || 0;
          const cls = v >= 0 ? 'text-success' : 'text-danger';
          return '<span class="' + cls + ' fw-semibold">' + fmt(d) + '</span>';
        },
      },
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
      info:       'Showing _START_ to _END_ of _TOTAL_ cost centers',
    },
  });

  $('#cst-search-name').on('keyup',   function () { table.column(2).search(this.value).draw(); });
  $('#cst-search-dept').on('keyup',   function () { table.column(3).search(this.value).draw(); });
  $('#cst-search-status').on('keyup', function () { table.column(8).search(this.value).draw(); });

  $('#cstSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('cstSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('cost_centers-selected', { detail: data }));
  });

  $('#cstSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
