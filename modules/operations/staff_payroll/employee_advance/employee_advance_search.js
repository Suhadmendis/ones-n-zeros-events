// employee_advance_search.js — Employee picker + advance search logic

$(document).ready(function () {

  // ── Employee picker ─────────────────────────────────────────────────────────
  const employeePicker = $('#advEmployeePickerTable').DataTable({
    ajax: { url: '/modules/master_files/people/employee_master/employee_master_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'full_name' },
      { data: 'mobile' },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#advEmployeePickerTable tbody').on('click', 'tr', function () {
    const data = employeePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('advEmployeePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('adv-employee-selected', { detail: data }));
  });

  $('#advEmployeePickerModal').on('shown.bs.modal', function () {
    employeePicker.ajax.reload(null, false);
    employeePicker.columns.adjust();
  });

  // ── Advance payment search ──────────────────────────────────────────────────
  const searchTable = $('#advSearchTable').DataTable({
    ajax: { url: '/modules/operations/staff_payroll/employee_advance/employee_advance_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      {
        data: null,
        render: function (data, type, row) {
          if (row.m_employees) return row.m_employees.ref + ' — ' + row.m_employees.full_name;
          return row.employee_ref || '';
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
