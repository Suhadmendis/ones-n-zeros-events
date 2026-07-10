const { createApp } = Vue;

createApp({
  data() {
    const now = new Date();
    return {
      month: ReportUtils.monthKey(now.getFullYear(), now.getMonth()),
      driver_id: '',
      drivers: [],
      loading: false,
      ran: false,
      error: '',
      slip: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    axios.get('/modules/reports/staff_reports/driver_salary_sheet/driver_salary_sheet_data.php?action=list_drivers')
      .then(r => { this.drivers = r.data; })
      .catch(() => {});
  },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      this.month = ReportUtils.monthKey(year, month);
      this.load();
    },
    load() {
      if (!this.driver_id) return;
      this.loading = true;
      this.error = '';
      const params = new URLSearchParams({ action: 'report', month: this.month, driver_ref: this.driver_id });
      axios.get('/modules/reports/staff_reports/driver_salary_sheet/driver_salary_sheet_data.php?' + params.toString())
        .then(r => {
          if (r.data.error) { this.error = r.data.error; return; }
          this.slip = r.data;
          this.ran = true;
        })
        .catch(() => { this.error = 'Failed to load payslip.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#driver-salary-sheet-app');
