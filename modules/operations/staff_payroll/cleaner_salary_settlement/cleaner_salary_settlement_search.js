$(document).ready(function () {
  function fmtAmt(v) {
    const n = parseFloat(v) || 0;
    return n.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function statusBadge(s) {
    const color = s === 'paid' ? 'success' : 'warning';
    return '<span class="badge bg-' + color + '">' + (s || '').toUpperCase() + '</span>';
  }

  const table = $('#cleanerSettlementTable').DataTable({
    ajax: {
      url: '/modules/operations/staff_payroll/cleaner_salary_settlement/cleaner_salary_settlement_data.php?action=list',
      dataSrc: '',
    },
    columns: [
      { data: 'ref' },
      { data: null, render: r => (r.cleaners?.name || '') + (r.cleaners?.ref ? ' (' + r.cleaners.ref + ')' : '') },
      { data: 'month' },
      { data: 'gross_earnings', render: fmtAmt },
      { data: 'advances', render: fmtAmt },
      { data: 'net_payable', render: v => '<strong>' + fmtAmt(v) + '</strong>' },
      { data: 'status', render: statusBadge },
    ],
    dom: 'Bfrtip',
    buttons: ['copy', 'excel', 'pdf', 'print'],
    order: [[0, 'desc']],
    pageLength: 20,
    language: { emptyTable: 'No settlement records found.' },
  });

  $('#cleanerSettlementTable tbody').on('click', 'tr', function () {
    const row = table.row(this).data();
    if (!row) return;
    window.parent.dispatchEvent(new CustomEvent('cleaner-settlement-selected', { detail: row }));
  });
});
