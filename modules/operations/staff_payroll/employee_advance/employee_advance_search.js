// driver_advance_search.js — Driver/cleaner pickers + advance search logic

$(document).ready(function () {

  const statusBadge = function (data) {
    const c = { active: 'success', inactive: 'danger', on_leave: 'warning' };
    const l = { active: 'Active', inactive: 'Inactive', on_leave: 'On Leave' };
    return '<span class="badge text-bg-' + (c[data] || 'secondary') + '">' + (l[data] || data) + '</span>';
  };

  // ── Driver picker ───────────────────────────────────────────────────────────
  const driverPicker = $('#advDriverPickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/driver_master_file/driver_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'phone' },
      { data: 'status', render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#advDriverPickerTable tbody').on('click', 'tr', function () {
    const data = driverPicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('advDriverPickerModal')).hide();
    document.dispatchEvent(new CustomEvent('adv-driver-selected', { detail: data }));
  });

  $('#advDriverPickerModal').on('shown.bs.modal', function () {
    driverPicker.ajax.reload(null, false);
    driverPicker.columns.adjust();
  });

  // ── Cleaner picker ──────────────────────────────────────────────────────────
  const cleanerPicker = $('#advCleanerPickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/cleaner_master_file/cleaner_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'phone' },
      { data: 'status', render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#advCleanerPickerTable tbody').on('click', 'tr', function () {
    const data = cleanerPicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('advCleanerPickerModal')).hide();
    document.dispatchEvent(new CustomEvent('adv-cleaner-selected', { detail: data }));
  });

  $('#advCleanerPickerModal').on('shown.bs.modal', function () {
    cleanerPicker.ajax.reload(null, false);
    cleanerPicker.columns.adjust();
  });

  // ── Advance payment search ──────────────────────────────────────────────────
  const searchTable = $('#advSearchTable').DataTable({
    ajax: { url: '/modules/operations/staff_payroll/driver_advance/driver_advance_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      {
        data: 'recipient_type',
        render: function (data) {
          return data === 'driver' ? 'Driver' : 'Cleaner';
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          if (row.recipient_type === 'driver'  && row.drivers)  return row.drivers.ref  + ' — ' + row.drivers.name;
          if (row.recipient_type === 'cleaner' && row.cleaners) return row.cleaners.ref + ' — ' + row.cleaners.name;
          return '';
        },
      },
      { data: 'date' },
      {
        data: 'amount',
        render: function (data) { return 'LKR ' + parseFloat(data).toLocaleString(); },
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
    language: { search: 'Global search:', lengthMenu: 'Show _MENU_ entries', info: 'Showing _START_ to _END_ of _TOTAL_ payments' },
  });

  $('#advSearchTable tbody').on('click', 'tr', function () {
    const data = searchTable.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('advSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('adv-entry-selected', { detail: data }));
  });

  $('#advSearchModal').on('shown.bs.modal', function () {
    searchTable.ajax.reload(null, false);
    searchTable.columns.adjust();
  });

});
