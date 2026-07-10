// general_expense_entry_search.js — Expense type picker + entry search logic

$(document).ready(function () {

  // ── Expense type picker ─────────────────────────────────────────────────────
  const typePicker = $('#gexTypePickerTable').DataTable({
    ajax: { url: '/modules/master_files/references/general_expense_type/general_expense_type_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      {
        data: 'status',
        render: function (data) {
          const c = { active: 'success', inactive: 'danger' };
          const l = { active: 'Active',  inactive: 'Inactive' };
          return '<span class="badge text-bg-' + (c[data] || 'secondary') + '">' + (l[data] || data) + '</span>';
        },
      },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#gexTypePickerTable tbody').on('click', 'tr', function () {
    const data = typePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('gexTypePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('gex-expense-type-selected', { detail: data }));
  });

  $('#gexTypePickerModal').on('shown.bs.modal', function () {
    typePicker.ajax.reload(null, false);
    typePicker.columns.adjust();
  });

  // ── Entry search ────────────────────────────────────────────────────────────
  const searchTable = $('#gexSearchTable').DataTable({
    ajax: { url: '/modules/operations/expenses/general_expense_entry/general_expense_entry_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      {
        data: null,
        render: function (data, type, row) {
          if (row.general_expense_types) {
            return row.general_expense_types.ref + ' — ' + row.general_expense_types.name;
          }
          return '';
        },
      },
      { data: 'date' },
      {
        data: 'amount',
        render: function (data) { return 'LKR ' + parseFloat(data).toLocaleString(); },
      },
      { data: 'remark' },
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
    language: { search: 'Global search:', lengthMenu: 'Show _MENU_ entries', info: 'Showing _START_ to _END_ of _TOTAL_ expenses' },
  });

  $('#gexSearchTable tbody').on('click', 'tr', function () {
    const data = searchTable.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('gexSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('gex-entry-selected', { detail: data }));
  });

  $('#gexSearchModal').on('shown.bs.modal', function () {
    searchTable.ajax.reload(null, false);
    searchTable.columns.adjust();
  });

});
