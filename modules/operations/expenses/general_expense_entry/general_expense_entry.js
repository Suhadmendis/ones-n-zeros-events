// general_expense_entry.js — General expense entry Vue app

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
      checkGL: true,
      moduleVisibility: {},
      form: {
        ref:               '',
        expense_type_ref:  '',
        expense_type_name: '',
        remark:            '',
        amount:            '',
        date:              '',
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
      return (
        this.form.expense_type_ref !== '' ||
        this.form.amount          !== ''   ||
        this.form.date            !== ''   ||
        this.form.remark.trim()   !== ''
      );
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
    document.addEventListener('gex-expense-type-selected', (e) => {
      const t = e.detail;
      this.form.expense_type_ref  = t.ref;
      this.form.expense_type_name = t.name;
    });
    document.addEventListener('gex-entry-selected', (e) => this.loadEntry(e.detail));
  },

  methods: {
    fmtAmount(v) {
      return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    loadEntry(data) {
      this.form.ref               = data.ref;
      this.form.expense_type_ref  = data.expense_type_ref ?? data.general_expense_types?.ref ?? '';
      this.form.expense_type_name = data.general_expense_types?.name ?? '';
      this.form.remark            = data.remark  ?? '';
      this.form.amount            = data.amount;
      this.form.date              = data.date;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onEdit()   { console.log('Edit clicked'); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/operations/general_expense_entry/general_expense_entry_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:               this.form.ref,
            expense_type_ref:  this.form.expense_type_ref,
            expense_type_name: this.form.expense_type_name,
            remark:            this.form.remark,
            amount:            this.form.amount,
            date:              this.form.date,
          });
          window.open('/modules/operations/general_expense_entry/general_expense_entry_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.form.expense_type_ref  = '';
      this.form.expense_type_name = '';
      this.form.remark            = '';
      this.form.amount            = '';
      this.form.date              = '';
      this.isExisting = false;
      this.saved = false;
      this.error = '';
    },

    onSave() {
      if (!this.isDirty) return;
      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const proceed = (action) => {
        axios.post('general_expense_entry_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = 'Failed to save expense entry.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('general_expense_entry_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#gex-app');
