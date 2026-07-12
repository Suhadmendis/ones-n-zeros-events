// fuel_entry.js — Fuel entry module Vue app

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
        ref:           '',
        vehicle_ref:   '',
        vehicle_plate: '',
        date:          '',
        liters:        '',
        rate:          '',
        total:         '',
        voucher_number: '',
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
        this.form.date        !== '' ||
        this.form.liters     !== ''   ||
        this.form.rate       !== ''   ||
        this.form.voucher_number.trim() !== ''
      );
    },
    financeEnabled() {
      return this.moduleVisibility.finance === true;
    },
    jeTotalAmount() {
      const v = parseFloat(this.form.total);
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
    document.addEventListener('fuel-vehicle-selected', (e) => {
      const v = e.detail;
      this.form.vehicle_ref   = v.ref;
      this.form.vehicle_plate = v.plate_number;
    });
    document.addEventListener('fuel-entry-selected', (e) => this.loadEntry(e.detail));
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

    calcTotal() {
      const l = parseFloat(this.form.liters) || 0;
      const r = parseFloat(this.form.rate)   || 0;
      this.form.total = (l * r).toFixed(2);
    },

    loadEntry(data) {
      this.form.ref           = data.ref;
      this.form.vehicle_ref   = data.vehicle_ref ?? data.vehicles?.ref ?? '';
      this.form.vehicle_plate = data.vehicles?.plate_number ?? '';
      this.form.date          = data.date;
      this.form.liters        = data.liters;
      this.form.rate          = data.rate;
      this.form.total         = data.total;
      this.form.voucher_number = data.voucher_number ?? '';
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onEdit()   { console.log('Edit clicked'); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/operations/fuel_entry/fuel_entry_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const params = new URLSearchParams({
            ref:           this.form.ref,
            vehicle_ref:   this.form.vehicle_ref,
            vehicle_plate: this.form.vehicle_plate,
            date:          this.form.date,
            liters:        this.form.liters,
            rate:          this.form.rate,
            total:         this.form.total,
            voucher_number: this.form.voucher_number,
          });
          window.open('/modules/operations/fuel_entry/fuel_entry_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.form.vehicle_ref   = '';
      this.form.vehicle_plate = '';
      this.form.date          = '';
      this.form.liters          = '';
      this.form.rate            = '';
      this.form.total           = '';
      this.form.voucher_number  = '';
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
        axios.post('fuel_entry_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = 'Failed to save fuel entry.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('fuel_entry_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#fuel-app');
