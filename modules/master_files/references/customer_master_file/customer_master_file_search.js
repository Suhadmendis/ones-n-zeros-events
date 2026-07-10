// customer_master_file_search.js — Customer search modal logic

$(document).ready(function () {

  const statusBadge = function (data) {
    const colours = { active: 'success', inactive: 'danger' };
    const labels  = { active: 'Active',  inactive: 'Inactive' };
    return '<span class="badge text-bg-' + (colours[data] || 'secondary') + '">' + (labels[data] || data) + '</span>';
  };

  // ── Customer Type picker ─────────────────────────────────────────────────
  const customerTypePicker = $('#cuCustomerTypePickerTable').DataTable({
    ajax: { url: '/modules/master_files/references/customer_types/customer_types_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'code' },
      { data: 'name' },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#cuCustomerTypePickerTable tbody').on('click', 'tr', function () {
    const data = customerTypePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('cuCustomerTypePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('cu-customer-type-selected', { detail: data }));
  });

  $('#cuCustomerTypePickerModal').on('shown.bs.modal', function () {
    customerTypePicker.ajax.reload(null, false);
    customerTypePicker.columns.adjust();
  });

  const table = $('#customerSearchTable').DataTable({
    ajax: { url: '/modules/master_files/references/customer_master_file/customer_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'code', defaultContent: '—' },
      { data: 'customer_name' },
      { data: 'm_customer_types.name', defaultContent: '—' },
      { data: 'phone', defaultContent: '—' },
      { data: 'email', defaultContent: '—' },
      { data: 'city', defaultContent: '—' },
      { data: 'record_status', render: statusBadge },
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
      info: 'Showing _START_ to _END_ of _TOTAL_ customers',
    },
  });

  $('#cu-search-name').on('keyup', function ()   { table.column(2).search(this.value).draw(); });
  $('#cu-search-status').on('keyup', function () { table.column(7).search(this.value).draw(); });

  $('#customerSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('customerSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('customer-selected', { detail: data }));
  });

  $('#customerSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
