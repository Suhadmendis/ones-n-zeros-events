const { createApp } = Vue;

createApp({
    data() {
        return {
            systemName: 'loan',
            title: 'Loan',
            loading: false,
            saving: false,
            error: '',
            success: '',
            mode: 'view', // view | new | edit
            checkGL: true,
            moduleVisibility: {},
            form: {
                ref: '', date: '', recipient_type: 'driver',
                driver_ref: '', driver_name: '',
                cleaner_ref: '', cleaner_name: '',
                principal_amount: '', recovered_amount: '0', status: 'active'
            }
        };
    },
    watch: {
        checkGL(val) {
            axios.post('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=save', { check_gl_default: val });
        },
    },
    computed: {
        financeEnabled() {
            return this.moduleVisibility.finance !== false;
        },
        jeTotalAmount() {
            const v = parseFloat(this.form.principal_amount);
            return isNaN(v) || v <= 0 ? 0 : v;
        },
    },
    mounted() {
        this.fetchRefNumber();
        axios.get('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=get')
            .then(res => { this.checkGL = !!res.data.check_gl_default; })
            .catch(() => {});
        axios.get('/server/module_visibility/module_visibility.php')
            .then(res => { this.moduleVisibility = res.data; })
            .catch(() => {});
        window.addEventListener('loan-driver-selected', (e) => {
            this.form.driver_ref  = e.detail.ref;
            this.form.driver_name = e.detail.name;
        });
        window.addEventListener('loan-cleaner-selected', (e) => {
            this.form.cleaner_ref  = e.detail.ref;
            this.form.cleaner_name = e.detail.name;
        });
        window.addEventListener('loan-selected', (e) => {
            this.loadRecord(e.detail);
        });
    },
    methods: {
        fmtAmount(v) {
            return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        fetchRefNumber() {
            this.loading = true;
            axios.get('/server/general/module_data.php?system_name=' + this.systemName)
                .then(res => { this.form.ref = res.data.ref; this.title = res.data.module || 'Loan'; })
                .catch(() => { this.error = 'Failed to load reference number.'; })
                .finally(() => { this.loading = false; });
        },
        newRecord() {
            this.mode = 'new';
            this.error = '';
            this.success = '';
            this.form = {
                ref: '', date: '', recipient_type: 'driver',
                driver_ref: '', driver_name: '',
                cleaner_ref: '', cleaner_name: '',
                principal_amount: '', recovered_amount: '0', status: 'active'
            };
            this.fetchRefNumber();
        },
        loadRecord(record) {
            this.mode = 'edit';
            this.error = '';
            this.success = '';
            this.form = {
                ref: record.ref,
                date: record.date,
                recipient_type: record.recipient_type,
                driver_ref: record.driver_ref || '',
                driver_name: record.recipient_type === 'driver' ? record.recipient_name : '',
                cleaner_ref: record.cleaner_ref || '',
                cleaner_name: record.recipient_type === 'cleaner' ? record.recipient_name : '',
                principal_amount: record.principal_amount,
                recovered_amount: record.recovered_amount,
                status: record.status
            };
        },
        saveRecord() {
            this.error = '';
            this.success = '';
            if (!this.form.date) { this.error = 'Date is required.'; return; }
            if (this.form.recipient_type === 'driver' && !this.form.driver_ref) { this.error = 'Please select a driver.'; return; }
            if (this.form.recipient_type === 'cleaner' && !this.form.cleaner_ref) { this.error = 'Please select a cleaner.'; return; }
            if (!this.form.principal_amount) { this.error = 'Principal amount is required.'; return; }

            this.saving = true;

            const proceed = (action) => {
                axios.post('loan_data.php?action=' + action, this.form)
                    .then(res => {
                        if (res.data.success) {
                            this.success = 'Loan saved successfully. Ref: ' + res.data.ref;
                            this.form.ref = res.data.ref;
                            this.mode = 'view';
                        } else {
                            this.error = (res.data.errors || ['Save failed.']).join(' ');
                        }
                    })
                    .catch(() => { this.error = 'Network error. Please try again.'; })
                    .finally(() => { this.saving = false; });
            };

            if (this.mode === 'edit') {
                axios.get('loan_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
                    .then(res => {
                        if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
                        proceed('update');
                    })
                    .catch(() => { this.error = 'Failed to verify record.'; this.saving = false; });
            } else {
                proceed('save');
            }
        },
        resetForm() {
            this.error = '';
            this.success = '';
            this.form.date = '';
            this.form.recipient_type = 'driver';
            this.form.driver_ref = ''; this.form.driver_name = '';
            this.form.cleaner_ref = ''; this.form.cleaner_name = '';
            this.form.principal_amount = '';
            this.form.recovered_amount = '0';
            this.form.status = 'active';
        },
        printRecord() {
            if (!this.form.ref) { alert('No record to print.'); return; }
            const rt = this.form.recipient_type;
            const rref  = rt === 'driver' ? this.form.driver_ref  : this.form.cleaner_ref;
            const rname = rt === 'driver' ? this.form.driver_name : this.form.cleaner_name;
            const params = new URLSearchParams({
                ref: this.form.ref,
                date: this.form.date,
                recipient_type: rt,
                recipient_ref: rref,
                recipient_name: rname,
                principal_amount: this.form.principal_amount,
                recovered_amount: this.form.recovered_amount,
                status: this.form.status
            });
            window.open('loan_print.php?' + params.toString(), '_blank');
        },
        openSearch() {
            const modal = new bootstrap.Modal(document.getElementById('loanSearchModal'));
            modal.show();
        },
        openDriverPicker() {
            const modal = new bootstrap.Modal(document.getElementById('loanDriverPickerModal'));
            modal.show();
        },
        openCleanerPicker() {
            const modal = new bootstrap.Modal(document.getElementById('loanCleanerPickerModal'));
            modal.show();
        }
    }
}).mount('#loan-app');
