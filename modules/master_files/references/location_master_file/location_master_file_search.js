// location_master_file_search.js — Location search modal logic

$(document).ready(function () {

  const table = $('#locationSearchTable').DataTable({
    ajax: { url: '/modules/master_files/references/location_master_file/location_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'district' },
      { data: 'province' },
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
      info: 'Showing _START_ to _END_ of _TOTAL_ locations',
    },
  });

  $('#loc-search-name').on('keyup',     function () { table.column(1).search(this.value).draw(); });
  $('#loc-search-district').on('keyup', function () { table.column(2).search(this.value).draw(); });
  $('#loc-search-status').on('keyup',   function () { table.column(4).search(this.value).draw(); });

  $('#locationSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('locationSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('location-selected', { detail: data }));
  });

  $('#locationSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
