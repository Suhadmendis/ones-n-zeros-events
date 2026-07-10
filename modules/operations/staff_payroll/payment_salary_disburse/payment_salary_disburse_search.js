$(function () {
    const table = $('#paymentSearchTable').DataTable({
        ajax: { url: '/modules/operations/staff_payroll/payment_salary_disburse/payment_salary_disburse_data.php?action=list', dataSrc: '' },
        columns: [
            { data: 'ref' },
            { data: 'date' },
            { data: 'recipient_type', render: d => d.charAt(0).toUpperCase() + d.slice(1) },
            { data: 'recipient_name', defaultContent: '' },
            { data: 'payment_type', defaultContent: '' },
            { data: 'amount', render: d => parseFloat(d).toLocaleString('en-LK', { minimumFractionDigits: 2 }) },
            { data: 'remark', defaultContent: '' },
            {
                data: null,
                orderable: false,
                render: (d, t, row) => {
                    const params = new URLSearchParams({
                        ref: row.ref,
                        date: row.date,
                        recipient_type: row.recipient_type,
                        recipient_ref: row.recipient_ref || '',
                        recipient_name: row.recipient_name || '',
                        payment_type: row.payment_type || '',
                        amount: row.amount,
                        remark: row.remark || ''
                    });
                    return `<a href="/modules/operations/staff_payroll/payment_salary_disburse/payment_salary_disburse_print.php?${params}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a>`;
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
