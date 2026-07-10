// period_closing_search.js — Period Closing search modal logic

$(document).ready(function () {

  const fmt        = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
  const statusCols = { open: 'success', closed: 'warning', locked: 'danger' };
  const statusLbls = { open: 'Open',   closed: 'Closed',  locked: 'Locked' };

  const table = $('#prcSearchTable').DataTable({
    ajax: { url: '/modules/finance/compliance/period_closing/period_closing_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'period' },
      { data: 'period_name' },
      { data: 'closing_date' },
      { data: 'total_revenue',  render: (d) => fmt(d) },
      { data: 'total_expenses', render: (d) => fmt(d) },
      {
        data: 'net_profit',
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
      info:       'Showing _START_ to _END_ of _TOTAL_ periods',
    },
  });

  $('#prc-search-period').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#prc-search-status').on('keyup', function () { table.column(7).search(this.value).draw(); });

  $('#prcSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('prcSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('period_closing-selected', { detail: data }));
  });

  $('#prcSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
