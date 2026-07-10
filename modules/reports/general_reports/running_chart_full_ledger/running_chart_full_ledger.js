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
    rowClass(r) {
      if (r.inflow > 0)  return 'table-success';
      if (r.outflow > 0) return 'table-danger';
      return '';
    },
    balanceClass(v) {
      if (v > 0)  return 'text-success fw-bold';
      if (v < 0)  return 'text-danger fw-bold';
      return 'fw-bold';
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
          '/modules/reports/general_reports/running_chart_full_ledger/running_chart_full_ledger_data.php?action=report'
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
}).mount('#ledger-app');
