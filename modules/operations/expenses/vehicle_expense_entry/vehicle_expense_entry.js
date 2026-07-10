// vehicle_expense_entry.js — Vehicle expense module Vue app

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
        ref:             '',
        vehicle_ref:     '',
        vehicle_display: '',
        category:        '',
        remark:          '',
        amount:          '',
        date:            '',
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
        this.form.vehicle_ref !== '' ||
        this.form.category   !== ''   ||
        this.form.remark.trim() !== '' ||
        this.form.amount     !== ''   ||
        this.form.date       !== ''
      );
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
    document.addEventListener('vex-vehicle-selected', (e) => {
      const v = e.detail;
      this.form.vehicle_ref     = v.ref;
      this.form.vehicle_display = v.ref + ' — ' + v.plate_number + ' (' + v.make + ' ' + v.model + ')';
    });
    document.addEventListener('vex-entry-selected', (e) => this.loadEntry(e.detail));
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
      this.form.ref             = data.ref;
      this.form.vehicle_ref     = data.vehicle_ref ?? data.vehicles?.ref ?? '';
      this.form.vehicle_display = data.vehicles
        ? data.vehicles.ref + ' — ' + data.vehicles.plate_number + ' (' + data.vehicles.make + ')'
        : '';
      this.form.category = data.category;
      this.form.remark   = data.remark;
      this.form.amount   = data.amount;
      this.form.date     = data.date;
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onEdit()   { console.log('Edit clicked'); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/operations/vehicle_expense_entry/vehicle_expense_entry_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:             this.form.ref,
            vehicle_display: this.form.vehicle_display,
            category:        this.form.category,
            remark:          this.form.remark,
            amount:          this.form.amount,
            date:            this.form.date,
          });
          window.open('/modules/operations/vehicle_expense_entry/vehicle_expense_entry_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.form.vehicle_ref     = '';
      this.form.vehicle_display = '';
      this.form.category        = '';
      this.form.remark          = '';
      this.form.amount          = '';
      this.form.date            = '';
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
        axios.post('vehicle_expense_entry_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = 'Failed to save vehicle expense.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('vehicle_expense_entry_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#vex-app');
