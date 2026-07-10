// vehicle_master_file_search.js — Vehicle search modal logic

$(document).ready(function () {

  const table = $('#vehicleSearchTable').DataTable({
    ajax: {
      url: '/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=list',
      dataSrc: '',
    },
    columns: [
      { data: 'ref' },
      { data: 'plate_number' },
      { data: 'make' },
      { data: 'model' },
      {
        data: 'type',
        render: function (data) {
          const labels = { lorry: 'Lorry', bowser: 'Bowser', tipper: 'Tipper', truck: 'Truck', van: 'Van', bus: 'Bus' };
          return labels[data] || data;
        },
      },
      {
        data: 'fuel_type',
        render: function (data) {
          return data === 'petrol' ? 'Petrol' : data === 'diesel' ? 'Diesel' : data;
        },
      },
      {
        data: 'status',
        render: function (data) {
          const colours = { active: 'success', inactive: 'danger' };
          const labels  = { active: 'Active',  inactive: 'Inactive' };
          return '<span class="badge text-bg-' + (colours[data] || 'secondary') + '">' + (labels[data] || data) + '</span>';
        },
      },
      { data: 'year' },
      { data: 'mileage' },
    ],
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    ordering: true,
    searching: true,
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
      info: 'Showing _START_ to _END_ of _TOTAL_ vehicles',
    },
  });

  // Column-specific search — Plate Number (col 1)
  $('#vh-search-plate').on('keyup', function () {
    table.column(1).search(this.value).draw();
  });

  // Column-specific search — Type (col 4)
  $('#vh-search-type').on('keyup', function () {
    table.column(4).search(this.value).draw();
  });

  // Column-specific search — Status (col 6)
  $('#vh-search-status').on('keyup', function () {
    table.column(6).search(this.value).draw();
  });

  // Row click — close modal and send data to form
  $('#vehicleSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('vehicleSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('vehicle-selected', { detail: data }));
  });

  // Re-adjust table layout when modal fully opens
  $('#vehicleSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
