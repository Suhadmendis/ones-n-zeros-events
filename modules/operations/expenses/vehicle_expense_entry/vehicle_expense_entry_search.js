// vehicle_expense_entry_search.js — Vehicle picker + expense search logic

$(document).ready(function () {

  const catLabels = { repair: 'Repair', maintenance: 'Maintenance', body_work: 'Body Work', tyres: 'Tyres' };

  // ── Vehicle picker ──────────────────────────────────────────────────────────
  const vehiclePicker = $('#vexVehiclePickerTable').DataTable({
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

  $('#vexVehiclePickerTable tbody').on('click', 'tr', function () {
    const data = vehiclePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('vexVehiclePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('vex-vehicle-selected', { detail: data }));
  });

  $('#vexVehiclePickerModal').on('shown.bs.modal', function () {
    vehiclePicker.ajax.reload(null, false);
    vehiclePicker.columns.adjust();
  });

  // ── Expense search ──────────────────────────────────────────────────────────
  const searchTable = $('#vexSearchTable').DataTable({
    ajax: { url: '/modules/operations/expenses/vehicle_expense_entry/vehicle_expense_entry_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      {
        data: 'vehicles',
        render: function (data, type, row) {
          if (!data) return row.vehicle_ref ?? '';
          return data.ref + ' — ' + data.plate_number + ' (' + data.make + ')';
        },
      },
      {
        data: 'category',
        render: function (data) { return catLabels[data] || data; },
      },
      { data: 'remark' },
      { data: 'amount' },
      { data: 'date' },
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

  $('#vexSearchTable tbody').on('click', 'tr', function () {
    const data = searchTable.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('vexSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('vex-entry-selected', { detail: data }));
  });

  $('#vexSearchModal').on('shown.bs.modal', function () {
    searchTable.ajax.reload(null, false);
    searchTable.columns.adjust();
  });

});
