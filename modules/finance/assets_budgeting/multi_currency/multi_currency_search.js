// multi_currency_search.js — Multi Currency search modal logic

$(document).ready(function () {

  const statusCols = { active: 'success', inactive: 'secondary' };
  const statusLbls = { active: 'Active',  inactive: 'Inactive' };

  const table = $('#curSearchTable').DataTable({
    ajax: { url: '/modules/finance/assets_budgeting/multi_currency/multi_currency_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'currency_code' },
      { data: 'currency_name' },
      { data: 'symbol', render: (d) => d ?? '—' },
      { data: 'exchange_rate', render: (d) => parseFloat(d).toFixed(6) },
      {
        data: 'is_base_currency',
        render: (d) => d ? '<span class="badge text-bg-primary">Base</span>' : '—',
      },
      { data: 'effective_date' },
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
      info:       'Showing _START_ to _END_ of _TOTAL_ currencies',
    },
  });

  $('#cur-search-name').on('keyup',   function () { table.column(2).search(this.value).draw(); });
  $('#cur-search-code').on('keyup',   function () { table.column(1).search(this.value).draw(); });
  $('#cur-search-status').on('keyup', function () { table.column(7).search(this.value).draw(); });

  $('#curSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('curSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('multi_currency-selected', { detail: data }));
  });

  $('#curSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
