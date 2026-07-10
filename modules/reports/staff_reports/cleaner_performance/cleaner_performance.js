const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      cleaner_id: '',
      cleaners: [],
      loading: false,
      ran: false,
      error: '',
      rows: [],
      summary: {},
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    axios.get('/modules/reports/staff_reports/cleaner_performance/cleaner_performance_data.php?action=list_cleaners')
      .then(r => { this.cleaners = r.data; })
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
      if (this.cleaner_id) params.set('cleaner_ref', this.cleaner_id);
      axios.get('/modules/reports/staff_reports/cleaner_performance/cleaner_performance_data.php?' + params.toString())
        .then(r => { this.rows = r.data.rows; this.summary = r.data.summary; this.ran = true; })
        .catch(() => { this.error = 'Failed to load report.'; })
        .finally(() => { this.loading = false; });
    },
  },
}).mount('#cleaner-perf-app');
