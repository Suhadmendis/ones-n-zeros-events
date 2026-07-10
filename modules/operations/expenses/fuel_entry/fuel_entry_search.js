// fuel_entry_search.js — Vehicle picker + fuel entry search logic

$(document).ready(function () {

  // ── Vehicle picker ──────────────────────────────────────────────────────────
  const vehiclePicker = $('#fuelVehiclePickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'plate_number' },
      { data: 'make' },
      { data: 'model' },
      { data: 'type' },
      {
        data: 'status',
        render: function (data) {
          const c = { active: 'success', inactive: 'danger' };
          return '<span class="badge text-bg-' + (c[data] || 'secondary') + '">' + (data === 'active' ? 'Active' : 'Inactive') + '</span>';
        },
      },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#fuelVehiclePickerTable tbody').on('click', 'tr', function () {
    const data = vehiclePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('fuelVehiclePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('fuel-vehicle-selected', { detail: data }));
  });

  $('#fuelVehiclePickerModal').on('shown.bs.modal', function () {
    vehiclePicker.ajax.reload(null, false);
    vehiclePicker.columns.adjust();
  });

  // ── Fuel entry search ───────────────────────────────────────────────────────
  const searchTable = $('#fuelSearchTable').DataTable({
    ajax: { url: '/modules/operations/expenses/fuel_entry/fuel_entry_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      {
        data: 'vehicles',
        render: function (data, type, row) {
          if (!data) return row.vehicle_ref ?? '';
          return data.ref + ' — ' + data.plate_number + ' (' + data.make + ')';
        },
      },
      { data: 'date' },
      { data: 'liters' },
      { data: 'rate' },
      { data: 'total' },
      { data: 'voucher_number', defaultContent: '' },
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

  $('#fuelSearchTable tbody').on('click', 'tr', function () {
    const data = searchTable.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('fuelSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('fuel-entry-selected', { detail: data }));
  });

  $('#fuelSearchModal').on('shown.bs.modal', function () {
    searchTable.ajax.reload(null, false);
    searchTable.columns.adjust();
  });

});
