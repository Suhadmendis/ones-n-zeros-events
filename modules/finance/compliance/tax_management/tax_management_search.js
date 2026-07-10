// tax_management_search.js — Tax Management search modal logic

$(document).ready(function () {

  const fmt        = (v) => v !== null && v !== undefined ? parseFloat(v).toFixed(4) : '—';
  const statusCols = { active: 'success', inactive: 'secondary' };
  const statusLbls = { active: 'Active',  inactive: 'Inactive' };

  const table = $('#taxSearchTable').DataTable({
    ajax: { url: '/modules/finance/compliance/tax_management/tax_management_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'tax_name' },
      { data: 'tax_code' },
      { data: 'tax_type' },
      { data: 'rate', render: (d) => fmt(d) + '%' },
      { data: 'applicable_on' },
      { data: 'effective_date' },
      { data: 'expiry_date', render: (d) => d ?? '—' },
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
      info:       'Showing _START_ to _END_ of _TOTAL_ tax records',
    },
  });

  $('#tax-search-name').on('keyup',   function () { table.column(1).search(this.value).draw(); });
  $('#tax-search-code').on('keyup',   function () { table.column(2).search(this.value).draw(); });
  $('#tax-search-status').on('keyup', function () { table.column(8).search(this.value).draw(); });

  $('#taxSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('taxSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('tax_management-selected', { detail: data }));
  });

  $('#taxSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
