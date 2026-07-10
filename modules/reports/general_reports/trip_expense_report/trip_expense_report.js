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
      summary: null,
      months: ReportUtils.buildMonths(),
    };
  },
  mounted() {
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
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
          '/modules/reports/general_reports/trip_expense_report/trip_expense_report_data.php?action=report'
            + '&from=' + this.dateFrom
            + '&to='   + this.dateTo
        )
        .then(r => {
          this.rows    = r.data.rows    || [];
          this.summary = r.data.summary || null;
          this.ran     = true;
        })
        .catch(() => {
          this.error = 'Failed to load report.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
}).mount('#trip-expense-app');
