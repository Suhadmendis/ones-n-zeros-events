// fixed_assets_search.js — Fixed Assets search modal logic

$(document).ready(function () {

  const fmt = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
  const statusColours = { active: 'success', disposed: 'danger', fully_depreciated: 'secondary' };
  const statusLabels  = { active: 'Active',  disposed: 'Disposed', fully_depreciated: 'Fully Depreciated' };

  const table = $('#faSearchTable').DataTable({
    ajax: { url: '/modules/finance/assets_budgeting/fixed_assets/fixed_assets_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'asset_name' },
      { data: 'asset_category' },
      { data: 'purchase_date' },
      { data: 'purchase_cost',            render: (d) => fmt(d) },
      { data: 'accumulated_depreciation', render: (d) => fmt(d) },
      { data: 'net_book_value',           render: (d) => fmt(d) },
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
      info: 'Showing _START_ to _END_ of _TOTAL_ assets',
    },
  });

  $('#fa-search-name').on('keyup',     function () { table.column(1).search(this.value).draw(); });
  $('#fa-search-category').on('keyup', function () { table.column(2).search(this.value).draw(); });
  $('#fa-search-status').on('keyup',   function () { table.column(7).search(this.value).draw(); });

  $('#faSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('faSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('fixed_assets-selected', { detail: data }));
  });

  $('#faSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
