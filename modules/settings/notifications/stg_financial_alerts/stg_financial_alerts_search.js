// stg_financial_alerts_search.js — Financial Alerts search modal logic

$(document).ready(function () {

  const fmt         = (v) => v !== null && v !== undefined ? parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
  const statusCols   = { active: 'success', inactive: 'secondary' };
  const statusLbls   = { active: 'Active',  inactive: 'Inactive' };
  const severityCols = { low: 'secondary', medium: 'warning', high: 'danger' };
  const channelLbls  = { email: 'Email', sms: 'SMS', whatsapp: 'WhatsApp', in_app: 'In-App' };

  const table = $('#sfaSearchTable').DataTable({
    ajax: { url: '/modules/settings/notifications/stg_financial_alerts/stg_financial_alerts_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'alert_name' },
      { data: 'condition_field', render: (d) => `<span class="font-monospace">${d}</span>` },
      { data: 'operator' },
      { data: 'threshold_value', render: (d) => fmt(d) },
      { data: 'channel', render: (d) => channelLbls[d] || d },
      {
        data: 'severity',
        render: (d) => '<span class="badge text-bg-' + (severityCols[d] || 'secondary') + '">' + (d ? d.charAt(0).toUpperCase() + d.slice(1) : '') + '</span>',
      },
      { data: 'is_enabled', render: (d) => d ? '<span class="badge text-bg-primary">Yes</span>' : '<span class="badge text-bg-light text-dark">No</span>' },
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
      info:       'Showing _START_ to _END_ of _TOTAL_ alert rules',
    },
  });

  $('#sfa-search-name').on('keyup',      function () { table.column(1).search(this.value).draw(); });
  $('#sfa-search-condition').on('keyup', function () { table.column(2).search(this.value).draw(); });
  $('#sfa-search-status').on('keyup',    function () { table.column(8).search(this.value).draw(); });

  $('#sfaSearchTable tbody').on('click', 'tr', function () {
    const data = table.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('sfaSearchModal')).hide();
    document.dispatchEvent(new CustomEvent('stg_financial_alerts-selected', { detail: data }));
  });

  $('#sfaSearchModal').on('shown.bs.modal', function () {
    table.ajax.reload(null, false);
    table.columns.adjust();
  });

});
