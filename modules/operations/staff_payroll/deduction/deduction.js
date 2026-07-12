const { createApp } = Vue;

createApp({
    data() {
        const today = new Date().toISOString().split('T')[0];
        return {
            systemName: 'deduction',
            title: 'Deduction',
            loading: false,
            saving: false,
            error: '',
            success: '',
            isExisting: false,
            checkGL: true,
            moduleVisibility: {},
            form: {
                id: null,
                ref: '',
                date: today,
                recipient_type: '',
                driver_ref: '',
                cleaner_ref: '',
                recipient_ref: '',
                recipient_name: '',
                amount: '',
                reason: '',
            },
            today,
        };
    },
    computed: {
        isDirty() {
            return this.form.amount !== '' || this.form.reason.trim() !== '';
        },
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

        document.addEventListener('deduction-selected', (e) => this.loadRecord(e.detail));

        document.addEventListener('deduction-driver-selected', (e) => {
            this.form.driver_ref = e.detail.ref;
            this.form.cleaner_ref = '';
            this.form.recipient_ref = e.detail.ref;
            this.form.recipient_name = e.detail.name;
        });

        document.addEventListener('deduction-cleaner-selected', (e) => {
            this.form.cleaner_ref = e.detail.ref;
            this.form.driver_ref = '';
            this.form.recipient_ref = e.detail.ref;
            this.form.recipient_name = e.detail.name;
        });
    },
    watch: {
        'form.recipient_type'() {
            this.form.driver_ref = '';
            this.form.cleaner_ref = '';
            this.form.recipient_ref = '';
            this.form.recipient_name = '';
        },
        checkGL(val) {
            axios.post('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=save', { check_gl_default: val });
        },
    },
    methods: {
        fmtAmount(v) {
            return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        fetchRefNumber() {
            this.loading = true;
            axios.get('/modules/operations/deduction/deduction_data.php?action=next_ref')
                .then(res => {
                    this.form.ref = res.data.ref;
                    if (res.data.module) this.title = res.data.module;
                })
                .catch(() => { this.error = 'Failed to load reference number.'; })
                .finally(() => { this.loading = false; });
        },

        loadRecord(detail) {
            this.error = '';
            this.success = '';
            this.form.id = detail.id;
            this.form.ref = detail.ref;
            this.form.date = detail.date;
            this.form.recipient_type = detail.recipient_type ?? '';
            this.form.driver_ref = detail.driver_ref ?? '';
            this.form.cleaner_ref = detail.cleaner_ref ?? '';
            this.form.recipient_ref = detail.recipient_ref ?? '';
            this.form.recipient_name = detail.recipient_name ?? '';
            this.form.amount = detail.amount ?? '';
            this.form.reason = detail.reason ?? '';
            this.isExisting = true;
        },

        newRecord() {
            this.error = '';
            this.success = '';
            this.isExisting = false;
            this.form.id = null;
            this.form.date = this.today;
            this.form.recipient_type = '';
            this.form.driver_ref = '';
            this.form.cleaner_ref = '';
            this.form.recipient_ref = '';
            this.form.recipient_name = '';
            this.form.amount = '';
            this.form.reason = '';
            this.fetchRefNumber();
        },

        openDriverPicker() {
            const modal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('deductionDriverPickerModal')
            );
            modal.show();
        },

        openCleanerPicker() {
            const modal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('deductionCleanerPickerModal')
            );
            modal.show();
        },

        searchRecord() {
            const modal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('deductionSearchModal')
            );
            modal.show();
        },

        saveRecord() {
            this.error = '';
            this.success = '';

            if (!this.form.date) { this.error = 'Date is required.'; return; }
            if (!this.form.recipient_type) { this.error = 'Recipient type is required.'; return; }
            if (this.form.recipient_type === 'driver' && !this.form.driver_ref) {
                this.error = 'Please select a driver.'; return;
            }
            if (this.form.recipient_type === 'cleaner' && !this.form.cleaner_ref) {
                this.error = 'Please select a cleaner.'; return;
            }
            if (this.form.amount === '' || parseFloat(this.form.amount) < 0) {
                this.error = 'A valid amount is required.'; return;
            }
            if (!this.form.reason.trim()) { this.error = 'Reason is required.'; return; }

            this.saving = true;

            const payload = {
                ref: this.form.ref,
                date: this.form.date,
                recipient_type: this.form.recipient_type,
                driver_ref: this.form.driver_ref,
                cleaner_ref: this.form.cleaner_ref,
                amount: parseFloat(this.form.amount),
                reason: this.form.reason.trim(),
            };

            const proceed = (action) => {
                axios.post('deduction_data.php?action=' + action, payload)
                .then(res => {
                    if (res.data.success) {
                        this.success = 'Deduction saved. Ref: ' + res.data.ref;
                        this.form.id = res.data.id;
                        this.form.ref = res.data.ref;
                        this.isExisting = true;
                    } else {
                        this.error = res.data.error ?? 'Save failed.';
                    }
                })
                .catch(() => { this.error = 'Server error while saving.'; })
                .finally(() => { this.saving = false; });
            };

            if (this.isExisting) {
                axios.get('deduction_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
                    .then(res => {
                        if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
                        proceed('update');
                    })
                    .catch(() => { this.error = 'Failed to verify record.'; this.saving = false; });
            } else {
                proceed('save');
            }
        },

        printRecord() {
            const p = new URLSearchParams({
                ref: this.form.ref,
                date: this.form.date,
                recipient_type: this.form.recipient_type,
                recipient_ref: this.form.recipient_ref,
                recipient_name: this.form.recipient_name,
                amount: this.form.amount,
                reason: this.form.reason,
            });
            window.open('/modules/operations/deduction/deduction_print.php?' + p.toString(), '_blank');
        },
    },
}).mount('#deduction-app');
