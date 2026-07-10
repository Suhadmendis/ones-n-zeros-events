$(function () {
    const table = $('#loanSearchTable').DataTable({
        ajax: { url: '/modules/operations/staff_payroll/loan/loan_data.php?action=list', dataSrc: '' },
        columns: [
            { data: 'ref' },
            { data: 'date' },
            { data: 'recipient_type', render: d => d.charAt(0).toUpperCase() + d.slice(1) },
            { data: 'recipient_name', defaultContent: '' },
            { data: 'principal_amount', render: d => parseFloat(d).toLocaleString('en-LK', { minimumFractionDigits: 2 }) },
            { data: 'recovered_amount', render: d => parseFloat(d).toLocaleString('en-LK', { minimumFractionDigits: 2 }) },
            {
                data: 'status',
                render: d => `<span class="badge bg-${d === 'active' ? 'success' : 'secondary'}">${d.charAt(0).toUpperCase() + d.slice(1)}</span>`
            },
            {
                data: null,
                orderable: false,
                render: (d, t, row) => {
                    const params = new URLSearchParams({
                        ref: row.ref, date: row.date,
                        recipient_type: row.recipient_type,
                        recipient_ref: row.recipient_ref || '',
                        recipient_name: row.recipient_name || '',
                        principal_amount: row.principal_amount,
                        recovered_amount: row.recovered_amount,
                        status: row.status
                    });
                    return `<a href="/modules/operations/staff_payroll/loan/loan_print.php?${params}" target="_blank" class="btn btn-xs btn-outline-secondary btn-sm"><i class="bi bi-printer"></i></a>`;
                }
            }
        ],
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print'],
        pageLength: 25,
        responsive: true,
        order: [[0, 'desc']]
    });
});
