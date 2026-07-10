const { createApp } = Vue;
createApp({
  data() {
    const { from, to } = ReportUtils.currentMonthRange();
    return {
      from,
      to,
      loading: false,
      ran: false,
      error: '',
      rows: [],
      summary: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() { this.load(); },
  methods: {
    fmt: ReportUtils.fmt,
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.from = from;
      this.to   = to;
      this.load();
    },
    load() {
      if (!this.from || !this.to) { this.error = 'Please select both dates.'; return; }
      this.loading = true;
      this.error = '';
      axios.get('/modules/reports/operations_reports/route_profitability/route_profitability_data.php', {
        params: { action: 'report', from: this.from, to: this.to },
      })
      .then(r => { this.rows = r.data.rows || []; this.summary = r.data.summary || null; this.ran = true; })
      .catch(() => { this.error = 'Failed to load data.'; })
      .finally(() => { this.loading = false; });
    },
  },
}).mount('#route-profit-app');
