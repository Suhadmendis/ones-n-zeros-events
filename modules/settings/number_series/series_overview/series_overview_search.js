// series_overview_search.js — Series Overview search modal logic

$(document).ready(function () {

  const table = $('#nsrSearchTable').DataTable({
    ajax: {
      url:     '/modules/settings/number_series/series_overview/series_overview_data.php?action=list',
      dataSrc: '',
    },
    columns: [
      { data: 'ref' },
      { data: 'module_name' },
      { data: 'prefix' },
      { data: 'suffix', defaultContent: '—' },
      { data: 'padding_length' },
      { data: 'current_number' },
      {
        data: 'record_status',
        render: function (data) {
          return data === 'active'
            ? '<span class="badge text-bg-success">Active</span>'
            : '<span class="badge text-bg-secondary">' + (data || 'Inactive') + '</span>';
        },
      },
    ],
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    ordering:   true,
    searching:  true,
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
      info:       'Showing _START_ to _END_ of _TOTAL_ series',
    },
  });

  $('#nsr-search-module').on('keyup', function () {
    table.column(1).search(this.value).draw();
  });

  $('#nsr-search-prefix').on('keyup', function () {
    table.column(2).search(this.value).draw();
  });

  $('#nsrSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('nsrSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('nsr-selected', { detail: data }));
  });

  $('#nsrSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
