// budgeting.js — Budgeting Vue app

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
      isExisting: false,
      form: {
        ref:           '',
        budget_name:   '',
        account_code:  '',
        period:        '',
        budget_type:   'Monthly',
        category:      '',
        budget_amount: '',
        actual_amount: '',
        notes:         '',
        status:        'draft',
      },
    };
  },

  computed: {
    variance() {
      return (parseFloat(this.form.budget_amount) || 0) - (parseFloat(this.form.actual_amount) || 0);
    },
    isDirty() {
      return this.form.budget_name.trim() !== '' || this.form.account_code.trim() !== '';
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('budgeting-selected', (e) => this.loadRecord(e.detail));
  },

  methods: {
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
      this.form.budget_name   = data.budget_name;
      this.form.account_code  = data.account_code;
      this.form.period        = data.period;
      this.form.budget_type   = data.budget_type;
      this.form.category      = data.category;
      this.form.budget_amount = data.budget_amount;
      this.form.actual_amount = data.actual_amount;
      this.form.notes         = data.notes  ?? '';
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
      axios.get('/modules/finance/assets_budgeting/budgeting/budgeting_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) { this.error = 'No saved record found. Please search and select a record to print.'; return; }
          const params = new URLSearchParams({
            ref:           this.form.ref,
            budget_name:   this.form.budget_name,
            account_code:  this.form.account_code,
            period:        this.form.period,
            budget_type:   this.form.budget_type,
            category:      this.form.category,
            budget_amount: this.form.budget_amount,
            actual_amount: this.form.actual_amount,
            variance:      this.variance,
            notes:         this.form.notes,
            status:        this.form.status,
          });
          window.open('/modules/finance/assets_budgeting/budgeting/budgeting_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.budget_name   = '';
      this.form.account_code  = '';
      this.form.period        = '';
      this.form.budget_type   = 'Monthly';
      this.form.category      = '';
      this.form.budget_amount = '';
      this.form.actual_amount = '';
      this.form.notes         = '';
      this.form.status        = 'draft';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      if (this.form.budget_name.trim() === '')  { this.error = 'Budget name is required.'; return; }
      if (this.form.account_code.trim() === '') { this.error = 'Account code is required.'; return; }
      if (this.form.period === '')              { this.error = 'Period is required.'; return; }
      if (this.form.category === '')            { this.error = 'Category is required.'; return; }
      if ((parseFloat(this.form.budget_amount) || 0) <= 0) { this.error = 'Budget amount must be greater than zero.'; return; }
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('/modules/finance/assets_budgeting/budgeting/budgeting_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = err.response?.data?.error || 'Failed to save budget.'; })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/finance/assets_budgeting/budgeting/budgeting_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#bdg-app');
