// cleaner_master_file_search.js — Cleaner search modal logic

$(document).ready(function () {

  // ── Employee picker ──────────────────────────────────────────────────────
  const employeePicker = $('#cleanerEmployeePickerTable').DataTable({
    ajax: { url: '/modules/master_files/people/employee_master/employee_master_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'code' },
      { data: 'full_name' },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#cleanerEmployeePickerTable tbody').on('click', 'tr', function () {
    const data = employeePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('cleanerEmployeePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('cleaner-employee-selected', { detail: data }));
  });

  $('#cleanerEmployeePickerModal').on('shown.bs.modal', function () {
    employeePicker.ajax.reload(null, false);
    employeePicker.columns.adjust();
  });

  const table = $('#cleanerSearchTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/cleaner_master_file/cleaner_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'phone' },
      {
        data: 'status',
        render: function (data) {
          const colours = { active: 'success', inactive: 'danger' };
          const labels  = { active: 'Active',  inactive: 'Inactive' };
          return '<span class="badge text-bg-' + (colours[data] || 'secondary') + '">' + (labels[data] || data) + '</span>';
        },
      },
      { data: 'date_of_birth' },
      { data: 'joining_date' },
      { data: 'm_employees.full_name', defaultContent: '' },
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
      info: 'Showing _START_ to _END_ of _TOTAL_ cleaners',
    },
  });

  $('#cl-search-name').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#cl-search-status').on('keyup', function () { table.column(3).search(this.value).draw(); });

  $('#cleanerSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('cleanerSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('cleaner-selected', { detail: data }));
  });

  $('#cleanerSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
