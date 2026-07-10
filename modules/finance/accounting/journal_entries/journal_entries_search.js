// journal_entries_search.js — JE search modal logic

$(document).ready(function () {

  const fmt = (v) => v ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';

  const table = $('#jeSearchTable').DataTable({
    ajax: { url: '/modules/finance/accounting/journal_entries/journal_entries_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'journal_date' },
      { data: 'period' },
      { data: 'description' },
      { data: 'reference_doc', defaultContent: '—' },
      { data: 'total_debit',   render: (d) => fmt(d) },
      { data: 'total_credit',  render: (d) => fmt(d) },
      {
        data: 'status',
        render: function (data) {
          const colours = { draft: 'secondary', posted: 'success', reversed: 'danger' };
          const labels  = { draft: 'Draft',     posted: 'Posted',  reversed: 'Reversed' };
          return '<span class="badge text-bg-' + (colours[data] || 'secondary') + '">' + (labels[data] || data) + '</span>';
        },
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
      search: 'Global search:',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
    },
  });

  $('#je-search-period').on('keyup', function () { table.column(2).search(this.value).draw(); });
  $('#je-search-desc').on('keyup',   function () { table.column(3).search(this.value).draw(); });
  $('#je-search-status').on('keyup', function () { table.column(7).search(this.value).draw(); });

  $('#jeSearchTable tbody').on('click', 'tr', function () {
    const row = table.row(this).data();
    if (!row) return;
    bootstrap.Modal.getInstance(document.getElementById('jeSearchModal')).hide();
    axios.get('/modules/finance/accounting/journal_entries/journal_entries_data.php?action=get&ref=' + encodeURIComponent(row.ref))
      .then(res => {
        document.dispatchEvent(new CustomEvent('journal_entries-selected', { detail: res.data }));
      });
  });

  $('#jeSearchModal').on('shown.bs.modal', function () {
    table.columns.adjust();
    table.ajax.reload(null, false);
  });

});
