// accounts_receivable_search.js — AR search modal logic

$(document).ready(function () {

  const fmt = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';

  const statusColours = { open: 'primary', partial: 'warning', paid: 'success', overdue: 'danger', written_off: 'secondary' };
  const statusLabels  = { open: 'Open',    partial: 'Partial', paid: 'Paid',    overdue: 'Overdue', written_off: 'Written Off' };

  const table = $('#arSearchTable').DataTable({
    ajax: { url: '/modules/finance/receivables_payables/accounts_receivable/accounts_receivable_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'customer_name' },
      { data: 'invoice_ref',  defaultContent: '—' },
      { data: 'invoice_date' },
      { data: 'due_date' },
      { data: 'amount',       render: (d) => fmt(d) },
      { data: 'amount_paid',  render: (d) => fmt(d) },
      { data: 'balance',      render: (d) => fmt(d) },
      {
        data: 'status',
        render: (data) => '<span class="badge text-bg-' + (statusColours[data] || 'secondary') + '">' + (statusLabels[data] || data) + '</span>',
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
      info: 'Showing _START_ to _END_ of _TOTAL_ records',
    },
  });

  $('#ar-search-customer').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#ar-search-status').on('keyup',   function () { table.column(8).search(this.value).draw(); });

  $('#arSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('arSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('accounts_receivable-selected', { detail: data }));
  });

  $('#arSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
