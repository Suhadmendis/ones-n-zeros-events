// budgeting_search.js — Budgeting search modal logic

$(document).ready(function () {

  const fmt = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
  const statusColours = { draft: 'secondary', approved: 'success', closed: 'dark' };
  const statusLabels  = { draft: 'Draft',     approved: 'Approved', closed: 'Closed' };

  const table = $('#bdgSearchTable').DataTable({
    ajax: { url: '/modules/finance/assets_budgeting/budgeting/budgeting_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'budget_name' },
      { data: 'account_code' },
      { data: 'period' },
      { data: 'category' },
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
        render: (d) => '<span class="badge text-bg-' + (statusColours[d] || 'secondary') + '">' + (statusLabels[d] || d) + '</span>',
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
      search: 'Global search:',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ budgets',
    },
  });

  $('#bdg-search-name').on('keyup',   function () { table.column(1).search(this.value).draw(); });
  $('#bdg-search-period').on('keyup', function () { table.column(3).search(this.value).draw(); });
  $('#bdg-search-status').on('keyup', function () { table.column(8).search(this.value).draw(); });

  $('#bdgSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('bdgSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('budgeting-selected', { detail: data }));
  });

  $('#bdgSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
