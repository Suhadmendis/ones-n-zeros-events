const { createApp } = Vue;
createApp({
  data() {
    const now = new Date();
    return {
      year: now.getFullYear(),
      years: Array.from({ length: 5 }, (_, i) => now.getFullYear() - i),
      loading: false,
      ran: false,
      error: '',
      rows: [],
      chart: null,
    };
  },
  mounted() {
    this.load();
  },
  methods: {
    fmt: ReportUtils.fmt,
    load() {
      this.loading = true;
      this.error = '';
      axios
        .get('/modules/reports/financial_reports/revenue_trend/revenue_trend_data.php?action=report&year=' + this.year)
        .then(r => {
          this.rows = r.data;
          this.ran  = true;
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
      const el = document.querySelector('#revTrendChart');
      if (!el) return;
      this.chart = new ApexCharts(el, {
        chart: { type: 'line', height: 280, toolbar: { show: false } },
        series: [{ name: 'Revenue', data: this.rows.map(r => r.revenue) }],
        xaxis: { categories: this.rows.map(r => r.month_label) },
        colors: ['#10b981'],
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 4 },
        yaxis: { labels: { formatter: v => 'LKR ' + Number(v).toLocaleString() } },
        tooltip: { y: { formatter: v => 'LKR ' + parseFloat(v).toLocaleString('en-LK', { minimumFractionDigits: 2 }) } },
      });
      this.chart.render();
    },
  },
}).mount('#rev-trend-app');
