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
      totals: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    fmtNum(v) {
      return parseFloat(v || 0).toLocaleString('en-LK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      });
    },
    selectMonth(year, month) {
      const { from, to } = ReportUtils.monthRange(year, month);
      this.dateFrom = from;
      this.dateTo   = to;
      this.load();
    },
    load() {
      this.loading = true;
      this.error = '';
      axios
        .get(
          '/modules/reports/general_reports/lorry_vs_trips_report/lorry_vs_trips_report_data.php?action=report'
            + '&from=' + this.dateFrom
            + '&to='   + this.dateTo
        )
        .then(r => {
          this.rows   = r.data.rows   || [];
          this.totals = r.data.totals || null;
          this.ran    = true;
        })
        .catch(() => {
          this.error = 'Failed to load report.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
}).mount('#lorry-trips-app');
