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
        cleaner_ref: '',
        cleaner_name: '',
        month: '',
        gross_earnings: 0,
        advances: 0,
        net_payable: 0,
        status: 'pending',
      },
      alert: { message: '', type: 'success' },
      cleanerPickerSearch: '',
      cleanerPickerLoading: false,
      cleaners: [],
      cleanerPickerModal: null,
      searchModal: null,
    };
  },

  watch: {
    checkGL(val) {
      axios.post('/modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php?action=save', { check_gl_default: val });
    },
  },

  computed: {
    filteredCleaners() {
      const q = this.cleanerPickerSearch.toLowerCase();
      if (!q) return this.cleaners;
      return this.cleaners.filter(c =>
        (c.name || '').toLowerCase().includes(q) ||
        (c.ref || '').toLowerCase().includes(q)
      );
    },
    financeEnabled() {
      return this.moduleVisibility.finance === true;
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
    window.addEventListener('cleaner-settlement-cleaner-selected', e => {
      const c = e.detail;
      this.form.cleaner_ref = c.ref;
      this.form.cleaner_name = c.name;
      if (this.cleanerPickerModal) this.cleanerPickerModal.hide();
      this.autoCalculate();
    });

    window.addEventListener('cleaner-settlement-selected', e => {
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
        id: null, ref: '', cleaner_ref: '', cleaner_name: '',
        month: '', gross_earnings: 0, advances: 0, net_payable: 0, status: 'pending',
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
        this.searchModal = new bootstrap.Modal(document.getElementById('cleanerSettlementSearchModal'));
      this.searchModal.show();
    },

    openCleanerPicker() {
      if (!this.cleanerPickerModal)
        this.cleanerPickerModal = new bootstrap.Modal(document.getElementById('cleanerSettlementCleanerPickerModal'));
      this.cleanerPickerSearch = '';
      this.cleanerPickerLoading = true;
      this.cleanerPickerModal.show();
      axios.get('cleaner_salary_settlement_data.php?action=list_cleaners')
        .then(r => { this.cleaners = r.data; })
        .catch(() => { this.showAlert('Failed to load cleaners.', 'danger'); })
        .finally(() => { this.cleanerPickerLoading = false; });
    },

    selectCleaner(c) {
      this.form.cleaner_ref = c.ref;
      this.form.cleaner_name = c.name;
      this.cleanerPickerModal.hide();
      this.autoCalculate();
    },

    onMonthChange() {
      this.autoCalculate();
    },

    autoCalculate() {
      if (this.form.cleaner_ref && this.form.month) {
        this.calculate();
      }
    },

    calculate() {
      if (!this.form.cleaner_ref || !this.form.month) {
        this.showAlert('Please select a cleaner and month first.', 'warning');
        return;
      }
      this.calculating = true;
      axios.get('cleaner_salary_settlement_data.php', {
        params: { action: 'calculate', cleaner_ref: this.form.cleaner_ref, month: this.form.month }
      }).then(r => {
        const d = r.data;
        if (d.error) { this.showAlert(d.error, 'danger'); return; }
        this.form.gross_earnings = d.gross_earnings;
        this.form.advances       = d.advances;
        this.form.net_payable    = d.net_payable;
      }).catch(() => {
        this.showAlert('Calculation failed.', 'danger');
      }).finally(() => {
        this.calculating = false;
      });
    },

    saveRecord() {
      if (!this.form.cleaner_ref) { this.showAlert('Please select a cleaner.', 'warning'); return; }
      if (!this.form.month) { this.showAlert('Please select a month.', 'warning'); return; }

      const proceed = (action) => {
        axios.post('cleaner_salary_settlement_data.php?action=' + action, this.form)
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
        axios.get('cleaner_salary_settlement_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
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
      axios.delete('cleaner_salary_settlement_data.php?action=delete&id=' + this.form.id)
        .then(() => { this.resetForm(); this.showAlert('Record deleted.', 'success'); })
        .catch(() => { this.showAlert('Delete failed.', 'danger'); });
    },

    loadRecord(row) {
      this.form = {
        id:             row.id,
        ref:            row.ref,
        cleaner_ref:    row.cleaner_ref || row.cleaners?.ref || '',
        cleaner_name:   row.cleaners?.name || '',
        month:          row.month,
        gross_earnings: row.gross_earnings,
        advances:       row.advances,
        net_payable:    row.net_payable,
        status:         row.status,
      };
      this.editing = false;
      this.isExisting = true;
    },

    printRecord() {
      if (!this.form.id) return;
      const p = new URLSearchParams({
        ref:            this.form.ref,
        cleaner_ref:    this.form.cleaner_ref,
        cleaner_name:   this.form.cleaner_name,
        month:          this.form.month,
        gross_earnings: this.form.gross_earnings,
        advances:       this.form.advances,
        net_payable:    this.form.net_payable,
        status:         this.form.status,
      });
      window.open('cleaner_salary_settlement_print.php?' + p.toString(), '_blank');
    },
  }
}).mount('#cleaner-settlement-app');
