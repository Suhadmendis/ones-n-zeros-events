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
      totals: { total_inflow: 0, total_outflow: 0, final_balance: 0 },
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
      this.from = from;
      this.to   = to;
      this.load();
    },
    load() {
      this.loading = true;
      this.error = '';
      axios
        .get('/modules/reports/financial_reports/cash_flow_statement/cash_flow_statement_data.php?action=report&from=' + this.from + '&to=' + this.to)
        .then(r => {
          this.rows   = r.data.rows   || [];
          this.totals = r.data.totals || { total_inflow: 0, total_outflow: 0, final_balance: 0 };
          this.ran    = true;
        })
        .catch(() => {
          this.error = 'Failed to load data.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
}).mount('#cashflow-stmt-app');
