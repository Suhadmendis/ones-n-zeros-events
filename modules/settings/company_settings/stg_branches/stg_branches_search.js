// stg_branches_search.js — Branches (Company Settings) search modal logic

$(document).ready(function () {

  const statusCols = { active: 'success', inactive: 'secondary' };
  const statusLbls = { active: 'Active',  inactive: 'Inactive' };

  const table = $('#brSearchTable').DataTable({
    ajax: { url: '/modules/settings/company_settings/stg_branches/stg_branches_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'branch_code' },
      { data: 'branch_name' },
      { data: 'city',  render: (d) => d ?? '—' },
      { data: 'phone', render: (d) => d ?? '—' },
      { data: 'manager_name', render: (d) => d ?? '—' },
      {
        data: 'is_head_office',
        render: (d) => d ? '<span class="badge text-bg-primary">Head Office</span>' : '—',
      },
      {
        data: 'status',
        render: (d) => '<span class="badge text-bg-' + (statusCols[d] || 'secondary') + '">' + (statusLbls[d] || d) + '</span>',
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
    language: {
      search:     'Global search:',
      lengthMenu: 'Show _MENU_ entries',
      info:       'Showing _START_ to _END_ of _TOTAL_ branches',
    },
  });

  $('#br-search-name').on('keyup',   function () { table.column(2).search(this.value).draw(); });
  $('#br-search-code').on('keyup',   function () { table.column(1).search(this.value).draw(); });
  $('#br-search-status').on('keyup', function () { table.column(7).search(this.value).draw(); });

  $('#brSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('brSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('stg_branches-selected', { detail: data }));
  });

  $('#brSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
