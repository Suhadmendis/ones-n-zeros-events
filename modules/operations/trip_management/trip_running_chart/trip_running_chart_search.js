// trip_running_chart_search.js — Trip pickers + search table logic

$(document).ready(function () {

  const statusBadge = function (data) {
    const c = { active: 'success', inactive: 'danger' };
    return '<span class="badge text-bg-' + (c[data] || 'secondary') + '">' + data + '</span>';
  };

  // ── Vehicle picker ──────────────────────────────────────────────────────────
  const vehiclePicker = $('#tripVehiclePickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'plate_number' },
      { data: 'make' },
      { data: 'model' },
      { data: 'type' },
      { data: 'status', render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#tripVehiclePickerTable tbody').on('click', 'tr', function () {
    const data = vehiclePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('tripVehiclePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('trip-vehicle-selected', { detail: data }));
  });

  $('#tripVehiclePickerModal').on('shown.bs.modal', function () {
    vehiclePicker.ajax.reload(null, false);
    vehiclePicker.columns.adjust();
  });

  // ── Driver picker ───────────────────────────────────────────────────────────
  const driverPicker = $('#tripDriverPickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/driver_master_file/driver_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'phone', defaultContent: '' },
      { data: 'license_number', defaultContent: '' },
      { data: 'status', render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#tripDriverPickerTable tbody').on('click', 'tr', function () {
    const data = driverPicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('tripDriverPickerModal')).hide();
    document.dispatchEvent(new CustomEvent('trip-driver-selected', { detail: data }));
  });

  $('#tripDriverPickerModal').on('shown.bs.modal', function () {
    driverPicker.ajax.reload(null, false);
    driverPicker.columns.adjust();
  });

  // ── Cleaner picker ──────────────────────────────────────────────────────────
  const cleanerPicker = $('#tripCleanerPickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/cleaner_master_file/cleaner_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'phone', defaultContent: '' },
      { data: 'status', render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#tripCleanerPickerTable tbody').on('click', 'tr', function () {
    const data = cleanerPicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('tripCleanerPickerModal')).hide();
    document.dispatchEvent(new CustomEvent('trip-cleaner-selected', { detail: data }));
  });

  $('#tripCleanerPickerModal').on('shown.bs.modal', function () {
    cleanerPicker.ajax.reload(null, false);
    cleanerPicker.columns.adjust();
  });

  // ── Item picker ─────────────────────────────────────────────────────────────
  const itemPicker = $('#tripItemPickerTable').DataTable({
    ajax: { url: '/modules/master_files/references/item_master_file/item_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'category', defaultContent: '' },
      { data: 'unit',     defaultContent: '' },
      { data: 'status',   render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#tripItemPickerTable tbody').on('click', 'tr', function () {
    const data = itemPicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('tripItemPickerModal')).hide();
    document.dispatchEvent(new CustomEvent('trip-item-selected', { detail: data }));
  });

  $('#tripItemPickerModal').on('shown.bs.modal', function () {
    itemPicker.ajax.reload(null, false);
    itemPicker.columns.adjust();
  });

  // ── Trip search ─────────────────────────────────────────────────────────────
  const searchTable = $('#tripSearchTable').DataTable({
    ajax: { url: '/modules/operations/trip_management/trip_running_chart/trip_running_chart_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'date' },
      {
        data: 'vehicles',
        render: function (data, type, row) {
          if (!data) return row.vehicle_ref ?? '';
          return data.ref + ' — ' + data.plate_number;
        },
      },
      {
        data: 'drivers',
        render: function (data, type, row) {
          if (!data) return row.driver_ref ?? '';
          return data.ref + ' — ' + data.name;
        },
      },
      { data: 'from_loc' },
      { data: 'to_loc' },
      {
        data: 'amount',
        render: function (data) {
          return 'LKR ' + parseFloat(data).toLocaleString('en-LK', { minimumFractionDigits: 2 });
        },
      },
      { data: 'run_no', defaultContent: '' },
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
    language: { search: 'Global search:', lengthMenu: 'Show _MENU_ entries', info: 'Showing _START_ to _END_ of _TOTAL_ entries' },
  });

  $('#tripSearchTable tbody').on('click', 'tr', function () {
    const data = searchTable.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('tripSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('trip-running-chart-selected', { detail: data }));
  });

  $('#tripSearchModal').on('shown.bs.modal', function () {
    searchTable.ajax.reload(null, false);
    searchTable.columns.adjust();
  });

});
