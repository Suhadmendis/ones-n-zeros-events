const { createApp } = Vue;
createApp({
  data() {
    const { from: dateFrom, to: dateTo } = ReportUtils.currentMonthRange();
    return {
      dateFrom,
      dateTo,
      loading: false,
      ran: false,
      error: '',
      s: {},
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() { this.load(); },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.dateFrom = from;
      this.dateTo   = to;
      this.load();
    },
    load() {
      this.loading = true; this.error = '';
      axios.get('/modules/reports/general_reports/business_summary_dashboard/business_summary_dashboard_data.php?from='+this.dateFrom+'&to='+this.dateTo)
        .then(r => { this.s = r.data; this.ran = true; })
        .catch(() => this.error = 'Failed to load report.')
        .finally(() => this.loading = false);
    },
  },
}).mount('#biz-summary-app');
