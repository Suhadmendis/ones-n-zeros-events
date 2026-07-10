$(function () {

    /* ------------------------------------------------------------------ */
    /* Main search table — Deductions                                       */
    /* ------------------------------------------------------------------ */
    let searchTable = null;

    $('#deductionSearchModal').on('shown.bs.modal', function () {
        if (searchTable) {
            searchTable.ajax.reload(null, false);
            return;
        }
        searchTable = $('#deductionSearchTable').DataTable({
            ajax: {
                url: '/modules/operations/staff_payroll/deduction/deduction_data.php?action=list',
                dataSrc: '',
            },
            columns: [
                { data: 'ref' },
                { data: 'date' },
                {
                    data: 'recipient_type',
                    render: (val) => val ? val.charAt(0).toUpperCase() + val.slice(1) : '',
                },
                { data: 'recipient_name', defaultContent: '' },
                {
                    data: 'amount',
                    render: (val) => val !== null ? 'LKR ' + parseFloat(val).toFixed(2) : '',
                    className: 'text-end',
                },
                { data: 'reason', defaultContent: '' },
            ],
            order: [[0, 'desc']],
            pageLength: 15,
            dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                 "<'row'<'col-12'B>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: ['copy', 'excel', 'pdf', 'print'],
            language: { search: 'Filter:' },
        });

        $('#deductionSearchTable tbody').on('click', 'tr', function () {
            const row = searchTable.row(this).data();
            if (!row) return;
            document.dispatchEvent(new CustomEvent('deduction-selected', { detail: row }));
            bootstrap.Modal.getInstance(document.getElementById('deductionSearchModal')).hide();
        });
    });

    /* ------------------------------------------------------------------ */
    /* Driver picker table                                                  */
    /* ------------------------------------------------------------------ */
    let driverTable = null;

    $('#deductionDriverPickerModal').on('shown.bs.modal', function () {
        if (driverTable) {
            driverTable.ajax.reload(null, false);
            return;
        }
        driverTable = $('#deductionDriverTable').DataTable({
            ajax: {
                url: '/modules/operations/staff_payroll/deduction/deduction_data.php?action=list_drivers',
                dataSrc: '',
            },
            columns: [
                { data: 'ref' },
                { data: 'name' },
                { data: 'phone', defaultContent: '' },
                {
                    data: 'status',
                    render: (val) => {
                        const cls = val === 'active' ? 'success' : 'secondary';
                        return `<span class="badge bg-${cls}">${val}</span>`;
                    },
                },
            ],
            order: [[1, 'asc']],
            pageLength: 10,
            language: { search: 'Filter:' },
        });

        $('#deductionDriverTable tbody').on('click', 'tr', function () {
            const row = driverTable.row(this).data();
            if (!row) return;
            document.dispatchEvent(new CustomEvent('deduction-driver-selected', { detail: row }));
            bootstrap.Modal.getInstance(document.getElementById('deductionDriverPickerModal')).hide();
        });
    });

    /* ------------------------------------------------------------------ */
    /* Cleaner picker table                                                 */
    /* ------------------------------------------------------------------ */
    let cleanerTable = null;

    $('#deductionCleanerPickerModal').on('shown.bs.modal', function () {
        if (cleanerTable) {
            cleanerTable.ajax.reload(null, false);
            return;
        }
        cleanerTable = $('#deductionCleanerTable').DataTable({
            ajax: {
                url: '/modules/operations/staff_payroll/deduction/deduction_data.php?action=list_cleaners',
                dataSrc: '',
            },
            columns: [
                { data: 'ref' },
                { data: 'name' },
                { data: 'phone', defaultContent: '' },
                {
                    data: 'status',
                    render: (val) => {
                        const cls = val === 'active' ? 'success' : 'secondary';
                        return `<span class="badge bg-${cls}">${val}</span>`;
                    },
                },
            ],
            order: [[1, 'asc']],
            pageLength: 10,
            language: { search: 'Filter:' },
        });

        $('#deductionCleanerTable tbody').on('click', 'tr', function () {
            const row = cleanerTable.row(this).data();
            if (!row) return;
            document.dispatchEvent(new CustomEvent('deduction-cleaner-selected', { detail: row }));
            bootstrap.Modal.getInstance(document.getElementById('deductionCleanerPickerModal')).hide();
        });
    });

});
