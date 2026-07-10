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
      rows: [],
      summary: {},
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
      axios.get('/modules/reports/general_reports/cash_flow_print/cash_flow_print_data.php?from='+this.dateFrom+'&to='+this.dateTo)
        .then(r => { this.rows = r.data.rows; this.summary = r.data.summary; this.ran = true; })
        .catch(() => this.error = 'Failed to load report.')
        .finally(() => this.loading = false);
    },
  },
}).mount('#cash-flow-report-app');
