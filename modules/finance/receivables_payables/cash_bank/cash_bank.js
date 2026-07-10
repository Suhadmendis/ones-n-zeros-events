// cash_bank.js — Cash & Bank Vue app

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
        ref:              '',
        account_name:     '',
        account_number:   '',
        transaction_type: '',
        transaction_date: '',
        amount:           '',
        description:      '',
        reference_doc:    '',
        status:           'pending',
      },
    };
  },

  watch: {
    checkGL(val) {
      axios.post('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=save', { check_gl_default: val });
    },
  },

  computed: {
    isDirty() {
      return this.form.account_name.trim() !== '' || this.form.transaction_date !== '';
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
    document.addEventListener('cash_bank-selected', (e) => this.loadRecord(e.detail));
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

    loadRecord(data) {
      this.form.ref              = data.ref;
      this.form.account_name     = data.account_name;
      this.form.account_number   = data.account_number   ?? '';
      this.form.transaction_type = data.transaction_type;
      this.form.transaction_date = data.transaction_date;
      this.form.amount           = data.amount;
      this.form.description      = data.description      ?? '';
      this.form.reference_doc    = data.reference_doc    ?? '';
      this.form.status           = data.status;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/finance/receivables_payables/cash_bank/cash_bank_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) { this.error = 'No saved record found. Please search and select a record to print.'; return; }
          const params = new URLSearchParams({
            ref:              this.form.ref,
            account_name:     this.form.account_name,
            account_number:   this.form.account_number,
            transaction_type: this.form.transaction_type,
            transaction_date: this.form.transaction_date,
            amount:           this.form.amount,
            description:      this.form.description,
            reference_doc:    this.form.reference_doc,
            status:           this.form.status,
          });
          window.open('/modules/finance/cash_bank/cash_bank_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.account_name     = '';
      this.form.account_number   = '';
      this.form.transaction_type = '';
      this.form.transaction_date = '';
      this.form.amount           = '';
      this.form.description      = '';
      this.form.reference_doc    = '';
      this.form.status           = 'pending';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.form.account_name.trim() === '')  { this.error = 'Account name is required.'; return; }
      if (this.form.transaction_type === '')      { this.error = 'Transaction type is required.'; return; }
      if (this.form.transaction_date === '')      { this.error = 'Transaction date is required.'; return; }
      if ((parseFloat(this.form.amount) || 0) <= 0) { this.error = 'Amount must be greater than zero.'; return; }
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('/modules/finance/receivables_payables/cash_bank/cash_bank_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save transaction.'; })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/finance/receivables_payables/cash_bank/cash_bank_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#cb-app');
