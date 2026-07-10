const { createApp } = Vue;

createApp({
  data() {
    const now = new Date();
    return {
      month: ReportUtils.monthKey(now.getFullYear(), now.getMonth()),
      cleaner_id: '',
      cleaners: [],
      loading: false,
      ran: false,
      error: '',
      slip: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    axios.get('/modules/reports/staff_reports/cleaner_salary_report/cleaner_salary_report_data.php?action=list_cleaners')
      .then(r => { this.cleaners = r.data; })
      .catch(() => {});
  },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      this.month = ReportUtils.monthKey(year, month);
      this.load();
    },
    load() {
      if (!this.cleaner_id) return;
      this.loading = true;
      this.error = '';
      const params = new URLSearchParams({ action: 'report', month: this.month, cleaner_ref: this.cleaner_id });
      axios.get('/modules/reports/staff_reports/cleaner_salary_report/cleaner_salary_report_data.php?' + params.toString())
        .then(r => {
          if (r.data.error) { this.error = r.data.error; return; }
          this.slip = r.data;
          this.ran = true;
        })
        .catch(() => { this.error = 'Failed to load payslip.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#cleaner-salary-report-app');
