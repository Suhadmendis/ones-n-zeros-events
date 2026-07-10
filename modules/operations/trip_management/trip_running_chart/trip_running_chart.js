// trip_running_chart.js — Trip / Running Chart Vue app

const { createApp } = Vue;

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading:  false,
      saving:   false,
      saved:    false,
      error:    '',
      checkGL:  true,
      moduleVisibility: {},
      isExisting: false,
      form: {
        ref:               '',
        vehicle_ref:       '',
        vehicle_plate:     '',
        date:              '',
        opening_km:        '',
        closing_km:        '',
        mileage:           '',
        driver_ref:        '',
        driver_name:       '',
        cleaner_ref:       '',
        cleaner_name:      '',
        item_ref:          '',
        item_name_display: '',
        item_name:         '',
        run_no:            '',
        from_loc:          '',
        to_loc:            '',
        amount:            '',
        driver_salary:     '',
        cleaner_salary:    '',
        department:        '',
        remark:            '',
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
        this.form.driver_ref  !== '' ||
        this.form.date        !== '' ||
        this.form.from_loc.trim() !== '' ||
        this.form.to_loc.trim()   !== '' ||
        this.form.amount      !== ''
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

    document.addEventListener('trip-vehicle-selected', (e) => {
      const v = e.detail;
      this.form.vehicle_ref   = v.ref;
      this.form.vehicle_plate = v.plate_number;
    });

    document.addEventListener('trip-driver-selected', (e) => {
      const d = e.detail;
      this.form.driver_ref  = d.ref;
      this.form.driver_name = d.name;
    });

    document.addEventListener('trip-cleaner-selected', (e) => {
      const c = e.detail;
      this.form.cleaner_ref  = c.ref;
      this.form.cleaner_name = c.name;
    });

    document.addEventListener('trip-item-selected', (e) => {
      const i = e.detail;
      this.form.item_ref          = i.ref;
      this.form.item_name_display = i.name;
      if (!this.form.item_name) this.form.item_name = i.name;
    });

    document.addEventListener('trip-running-chart-selected', (e) => this.loadRecord(e.detail));
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

    calcMileage() {
      const o = parseFloat(this.form.opening_km) || 0;
      const c = parseFloat(this.form.closing_km) || 0;
      this.form.mileage = c > o ? (c - o).toFixed(2) : '';
    },

    clearCleaner() {
      this.form.cleaner_ref  = '';
      this.form.cleaner_name = '';
    },

    clearItem() {
      this.form.item_ref          = '';
      this.form.item_name_display = '';
    },

    loadRecord(data) {
      this.form.ref               = data.ref;
      this.form.vehicle_ref       = data.vehicle_ref       ?? data.vehicles?.ref          ?? '';
      this.form.vehicle_plate     = data.vehicles?.plate_number ?? '';
      this.form.date              = data.date;
      this.form.opening_km        = data.opening_km;
      this.form.closing_km        = data.closing_km;
      this.form.mileage           = data.mileage;
      this.form.driver_ref        = data.driver_ref        ?? data.drivers?.ref  ?? '';
      this.form.driver_name       = data.drivers?.name ?? '';
      this.form.cleaner_ref       = data.cleaner_ref       ?? data.cleaners?.ref  ?? '';
      this.form.cleaner_name      = data.cleaners?.name ?? '';
      this.form.item_ref          = data.item_ref          ?? data.items?.ref  ?? '';
      this.form.item_name_display = data.items?.name ?? '';
      this.form.item_name         = data.item_name   ?? '';
      this.form.run_no            = data.run_no      ?? '';
      this.form.from_loc          = data.from_loc;
      this.form.to_loc            = data.to_loc;
      this.form.amount            = data.amount;
      this.form.driver_salary     = data.driver_salary  ?? '';
      this.form.cleaner_salary    = data.cleaner_salary ?? '';
      this.form.department        = data.department ?? '';
      this.form.remark            = data.remark     ?? '';
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onEdit()   { console.log('Edit clicked'); },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/operations/trip_management/trip_running_chart/trip_running_chart_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
            opening_km:    this.form.opening_km,
            closing_km:    this.form.closing_km,
            mileage:       this.form.mileage,
            driver_ref:    this.form.driver_ref,
            driver_name:   this.form.driver_name,
            cleaner_ref:   this.form.cleaner_ref,
            cleaner_name:  this.form.cleaner_name,
            item_ref:      this.form.item_ref,
            item_name:     this.form.item_name || this.form.item_name_display,
            run_no:        this.form.run_no,
            from_loc:      this.form.from_loc,
            to_loc:        this.form.to_loc,
            amount:          this.form.amount,
            driver_salary:   this.form.driver_salary,
            cleaner_salary:  this.form.cleaner_salary,
            department:    this.form.department,
            remark:        this.form.remark,
          });
          window.open('/modules/operations/trip_management/trip_running_chart/trip_running_chart_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },

    onReset() {
      this.form.vehicle_ref       = '';
      this.form.vehicle_plate     = '';
      this.form.date              = '';
      this.form.opening_km        = '';
      this.form.closing_km        = '';
      this.form.mileage           = '';
      this.form.driver_ref        = '';
      this.form.driver_name       = '';
      this.form.cleaner_ref       = '';
      this.form.cleaner_name      = '';
      this.form.item_ref          = '';
      this.form.item_name_display = '';
      this.form.item_name         = '';
      this.form.run_no            = '';
      this.form.from_loc          = '';
      this.form.to_loc            = '';
      this.form.amount            = '';
      this.form.driver_salary     = '';
      this.form.cleaner_salary    = '';
      this.form.department        = '';
      this.form.remark            = '';
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
        axios.post('/modules/operations/trip_management/trip_running_chart/trip_running_chart_data.php?action=' + action, this.form)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => {
            this.error = err.response?.data?.error ?? 'Failed to save trip.';
            console.error(err);
          })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/operations/trip_management/trip_running_chart/trip_running_chart_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
}).mount('#trip-app');
