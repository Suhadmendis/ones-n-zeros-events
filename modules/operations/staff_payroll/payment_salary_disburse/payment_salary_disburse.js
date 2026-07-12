const { createApp } = Vue;

createApp({
    data() {
        return {
            systemName: 'payment_salary_disburse',
            title: 'Payment / Salary Disburse',
            loading: false,
            saving: false,
            error: '',
            success: '',
            mode: 'view',
            checkGL: true,
            moduleVisibility: {},
            form: {
                ref: '', date: '', recipient_type: 'driver',
                driver_ref: '', driver_name: '',
                cleaner_ref: '', cleaner_name: '',
                payment_type: 'Salary', amount: '', remark: ''
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
            return this.moduleVisibility.finance === true;
        },
        jeTotalAmount() {
            const v = parseFloat(this.form.amount);
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
        window.addEventListener('payment-driver-selected', (e) => {
            this.form.driver_ref  = e.detail.ref;
            this.form.driver_name = e.detail.name;
        });
        window.addEventListener('payment-cleaner-selected', (e) => {
            this.form.cleaner_ref  = e.detail.ref;
            this.form.cleaner_name = e.detail.name;
        });
        window.addEventListener('payment-selected', (e) => {
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
                .then(res => { this.form.ref = res.data.ref; this.title = res.data.module || 'Payment / Salary Disburse'; })
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
                payment_type: 'Salary', amount: '', remark: ''
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
                payment_type: record.payment_type,
                amount: record.amount,
                remark: record.remark || ''
            };
        },
        saveRecord() {
            this.error = '';
            this.success = '';
            if (!this.form.date) { this.error = 'Date is required.'; return; }
            if (this.form.recipient_type === 'driver' && !this.form.driver_ref) { this.error = 'Please select a driver.'; return; }
            if (this.form.recipient_type === 'cleaner' && !this.form.cleaner_ref) { this.error = 'Please select a cleaner.'; return; }
            if (!this.form.amount) { this.error = 'Amount is required.'; return; }
            if (!this.form.payment_type) { this.error = 'Payment type is required.'; return; }

            this.saving = true;

            const proceed = (action) => {
                axios.post('payment_salary_disburse_data.php?action=' + action, this.form)
                    .then(res => {
                        if (res.data.success) {
                            this.success = 'Payment saved successfully. Ref: ' + res.data.ref;
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
                axios.get('payment_salary_disburse_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
            this.form.payment_type = 'Salary';
            this.form.amount = '';
            this.form.remark = '';
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
                payment_type: this.form.payment_type,
                amount: this.form.amount,
                remark: this.form.remark
            });
            window.open('payment_salary_disburse_print.php?' + params.toString(), '_blank');
        },
        openSearch() {
            const modal = new bootstrap.Modal(document.getElementById('paymentSearchModal'));
            modal.show();
        },
        openDriverPicker() {
            const modal = new bootstrap.Modal(document.getElementById('paymentDriverPickerModal'));
            modal.show();
        },
        openCleanerPicker() {
            const modal = new bootstrap.Modal(document.getElementById('paymentCleanerPickerModal'));
            modal.show();
        }
    }
}).mount('#payment-app');
