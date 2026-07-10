const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      employee_id: '',
      employees: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
      summary: {},
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    axios.get('/modules/reports/staff_reports/employee_advance_report/employee_advance_report_data.php?action=list_employees')
      .then(r => { this.employees = r.data; })
      .catch(() => {});
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.from = from;
      this.to   = to;
      this.load();
    },
    load() {
      this.loading = true;
      this.error = '';
      const params = new URLSearchParams({ action: 'report', from: this.from, to: this.to });
      if (this.employee_id) params.set('employee_ref', this.employee_id);
      axios.get('/modules/reports/staff_reports/employee_advance_report/employee_advance_report_data.php?' + params.toString())
        .then(r => { this.rows = r.data.rows; this.summary = r.data.summary; this.ran = true; })
        .catch(() => { this.error = 'Failed to load report.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#employee-advance-app');
