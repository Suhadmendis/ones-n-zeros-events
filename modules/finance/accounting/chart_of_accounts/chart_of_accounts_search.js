// chart_of_accounts_search.js — COA search modal logic

$(document).ready(function () {

  const table = $('#coaSearchTable').DataTable({
    ajax: { url: '/modules/finance/accounting/chart_of_accounts/chart_of_accounts_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'account_code' },
      { data: 'account_name' },
      { data: 'account_type' },
      { data: 'account_sub_type', defaultContent: '—' },
      {
        data: 'status',
        render: function (data) {
          const colours = { active: 'success', inactive: 'danger' };
          const labels  = { active: 'Active',  inactive: 'Inactive' };
          return '<span class="badge text-bg-' + (colours[data] || 'secondary') + '">' + (labels[data] || data) + '</span>';
        },
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
      info: 'Showing _START_ to _END_ of _TOTAL_ accounts',
    },
  });

  $('#coa-search-code').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#coa-search-name').on('keyup', function () { table.column(2).search(this.value).draw(); });
  $('#coa-search-type').on('keyup', function () { table.column(3).search(this.value).draw(); });

  $('#coaSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('coaSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('chart_of_accounts-selected', { detail: data }));
  });

  $('#coaSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
