const { createApp } = Vue;
createApp({
  data() {
    const now = new Date();
    return {
      month: ReportUtils.monthKey(now.getFullYear(), now.getMonth()),
      loading: false,
      ran: false,
      error: '',
      rows: [],
      summary: {},
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() { this.load(); },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      this.month = ReportUtils.monthKey(year, month);
      this.load();
    },
    load() {
      this.loading = true; this.error = '';
      axios.get('/modules/reports/general_reports/driver_salary_summary/driver_salary_summary_data.php?month='+this.month)
        .then(r => { this.rows = r.data.rows; this.summary = r.data.summary; this.ran = true; })
        .catch(() => this.error = 'Failed to load report.')
        .finally(() => this.loading = false);
    },
  },
}).mount('#driver-salary-summary-app');
