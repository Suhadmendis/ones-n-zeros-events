// cash_flow_search.js — Cash flow entry search logic

$(document).ready(function () {

  const searchTable = $('#cashFlowSearchTable').DataTable({
    ajax: { url: '/modules/operations/expenses/cash_flow/cash_flow_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'date' },
      {
        data: 'flow_type',
        render: function (data) {
          if (data === 'inflow')  return '<span class="badge text-bg-success">Inflow</span>';
          if (data === 'outflow') return '<span class="badge text-bg-danger">Outflow</span>';
          return data;
        },
      },
      { data: 'category' },
      {
        data: 'amount',
        render: function (data) {
          return 'LKR ' + parseFloat(data).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
      },
      { data: 'description', defaultContent: '' },
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

  $('#cashFlowSearchTable tbody').on('click', 'tr', function () {
    const data = searchTable.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('cashFlowSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('cash-flow-selected', { detail: data }));
  });

  $('#cashFlowSearchModal').on('shown.bs.modal', function () {
    searchTable.ajax.reload(null, false);
    searchTable.columns.adjust();
  });

});
