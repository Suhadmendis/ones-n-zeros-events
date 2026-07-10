// cash_bank_search.js — Cash & Bank search modal logic

$(document).ready(function () {

  const fmt = (v) => v ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';

  const typeColours   = { Deposit: 'success', Withdrawal: 'danger', Transfer: 'info' };
  const statusColours = { pending: 'warning', cleared: 'success', reconciled: 'primary' };
  const statusLabels  = { pending: 'Pending', cleared: 'Cleared', reconciled: 'Reconciled' };

  const table = $('#cbSearchTable').DataTable({
    ajax: { url: '/modules/finance/receivables_payables/cash_bank/cash_bank_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'account_name' },
      {
        data: 'transaction_type',
        render: (d) => '<span class="badge text-bg-' + (typeColours[d] || 'secondary') + '">' + d + '</span>',
      },
      { data: 'transaction_date' },
      { data: 'amount', render: (d) => fmt(d) },
      { data: 'reference_doc', defaultContent: '—' },
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
      info: 'Showing _START_ to _END_ of _TOTAL_ transactions',
    },
  });

  $('#cb-search-account').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#cb-search-type').on('keyup',    function () { table.column(2).search(this.value).draw(); });
  $('#cb-search-status').on('keyup',  function () { table.column(6).search(this.value).draw(); });

  $('#cbSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('cbSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('cash_bank-selected', { detail: data }));
  });

  $('#cbSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
