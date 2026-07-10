// accounts_payable.js — Accounts Payable Vue app

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading: false,
      saving:  false,
      saved:   false,
      error:   '',
      checkGL: true,
      moduleVisibility: {},
      isExisting: false,
      form: {
        ref:           '',
        supplier_name: '',
        supplier_ref:  '',
        invoice_ref:   '',
        invoice_date:  '',
        due_date:      '',
        amount:        '',
        amount_paid:   '',
        description:   '',
        status:        'open',
      },
    };
  },

  watch: {
    checkGL(val) {
      axios.post('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=save', { check_gl_default: val });
    },
  },

  computed: {
    balance() {
      return (parseFloat(this.form.amount) || 0) - (parseFloat(this.form.amount_paid) || 0);
    },
    isDirty() {
      return this.form.supplier_name.trim() !== '' || this.form.invoice_date !== '';
    },
    financeEnabled() {
      return this.moduleVisibility.finance !== false;
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
    document.addEventListener('accounts_payable-selected', (e) => this.loadRecord(e.detail));
  },

  methods: {
    fmtAmount(v) {
      return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(() => { this.error = 'Failed to load reference number.'; })
        .finally(() => { this.loading = false; });
    },

    fmtNum(v) {
      return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    loadRecord(data) {
      this.form.ref           = data.ref;
      this.form.supplier_name = data.supplier_name;
      this.form.supplier_ref  = data.supplier_ref  ?? '';
      this.form.invoice_ref   = data.invoice_ref   ?? '';
      this.form.invoice_date  = data.invoice_date;
      this.form.due_date      = data.due_date;
      this.form.amount        = data.amount;
      this.form.amount_paid   = data.amount_paid;
      this.form.description   = data.description   ?? '';
      this.form.status        = data.status;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/finance/receivables_payables/accounts_payable/accounts_payable_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) { this.error = 'No saved record found. Please search and select a record to print.'; return; }
          const params = new URLSearchParams({
            ref:           this.form.ref,
            supplier_name: this.form.supplier_name,
            supplier_ref:  this.form.supplier_ref,
            invoice_ref:   this.form.invoice_ref,
            invoice_date:  this.form.invoice_date,
            due_date:      this.form.due_date,
            amount:        this.form.amount,
            amount_paid:   this.form.amount_paid,
            balance:       this.balance,
            description:   this.form.description,
            status:        this.form.status,
          });
          window.open('/modules/finance/accounts_payable/accounts_payable_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.supplier_name = '';
      this.form.supplier_ref  = '';
      this.form.invoice_ref   = '';
      this.form.invoice_date  = '';
      this.form.due_date      = '';
      this.form.amount        = '';
      this.form.amount_paid   = '';
      this.form.description   = '';
      this.form.status        = 'open';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.form.supplier_name.trim() === '') { this.error = 'Supplier name is required.'; return; }
      if (this.form.invoice_date === '')          { this.error = 'Invoice date is required.'; return; }
      if (this.form.due_date === '')              { this.error = 'Due date is required.'; return; }
      if ((parseFloat(this.form.amount) || 0) <= 0) { this.error = 'Invoice amount must be greater than zero.'; return; }
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('/modules/finance/receivables_payables/accounts_payable/accounts_payable_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save record.'; })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/finance/receivables_payables/accounts_payable/accounts_payable_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
            proceed('update');
          })
          .catch(err => { this.error = 'Failed to verify record.'; console.error(err); this.saving = false; });
      } else {
        proceed('save');
      }
    },
  },
}).mount('#ap-app');
