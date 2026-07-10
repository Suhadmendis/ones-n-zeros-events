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
      total: 0,
      chart: null,
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
        .get('/modules/reports/financial_reports/expense_summary/expense_summary_data.php?action=report&from=' + this.from + '&to=' + this.to)
        .then(r => {
          this.rows  = r.data.rows  || [];
          this.total = r.data.total || 0;
          this.ran   = true;
          this.$nextTick(() => this.renderChart());
        })
        .catch(() => {
          this.error = 'Failed to load data.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
    renderChart() {
      if (this.chart) { this.chart.destroy(); this.chart = null; }
      const el = document.querySelector('#expSummaryChart');
      if (!el || !this.rows.length) return;
      const nonZero = this.rows.filter(r => r.amount > 0);
      if (!nonZero.length) return;
      this.chart = new ApexCharts(el, {
        chart: { type: 'donut', height: 280 },
        series: nonZero.map(r => r.amount),
        labels: nonZero.map(r => r.category),
        legend: { position: 'bottom' },
        tooltip: { y: { formatter: v => 'LKR ' + Number(v).toLocaleString('en-LK', { minimumFractionDigits: 2 }) } },
      });
      this.chart.render();
    },
  },
}).mount('#exp-summary-app');
