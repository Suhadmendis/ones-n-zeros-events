// general_ledger_search.js — GL search modal logic

$(document).ready(function () {

  const fmt = (v) => v ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';

  const table = $('#glSearchTable').DataTable({
    ajax: { url: '/modules/finance/accounting/general_ledger/general_ledger_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'account_code' },
      { data: 'transaction_date' },
      { data: 'period' },
      { data: 'description' },
      { data: 'debit_amount',  render: (d) => fmt(d) },
      { data: 'credit_amount', render: (d) => fmt(d) },
      {
        data: 'status',
        render: function (data) {
          const colours = { draft: 'secondary', posted: 'success', reversed: 'danger' };
          const labels  = { draft: 'Draft',     posted: 'Posted',  reversed: 'Reversed' };
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
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
    },
  });

  $('#gl-search-code').on('keyup',   function () { table.column(1).search(this.value).draw(); });
  $('#gl-search-period').on('keyup', function () { table.column(3).search(this.value).draw(); });
  $('#gl-search-status').on('keyup', function () { table.column(7).search(this.value).draw(); });

  $('#glSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('glSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('general_ledger-selected', { detail: data }));
  });

  $('#glSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
