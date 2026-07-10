// edit_deactivate_user_search.js — User search modal logic

$(document).ready(function () {

  const table = $('#editUserSearchTable').DataTable({
    ajax: { url: '/modules/company_management/user_management/edit_deactivate_user/edit_deactivate_user_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'full_name' },
      { data: 'email' },
      {
        data: 'roles',
        render: function (data) {
          if (!data) return '';
          return data.split(', ').map(function (r) {
            return '<span class="badge text-bg-secondary me-1">' + r + '</span>';
          }).join('');
        },
      },
      {
        data: 'record_status',
        render: function (data) {
          const colours = { active: 'success', inactive: 'danger' };
          const labels  = { active: 'Active',  inactive: 'Inactive' };
          return '<span class="badge text-bg-' + (colours[data] || 'secondary') + '">' + (labels[data] || data) + '</span>';
        },
      },
    ],
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: {
      search: 'Global search:',
      info: 'Showing _START_ to _END_ of _TOTAL_ users',
    },
  });

  $('#eu-search-name').on('keyup', function () { table.column(1).search(this.value).draw(); });
  $('#eu-search-role').on('keyup', function () { table.column(3).search(this.value).draw(); });

  $('#editUserSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('editUserSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('edit-user-selected', { detail: data }));
  });

  $('#editUserSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
