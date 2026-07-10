// item_master_file_search.js — Item search modal logic

$(document).ready(function () {

  const table = $('#itemSearchTable').DataTable({
    ajax: { url: '/modules/master_files/references/item_master_file/item_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      {
        data: 'category',
        render: function (data) {
          const labels = { fuel: 'Fuel', equipment: 'Equipment', materials: 'Materials', cargo: 'Cargo', other: 'Other' };
          return labels[data] || data;
        },
      },
      { data: 'unit' },
      {
        data: 'status',
        render: function (data) {
          const colours = { active: 'success', inactive: 'danger' };
          const labels  = { active: 'Active',  inactive: 'Inactive' };
          return '<span class="badge text-bg-' + (colours[data] || 'secondary') + '">' + (labels[data] || data) + '</span>';
        },
      },
      { data: 'description', defaultContent: '' },
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
      info: 'Showing _START_ to _END_ of _TOTAL_ items',
    },
  });

  $('#it-search-name').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#it-search-category').on('keyup', function () { table.column(2).search(this.value).draw(); });

  $('#itemSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('itemSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('item-selected', { detail: data }));
  });

  $('#itemSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
