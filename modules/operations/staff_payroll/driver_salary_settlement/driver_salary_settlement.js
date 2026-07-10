const { createApp } = Vue;

createApp({
  data() {
    return {
      editing: false,
      calculating: false,
      isExisting: false,
      checkGL: true,
      moduleVisibility: {},
      form: {
        id: null,
        ref: '',
        driver_ref: '',
        driver_name: '',
        month: '',
        gross_earnings: 0,
        advances: 0,
        deductions_total: 0,
        loan_recovery: 0,
        net_payable: 0,
        status: 'pending',
      },
      alert: { message: '', type: 'success' },
      driverPickerSearch: '',
      driverPickerLoading: false,
      drivers: [],
      driverPickerModal: null,
      searchModal: null,
    };
  },

  watch: {
    checkGL(val) {
      axios.post('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=save', { check_gl_default: val });
    },
  },

  computed: {
    filteredDrivers() {
      const q = this.driverPickerSearch.toLowerCase();
      if (!q) return this.drivers;
      return this.drivers.filter(d =>
        (d.name || '').toLowerCase().includes(q) ||
        (d.ref || '').toLowerCase().includes(q)
      );
    },
    financeEnabled() {
      return this.moduleVisibility.finance !== false;
    },
    jeTotalAmount() {
      const v = parseFloat(this.form.gross_earnings);
      return isNaN(v) || v <= 0 ? 0 : v;
    },
  },

  mounted() {
    axios.get('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=get')
      .then(res => { this.checkGL = !!res.data.check_gl_default; })
      .catch(() => {});
    axios.get('/server/module_visibility/module_visibility.php')
      .then(res => { this.moduleVisibility = res.data; })
      .catch(() => {});
    window.addEventListener('driver-settlement-driver-selected', e => {
      const d = e.detail;
      this.form.driver_ref = d.ref;
      this.form.driver_name = d.name;
      if (this.driverPickerModal) this.driverPickerModal.hide();
      this.autoCalculate();
    });

    window.addEventListener('driver-settlement-selected', e => {
      this.loadRecord(e.detail);
      if (this.searchModal) this.searchModal.hide();
    });
  },

  methods: {
    fmtAmount(v) {
      return Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    formatAmount(v) {
      const n = parseFloat(v) || 0;
      return n.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    showAlert(message, type = 'success') {
      this.alert = { message, type };
      setTimeout(() => { this.alert.message = ''; }, 4000);
    },

    newRecord() {
      this.resetForm();
      this.editing = true;
      this.form.status = 'pending';
    },

    resetForm() {
      this.form = {
        id: null, ref: '', driver_ref: '', driver_name: '',
        month: '', gross_earnings: 0, advances: 0, deductions_total: 0,
        loan_recovery: 0, net_payable: 0, status: 'pending',
      };
      this.editing = false;
      this.isExisting = false;
    },

    cancelEdit() {
      this.editing = false;
      if (!this.form.id) this.resetForm();
    },

    closeRecord() {
      this.resetForm();
    },

    editRecord() {
      if (!this.form.id) return;
      this.editing = true;
    },

    openSearch() {
      if (!this.searchModal)
        this.searchModal = new bootstrap.Modal(document.getElementById('driverSettlementSearchModal'));
      this.searchModal.show();
    },

    openDriverPicker() {
      if (!this.driverPickerModal)
        this.driverPickerModal = new bootstrap.Modal(document.getElementById('driverSettlementDriverPickerModal'));
      this.driverPickerSearch = '';
      this.driverPickerLoading = true;
      this.driverPickerModal.show();
      axios.get('driver_salary_settlement_data.php?action=list_drivers')
        .then(r => { this.drivers = r.data; })
        .catch(() => { this.showAlert('Failed to load drivers.', 'danger'); })
        .finally(() => { this.driverPickerLoading = false; });
    },

    selectDriver(d) {
      this.form.driver_ref = d.ref;
      this.form.driver_name = d.name;
      this.driverPickerModal.hide();
      this.autoCalculate();
    },

    onMonthChange() {
      this.autoCalculate();
    },

    autoCalculate() {
      if (this.form.driver_ref && this.form.month) {
        this.calculate();
      }
    },

    calculate() {
      if (!this.form.driver_ref || !this.form.month) {
        this.showAlert('Please select a driver and month first.', 'warning');
        return;
      }
      this.calculating = true;
      axios.get('driver_salary_settlement_data.php', {
        params: { action: 'calculate', driver_ref: this.form.driver_ref, month: this.form.month }
      }).then(r => {
        const d = r.data;
        if (d.error) { this.showAlert(d.error, 'danger'); return; }
        this.form.gross_earnings   = d.gross_earnings;
        this.form.advances         = d.advances;
        this.form.deductions_total = d.deductions_total;
        this.form.loan_recovery    = d.loan_recovery;
        this.form.net_payable      = d.net_payable;
      }).catch(() => {
        this.showAlert('Calculation failed.', 'danger');
      }).finally(() => {
        this.calculating = false;
      });
    },

    saveRecord() {
      if (!this.form.driver_ref) { this.showAlert('Please select a driver.', 'warning'); return; }
      if (!this.form.month) { this.showAlert('Please select a month.', 'warning'); return; }

      const proceed = (action) => {
        axios.post('driver_salary_settlement_data.php?action=' + action, this.form)
          .then(r => {
            const d = r.data;
            if (d.success) {
              this.form.ref = d.ref;
              this.form.id = d.row?.id;
              this.editing = false;
              this.isExisting = true;
              this.showAlert('Settlement saved successfully. Ref: ' + d.ref, 'success');
            } else {
              this.showAlert(d.error || 'Save failed.', 'danger');
            }
          }).catch(() => {
            this.showAlert('Save failed.', 'danger');
          });
      };

      if (this.isExisting) {
        axios.get('driver_salary_settlement_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.showAlert('Record not found. Please search again.', 'danger'); return; }
            proceed('update');
          })
          .catch(() => { this.showAlert('Failed to verify record.', 'danger'); });
      } else {
        proceed('save');
      }
    },

    deleteRecord() {
      if (!this.form.id) return;
      if (!confirm('Delete this settlement record?')) return;
      axios.delete('driver_salary_settlement_data.php?action=delete&id=' + this.form.id)
        .then(() => { this.resetForm(); this.showAlert('Record deleted.', 'success'); })
        .catch(() => { this.showAlert('Delete failed.', 'danger'); });
    },

    loadRecord(row) {
      this.form = {
        id:              row.id,
        ref:             row.ref,
        driver_ref:      row.driver_ref || row.drivers?.ref || '',
        driver_name:     row.drivers?.name || '',
        month:           row.month,
        gross_earnings:  row.gross_earnings,
        advances:        row.advances,
        deductions_total: row.deductions_total,
        loan_recovery:   row.loan_recovery,
        net_payable:     row.net_payable,
        status:          row.status,
      };
      this.editing = false;
      this.isExisting = true;
    },

    printRecord() {
      if (!this.form.id) return;
      const p = new URLSearchParams({
        ref:             this.form.ref,
        driver_ref:      this.form.driver_ref,
        driver_name:     this.form.driver_name,
        month:           this.form.month,
        gross_earnings:  this.form.gross_earnings,
        advances:        this.form.advances,
        deductions_total: this.form.deductions_total,
        loan_recovery:   this.form.loan_recovery,
        net_payable:     this.form.net_payable,
        status:          this.form.status,
      });
      window.open('driver_salary_settlement_print.php?' + p.toString(), '_blank');
    },
  }
}).mount('#driver-settlement-app');
