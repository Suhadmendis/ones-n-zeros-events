// cash_flow.js — Cash flow entry module Vue app

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
        ref:         '',
        date:        new Date().toISOString().slice(0, 10),
        flow_type:   '',
        category:    '',
        amount:      '',
        description: '',
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
      return this.form.category !== '' || this.form.amount !== '';
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
    document.addEventListener('cash-flow-selected', (e) => this.loadEntry(e.detail));
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
      this.form.ref         = data.ref;
      this.form.date        = data.date;
      this.form.flow_type   = data.flow_type;
      this.form.category    = data.category;
      this.form.amount      = data.amount;
      this.form.description = data.description ?? '';
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()  { this.onReset(); this.fetchRefNumber(); },
    onEdit() { console.log('Edit clicked'); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/operations/cash_flow/cash_flow_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:         this.form.ref,
            date:        this.form.date,
            flow_type:   this.form.flow_type,
            category:    this.form.category,
            amount:      this.form.amount,
            description: this.form.description,
          });
          window.open('/modules/operations/cash_flow/cash_flow_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.form.date        = new Date().toISOString().slice(0, 10);
      this.form.flow_type   = '';
      this.form.category    = '';
      this.form.amount      = '';
      this.form.description = '';
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
        axios.post('cash_flow_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => {
            this.error = err.response?.data?.error ?? 'Failed to save cash flow entry.';
            console.error(err);
          })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('cash_flow_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#cash-flow-app');
