// bank_reconciliation_search.js — Bank Reconciliation search modal logic

$(document).ready(function () {

  const fmt        = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
  const statusCols = { draft: 'secondary', reconciled: 'success', locked: 'danger' };
  const statusLbls = { draft: 'Draft',     reconciled: 'Reconciled', locked: 'Locked' };

  const table = $('#bnkSearchTable').DataTable({
    ajax: { url: '/modules/finance/receivables_payables/bank_reconciliation/bank_reconciliation_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'account_name' },
      { data: 'period' },
      { data: 'reconciliation_date' },
      { data: 'bank_balance',         render: (d) => fmt(d) },
      { data: 'book_balance',         render: (d) => fmt(d) },
      { data: 'adjusted_bank_balance', render: (d) => fmt(d) },
      {
        data: 'difference',
        render: (d) => {
          const v   = parseFloat(d) || 0;
          const cls = Math.abs(v) <= 0.01 ? 'text-success' : 'text-danger';
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
      info:       'Showing _START_ to _END_ of _TOTAL_ reconciliations',
    },
  });

  $('#bnk-search-account').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#bnk-search-period').on('keyup',  function () { table.column(2).search(this.value).draw(); });
  $('#bnk-search-status').on('keyup',  function () { table.column(8).search(this.value).draw(); });

  $('#bnkSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('bnkSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('bank_reconciliation-selected', { detail: data }));
  });

  $('#bnkSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
